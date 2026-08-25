<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessHearingTranscription;
use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\HearingTranscription;
use App\Models\IntegrationCredential;
use App\Models\LegalCase;
use App\Services\DocxDocumentRenderer;
use App\Services\HearingAudioStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class HearingTranscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', HearingTranscription::class);

        $records = HearingTranscription::query()
            ->visibleTo($request->user())
            ->with(['client:id,name', 'legalCase:id,title', 'uploader:id,name'])
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim($request->string('search')->toString());
                $query->where(fn (Builder $nested) => $nested
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.hearing-transcriptions.index', [
            'pageTitle' => 'Transcrição de Audiências',
            'records' => $records,
            'clients' => Client::query()->visibleTo($request->user())->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'cases' => LegalCase::query()->visibleTo($request->user())->where('is_active', true)->orderBy('title')->get(['id', 'client_id', 'title']),
            'events' => $this->availableHearingEvents($request)
                ->where('start_at', '>=', now()->subMonths(3))
                ->orderByDesc('start_at')
                ->limit(250)
                ->get(['id', 'title', 'start_at']),
            'providerEnabled' => IntegrationCredential::query()->where('service', 'legal_ai')->where('enabled', true)->exists(),
        ]);
    }

    public function store(Request $request, HearingAudioStorage $storage): JsonResponse
    {
        $this->authorize('create', HearingTranscription::class);
        $user = $request->user();
        $clientIds = Client::query()->visibleTo($user)->pluck('id')->all();
        $caseIds = LegalCase::query()->visibleTo($user)->pluck('id')->all();
        $eventIds = $this->availableHearingEvents($request)->pluck('id')->all();
        $maximumKilobytes = (int) config('legal_productivity.hearing_audio.max_size_kb', 262144);
        $maximumDuration = (int) config('legal_productivity.hearing_audio.max_duration_seconds', 14400);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'client_id' => ['nullable', 'integer', Rule::in($clientIds)],
            'legal_case_id' => ['nullable', 'integer', Rule::in($caseIds)],
            'calendar_event_id' => ['nullable', 'integer', Rule::in($eventIds)],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:'.$maximumDuration],
            'audio' => ['required', 'file', 'max:'.$maximumKilobytes],
            'recording_legal_notice' => ['accepted'],
        ]);

        if (filled($validated['legal_case_id'] ?? null)) {
            $caseClientId = LegalCase::query()->visibleTo($user)->whereKey($validated['legal_case_id'])->value('client_id');
            if (filled($validated['client_id'] ?? null) && (int) $validated['client_id'] !== (int) $caseClientId) {
                throw ValidationException::withMessages(['client_id' => 'O cliente não pertence ao processo selecionado.']);
            }
            $validated['client_id'] = $caseClientId;
        }

        if (filled($validated['calendar_event_id'] ?? null)) {
            $event = $this->availableHearingEvents($request)->findOrFail($validated['calendar_event_id']);
            if (filled($validated['legal_case_id'] ?? null) && filled($event->legal_case_id) && (int) $validated['legal_case_id'] !== (int) $event->legal_case_id) {
                throw ValidationException::withMessages(['calendar_event_id' => 'A audiência selecionada pertence a outro processo.']);
            }
            if (filled($validated['client_id'] ?? null) && filled($event->client_id) && (int) $validated['client_id'] !== (int) $event->client_id) {
                throw ValidationException::withMessages(['calendar_event_id' => 'A audiência selecionada pertence a outro cliente.']);
            }

            $validated['legal_case_id'] ??= $event->legal_case_id;
            $validated['client_id'] ??= $event->client_id;
        }

        $stored = $storage->store($request->file('audio'), $validated['duration_seconds'] ?? null);

        try {
            $record = DB::transaction(fn () => HearingTranscription::query()->create($stored + [
                'title' => trim($validated['title']),
                'client_id' => $validated['client_id'] ?? null,
                'legal_case_id' => $validated['legal_case_id'] ?? null,
                'calendar_event_id' => $validated['calendar_event_id'] ?? null,
                'uploaded_by' => $user?->id,
                'status' => 'uploaded',
                'review_status' => 'not_reviewed',
            ]));
        } catch (Throwable $exception) {
            $storage->delete($stored['disk'], $stored['path']);
            throw $exception;
        }

        $providerEnabled = IntegrationCredential::query()->where('service', 'legal_ai')->where('enabled', true)->exists();
        if ($providerEnabled) {
            ProcessHearingTranscription::dispatch($record->id);
            $message = 'Áudio armazenado de forma privada e enviado para processamento.';
        } else {
            $record->forceFill(['status' => 'configuration_required'])->save();
            $message = 'Áudio armazenado de forma privada. Configure o provedor antes de processar.';
        }

        activity_log('hearing_transcriptions', 'created', $record, [
            'client_id' => $record->client_id,
            'legal_case_id' => $record->legal_case_id,
            'sha256' => $record->sha256,
            'size' => $record->size,
        ], 'Áudio de audiência armazenado em área privada.');

        return response()->json(['message' => $message, 'reload' => true]);
    }

    public function show(HearingTranscription $transcription, Request $request): View
    {
        $this->authorize('view', $transcription);
        $transcription->load(['client:id,name', 'legalCase:id,title', 'uploader:id,name', 'reviewer:id,name', 'approver:id,name']);

        return view('admin.hearing-transcriptions.show', [
            'pageTitle' => $transcription->title,
            'record' => $transcription,
        ]);
    }

    public function update(HearingTranscription $transcription, Request $request): JsonResponse
    {
        $this->authorize('update', $transcription);
        if ($transcription->review_status === 'approved') {
            return response()->json(['message' => 'Uma ata aprovada não pode ser sobrescrita.'], 422);
        }

        $validated = $request->validate([
            'transcript_edited' => ['required', 'string', 'max:2000000'],
            'minutes_draft' => ['required', 'string', 'max:500000'],
        ]);
        $transcription->forceFill([
            'transcript_edited' => $validated['transcript_edited'],
            'minutes_draft' => $validated['minutes_draft'],
            'review_status' => 'reviewed',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ])->save();

        activity_log('hearing_transcriptions', 'reviewed', $transcription, ['review_status' => 'reviewed'], 'Transcrição e ata revisadas por usuário autorizado.');

        return response()->json(['message' => 'Revisão salva.', 'reload' => true]);
    }

    public function approve(HearingTranscription $transcription, Request $request): JsonResponse
    {
        $this->authorize('update', $transcription);
        if ($transcription->review_status !== 'reviewed' || blank($transcription->minutes_draft)) {
            return response()->json(['message' => 'Revise a transcrição e a ata antes de aprovar.'], 422);
        }

        $transcription->forceFill([
            'status' => 'approved',
            'review_status' => 'approved',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ])->save();

        activity_log('hearing_transcriptions', 'approved', $transcription, ['review_status' => 'approved'], 'Ata de audiência aprovada por usuário autorizado.');

        return response()->json(['message' => 'Ata aprovada.', 'reload' => true]);
    }

    public function process(HearingTranscription $transcription): JsonResponse
    {
        $this->authorize('update', $transcription);
        if (in_array($transcription->status, ['processing', 'pending_review', 'approved'], true)) {
            return response()->json(['message' => 'Este registro não está disponível para novo processamento.'], 422);
        }

        $transcription->forceFill(['status' => 'queued', 'processing_error' => null])->save();
        ProcessHearingTranscription::dispatch($transcription->id);

        return response()->json(['message' => 'Processamento enfileirado.', 'reload' => true]);
    }

    public function download(HearingTranscription $transcription): StreamedResponse
    {
        $this->authorize('view', $transcription);
        abort_unless($transcription->disk === config('legal_productivity.hearing_audio.disk', 'hearing_audio'), 404);

        return Storage::disk($transcription->disk)->download($transcription->path, $transcription->original_name, [
            'Content-Type' => $transcription->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function exportMinutes(HearingTranscription $transcription, DocxDocumentRenderer $renderer): BinaryFileResponse
    {
        $this->authorize('view', $transcription);
        abort_unless(filled($transcription->minutes_draft), 404);

        $temporary = tempnam(storage_path('app/private'), 'hearing-minutes-');
        if ($temporary === false) {
            abort(500, 'Não foi possível preparar a exportação privada.');
        }
        $path = $temporary.'.docx';
        rename($temporary, $path);
        $renderer->renderText($path, $transcription->title, strip_tags((string) $transcription->minutes_draft));

        return response()->download($path, 'ata-'.str($transcription->title)->slug().'.docx', [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ])->deleteFileAfterSend(true);
    }

    private function availableHearingEvents(Request $request): Builder
    {
        return CalendarEvent::query()
            ->visibleTo($request->user())
            ->where('event_type', 'hearing');
    }
}
