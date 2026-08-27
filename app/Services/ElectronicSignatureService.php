<?php

namespace App\Services;

use App\Models\LegalDocument;
use App\Models\SignatureDocument;
use App\Models\SignatureEvent;
use App\Models\SignatureRequest;
use App\Models\SignatureSigner;
use App\Notifications\SignatureInvitationNotification;
use App\Notifications\SignatureStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ElectronicSignatureService
{
    private const SUPPORTED_DOCUMENT_FORMATS = [
        'application/pdf' => ['pdf'],
    ];

    public function __construct(private readonly SignedPdfGenerator $signedPdfGenerator)
    {
    }

    public static function supports(LegalDocument $document): bool
    {
        $mime = Str::lower((string) $document->mime_type);
        $extension = Str::lower((string) $document->extension);

        return $document->disk === LegalDocumentStorage::DISK
            && $document->storage_status === 'private'
            && filled($document->sha256)
            && in_array($extension, self::SUPPORTED_DOCUMENT_FORMATS[$mime] ?? [], true);
    }

    public function create(LegalDocument $legalDocument, array $data, int $creatorId): SignatureRequest
    {
        $this->ensureEnabled();

        return DB::transaction(function () use ($legalDocument, $data, $creatorId): SignatureRequest {
            abort_unless(self::supports($legalDocument) && filled($legalDocument->path), 422, 'Formato de documento inelegível para assinatura eletrônica.');
            $disk = Storage::disk('legal_documents');
            abort_unless($disk->exists($legalDocument->path), 422, 'Arquivo original não localizado.');
            $contents = $disk->get($legalDocument->path);
            $hash = hash('sha256', $contents);
            abort_unless(hash_equals((string) $legalDocument->sha256, $hash), 409, 'A integridade do documento original não pôde ser confirmada.');

            $request = SignatureRequest::query()->create([
                'public_uuid' => (string) Str::uuid(), 'legal_document_id' => $legalDocument->id,
                'client_id' => $legalDocument->client_id, 'legal_case_id' => $legalDocument->legal_case_id,
                'created_by' => $creatorId, 'provider' => config('signatures.provider', 'internal'),
                'title' => $data['title'], 'message' => $data['message'] ?? null,
                'ordered' => (bool) ($data['ordered'] ?? false), 'expires_at' => $data['expires_at'],
            ]);
            $immutablePath = 'signatures/'.$request->public_uuid.'/original.'.($legalDocument->extension ?: 'bin');
            $disk->put($immutablePath, $contents);
            SignatureDocument::query()->create([
                'signature_request_id' => $request->id, 'legal_document_id' => $legalDocument->id,
                'disk' => 'legal_documents', 'immutable_path' => $immutablePath,
                'original_name' => $legalDocument->original_name ?: $legalDocument->file_name,
                'mime_type' => $legalDocument->mime_type, 'size' => strlen($contents), 'sha256' => $hash,
            ]);
            foreach ($data['signers'] as $index => $signer) {
                SignatureSigner::query()->create([
                    'public_uuid' => (string) Str::uuid(), 'signature_request_id' => $request->id,
                    'name' => $signer['name'], 'email' => Str::lower($signer['email']),
                    'document_normalized' => filled($signer['document'] ?? null) ? preg_replace('/\D+/', '', $signer['document']) : null,
                    'signing_order' => $index + 1,
                ]);
            }
            $this->event($request, null, 'created', ['signers' => count($data['signers'])], $creatorId);

            return $request->fresh(['document', 'signers']);
        });
    }

    public function send(SignatureRequest $request): void
    {
        $this->ensureEnabled();

        DB::transaction(function () use ($request): void {
            $request->refresh();
            if ($request->isTerminal()) {
                throw ValidationException::withMessages(['request' => 'Esta solicitação já foi encerrada.']);
            }
            $request->update(['status' => SignatureRequest::STATUS_PENDING, 'sent_at' => $request->sent_at ?: now()]);
            foreach ($request->signers()->whereIn('status', ['pending', 'sent'])->get() as $signer) {
                if ($request->ordered && $signer->signing_order !== $this->nextOrder($request)) {
                    continue;
                }
                $this->issueInvitation($request, $signer);
            }
            $this->event($request, null, 'sent');
        });
    }

    public function sign(SignatureSigner $signer, array $evidence): void
    {
        $this->ensureEnabled();

        DB::transaction(function () use ($signer, $evidence): void {
            $signer->load('signatureRequest.document');
            $request = $signer->signatureRequest;
            $this->assertActionable($request, $signer);
            if ($request->ordered && $signer->signing_order !== $this->nextOrder($request)) {
                throw ValidationException::withMessages(['signature' => 'A assinatura anterior ainda está pendente.']);
            }
            $this->assertDocumentIntegrity($request->document);
            $terms = (string) ($evidence['terms_text'] ?? 'Declaro que li e concordo em assinar eletronicamente este documento.');
            $signer->update([
                'status' => 'signed', 'signed_at' => now(), 'token_hash' => null, 'token_expires_at' => null,
                'ip_address' => $evidence['ip_address'] ?? null, 'user_agent' => Str::limit((string) ($evidence['user_agent'] ?? ''), 1000, ''),
                'terms_version' => config('signatures.terms_version', '1.0'), 'terms_hash' => hash('sha256', $terms),
            ]);
            $this->event($request, $signer, 'signed', ['name' => $signer->name, 'terms_hash' => $signer->terms_hash]);
            if (! $request->signers()->where('status', '!=', 'signed')->exists()) {
                $this->complete($request);
            } elseif ($request->ordered) {
                $next = $request->signers()->where('status', 'pending')->orderBy('signing_order')->first();
                if ($next) {
                    $this->issueInvitation($request, $next);
                }
            }
        });
    }

    public function decline(SignatureSigner $signer, ?string $reason): void
    {
        $this->ensureEnabled();

        DB::transaction(function () use ($signer, $reason): void {
            $signer->load('signatureRequest');
            $this->assertActionable($signer->signatureRequest, $signer);
            $signer->update(['status' => 'declined', 'declined_at' => now(), 'decline_reason' => $reason, 'token_hash' => null, 'ip_address' => request()?->ip(), 'user_agent' => Str::limit((string) request()?->userAgent(), 1000, '')]);
            $signer->signatureRequest->update(['status' => SignatureRequest::STATUS_DECLINED]);
            $this->event($signer->signatureRequest, $signer, 'declined', ['reason' => $reason]);
            $this->notifyStatus($signer->signatureRequest, 'declined');
        });
    }

    public function cancel(SignatureRequest $request, string $reason): void
    {
        $this->ensureEnabled();

        if ($request->isTerminal()) {
            throw ValidationException::withMessages(['request' => 'Esta solicitação já foi encerrada.']);
        }
        DB::transaction(function () use ($request, $reason): void {
            $request->update(['status' => SignatureRequest::STATUS_CANCELLED, 'cancelled_at' => now(), 'cancellation_reason' => $reason]);
            $request->signers()->whereNotIn('status', ['signed', 'declined'])->update(['status' => 'cancelled', 'token_hash' => null]);
            $this->event($request, null, 'cancelled', ['reason' => $reason], auth()->id());
            $this->notifyStatus($request, 'cancelled');
        });
    }

    public function expire(SignatureRequest $request): bool
    {
        $this->ensureEnabled();

        if ($request->isTerminal() || ! $request->expires_at?->isPast()) {
            return false;
        }
        DB::transaction(function () use ($request): void {
            $request->update(['status' => SignatureRequest::STATUS_EXPIRED]);
            $request->signers()->whereNotIn('status', ['signed', 'declined'])->update(['status' => 'expired', 'token_hash' => null]);
            $this->event($request, null, 'expired');
            $this->notifyStatus($request, 'expired');
        });

        return true;
    }

    public function resolveToken(string $token): SignatureSigner
    {
        $this->ensureEnabled();

        $signer = SignatureSigner::query()->with('signatureRequest.document')->where('token_hash', hash('sha256', $token))->firstOrFail();
        $this->expire($signer->signatureRequest);
        $signer->refresh();
        abort_if($signer->token_expires_at?->isPast() || $signer->status !== 'sent', 410, 'Link de assinatura expirado ou já utilizado.');

        return $signer;
    }

    public function markViewed(SignatureSigner $signer): void
    {
        $this->ensureEnabled();

        if ($signer->viewed_at) {
            return;
        }
        $signer->update(['viewed_at' => now()]);
        $this->event($signer->signatureRequest, $signer, 'viewed');
    }

    public function verifyEvidence(SignatureRequest $request): bool
    {
        $this->ensureEnabled();

        $document = $request->document;
        if (! $document?->evidence_path || ! $document->completed_path) {
            return false;
        }
        $disk = Storage::disk($document->disk);
        if (! $disk->exists($document->evidence_path) || ! $disk->exists($document->completed_path)) {
            return false;
        }
        $contents = $disk->get($document->evidence_path);
        $completed = $disk->get($document->completed_path);

        return hash_equals((string) $document->evidence_sha256, hash('sha256', $contents))
            && hash_equals((string) $document->completed_sha256, hash('sha256', $completed))
            && $this->documentIsIntact($document);
    }

    private function issueInvitation(SignatureRequest $request, SignatureSigner $signer): void
    {
        $token = Str::random(64);
        $expires = collect([$request->expires_at, now()->addHours((int) config('signatures.token_expiration_hours', 72))])->filter()->sort()->first();
        $signer->update(['status' => 'sent', 'token_hash' => hash('sha256', $token), 'token_expires_at' => $expires, 'sent_at' => now()]);
        Notification::route('mail', [$signer->email => $signer->name])->notify(new SignatureInvitationNotification($signer, $token));
    }

    private function complete(SignatureRequest $request): void
    {
        $request->load('document', 'signers', 'events');
        $document = $request->document;
        $disk = Storage::disk($document->disk);
        $completedPath = 'signatures/'.$request->public_uuid.'/completed.'.pathinfo($document->immutable_path, PATHINFO_EXTENSION);
        $completedAt = now();
        $request->forceFill(['completed_at' => $completedAt]);
        $completedPdf = $this->signedPdfGenerator->generate($disk->path($document->immutable_path), $request);
        $disk->put($completedPath, $completedPdf);
        $completedSha256 = hash('sha256', $completedPdf);
        $evidence = json_encode([
            'request_uuid' => $request->public_uuid, 'document_sha256' => $document->sha256,
            'completed_document_sha256' => $completedSha256, 'completed_at' => $completedAt->toIso8601String(),
            'signers' => $request->signers->map(fn ($s) => ['uuid' => $s->public_uuid, 'name' => $s->name, 'email' => $s->email, 'signed_at' => $s->signed_at?->toIso8601String(), 'ip' => $s->ip_address, 'terms_hash' => $s->terms_hash])->all(),
            'events' => $request->events->map(fn ($e) => ['type' => $e->type, 'at' => $e->occurred_at?->toIso8601String(), 'document_hash' => $e->document_hash])->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($evidence === false) {
            throw new RuntimeException('Não foi possível gerar o comprovante.');
        }
        $evidencePath = 'signatures/'.$request->public_uuid.'/evidence.json';
        $disk->put($evidencePath, $evidence);
        $document->update(['completed_path' => $completedPath, 'completed_sha256' => $completedSha256, 'evidence_path' => $evidencePath, 'evidence_sha256' => hash('sha256', $evidence)]);
        $request->update(['status' => SignatureRequest::STATUS_COMPLETED, 'completed_at' => $completedAt]);
        $this->event($request, null, 'completed');
        $this->notifyStatus($request, 'completed');
    }

    private function assertActionable(SignatureRequest $request, SignatureSigner $signer): void
    {
        $this->expire($request);
        if ($request->fresh()->isTerminal() || $signer->status !== 'sent') {
            throw ValidationException::withMessages(['signature' => 'Assinatura indisponível.']);
        }
    }

    private function assertDocumentIntegrity(SignatureDocument $document): void
    {
        if (! $this->documentIsIntact($document)) {
            throw ValidationException::withMessages(['document' => 'A integridade do documento foi violada.']);
        }
    }

    private function documentIsIntact(SignatureDocument $document): bool
    {
        $disk = Storage::disk($document->disk);

        return $disk->exists($document->immutable_path) && hash_equals($document->sha256, hash('sha256', $disk->get($document->immutable_path)));
    }

    private function nextOrder(SignatureRequest $request): int
    {
        return (int) ($request->signers()->whereIn('status', ['pending', 'sent'])->min('signing_order') ?? 0);
    }

    private function event(SignatureRequest $request, ?SignatureSigner $signer, string $type, array $metadata = [], ?int $actorId = null): void
    {
        SignatureEvent::query()->create(['signature_request_id' => $request->id, 'signature_signer_id' => $signer?->id, 'type' => $type, 'actor_type' => $actorId ? 'user' : ($signer ? 'signer' : 'system'), 'actor_id' => $actorId, 'occurred_at' => now(), 'ip_address' => request()?->ip(), 'user_agent' => Str::limit((string) request()?->userAgent(), 1000, ''), 'metadata' => $metadata ?: null, 'document_hash' => $request->document?->sha256]);
    }

    private function notifyStatus(SignatureRequest $request, string $event): void
    {
        foreach ($request->signers()->get(['name', 'email']) as $recipient) {
            Notification::route('mail', [$recipient->email => $recipient->name])->notify(new SignatureStatusNotification($request, $event));
        }
    }

    private function ensureEnabled(): void
    {
        abort_unless((bool) config('signatures.enabled', false), 404);
    }
}
