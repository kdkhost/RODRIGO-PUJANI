<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalCaseUpdate;
use App\Models\LegalUpdateSummary;
use App\Services\LegalProductivityProviderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LegalUpdateSummaryController extends Controller
{
    public function generate(string $update, Request $request, LegalProductivityProviderManager $manager): JsonResponse
    {
        $legalUpdate = $this->resolveUpdate($update, $request);
        $source = $this->sourceText($legalUpdate);
        if ($source === '') {
            return response()->json(['message' => 'A publicação não possui conteúdo para resumir.'], 422);
        }

        $lock = Cache::lock('legal-update-summary-generation:'.$legalUpdate->id, max(180, (int) config('legal_productivity.ai.timeout_seconds', 120) + 30));
        if (! $lock->get()) {
            return response()->json(['message' => 'Já existe uma geração em andamento para esta publicação.'], 409);
        }

        try {
            try {
                $result = $manager->provider()->summarize($source);
            } catch (RuntimeException $exception) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            $created = DB::transaction(function () use ($legalUpdate, $source, $result, $request): array {
                $lockedUpdate = LegalCaseUpdate::query()->whereKey($legalUpdate->id)->lockForUpdate()->firstOrFail();
                if (! hash_equals(hash('sha256', $source), hash('sha256', $this->sourceText($lockedUpdate)))) {
                    return ['error' => 'O conteúdo original mudou durante a geração. Solicite uma nova versão.'];
                }

                $version = (int) LegalUpdateSummary::query()
                    ->where('legal_case_update_id', $legalUpdate->id)
                    ->max('version') + 1;

                return ['summary' => LegalUpdateSummary::query()->create([
                    'legal_case_update_id' => $legalUpdate->id,
                    'version' => $version,
                    'source_sha256' => hash('sha256', $source),
                    'summary_text' => $result['text'],
                    'status' => 'draft',
                    'provider' => $result['provider'],
                    'model' => $result['model'],
                    'generation_metadata' => $result['metadata'] ?? [],
                    'generated_by' => $request->user()?->id,
                    'generated_at' => now(),
                ])];
            });
        } finally {
            $lock->release();
        }

        if (isset($created['error'])) {
            return response()->json(['message' => $created['error']], 409);
        }
        /** @var LegalUpdateSummary $summary */
        $summary = $created['summary'];

        activity_log('legal_update_summaries', 'generated', $summary, [
            'legal_case_update_id' => $legalUpdate->id,
            'version' => $summary->version,
            'provider' => $summary->provider,
            'model' => $summary->model,
        ], 'Resumo assistido gerado como rascunho para revisão humana.');

        return response()->json([
            'message' => 'Resumo gerado como rascunho.',
            'summary' => $summary,
            'redirect' => $this->redirectFor($legalUpdate),
        ]);
    }

    public function update(LegalUpdateSummary $summary, Request $request): JsonResponse
    {
        $legalUpdate = $this->guardSummary($summary, $request);
        $validated = $request->validate(['summary_text' => ['required', 'string', 'max:30000']]);
        $result = DB::transaction(function () use ($summary, $validated, $request): array {
            $locked = LegalUpdateSummary::query()->whereKey($summary->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, ['draft', 'reviewed'], true)) {
                return ['error' => 'Somente rascunhos podem ser editados.'];
            }

            $locked->forceFill([
                'summary_text' => trim($validated['summary_text']),
                'status' => 'reviewed',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
            ])->save();

            return ['summary' => $locked];
        });
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 422);
        }
        $summary = $result['summary'];

        activity_log('legal_update_summaries', 'reviewed', $summary, [
            'legal_case_update_id' => $summary->legal_case_update_id,
            'version' => $summary->version,
        ], 'Resumo assistido revisado por usuário autorizado.');

        return response()->json(['message' => 'Resumo revisado e salvo.', 'redirect' => $this->redirectFor($legalUpdate)]);
    }

    public function approve(LegalUpdateSummary $summary, Request $request): JsonResponse
    {
        $legalUpdate = $this->guardSummary($summary, $request);
        $result = DB::transaction(function () use ($summary, $legalUpdate, $request): array {
            $lockedUpdate = LegalCaseUpdate::query()->whereKey($legalUpdate->id)->lockForUpdate()->firstOrFail();
            $locked = LegalUpdateSummary::query()->whereKey($summary->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, ['draft', 'reviewed'], true)) {
                return ['error' => 'O resumo não está disponível para aprovação.'];
            }
            if (! hash_equals($locked->source_sha256, hash('sha256', $this->sourceText($lockedUpdate)))) {
                return ['error' => 'O conteúdo original mudou. Gere e revise uma nova versão antes da aprovação.'];
            }

            $locked->forceFill([
                'status' => 'approved',
                'approved_by' => $request->user()?->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ])->save();

            return ['summary' => $locked];
        });
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 422);
        }
        $summary = $result['summary'];

        activity_log('legal_update_summaries', 'approved', $summary, [
            'legal_case_update_id' => $summary->legal_case_update_id,
            'version' => $summary->version,
        ], 'Resumo assistido aprovado, ainda sem publicação automática.');

        return response()->json([
            'message' => 'Resumo aprovado. A publicação ao cliente ainda é uma ação separada.',
            'redirect' => $this->redirectFor($legalUpdate),
        ]);
    }

    public function reject(LegalUpdateSummary $summary, Request $request): JsonResponse
    {
        $legalUpdate = $this->guardSummary($summary, $request);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $result = DB::transaction(function () use ($summary, $validated, $request): array {
            $locked = LegalUpdateSummary::query()->whereKey($summary->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, ['draft', 'reviewed', 'approved'], true)) {
                return ['error' => 'Um resumo publicado ou já rejeitado não pode ser rejeitado por esta ação.'];
            }

            $locked->forceFill([
                'status' => 'rejected',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'published_by' => null,
                'published_at' => null,
                'rejection_reason' => $validated['reason'],
            ])->save();

            return ['summary' => $locked];
        });
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 422);
        }
        $summary = $result['summary'];

        activity_log('legal_update_summaries', 'rejected', $summary, [
            'legal_case_update_id' => $summary->legal_case_update_id,
            'version' => $summary->version,
        ], 'Resumo assistido rejeitado e preservado no histórico.');

        return response()->json(['message' => 'Resumo rejeitado e mantido no histórico.', 'redirect' => $this->redirectFor($legalUpdate)]);
    }

    public function publish(LegalUpdateSummary $summary, Request $request): JsonResponse
    {
        $legalUpdate = $this->guardSummary($summary, $request);
        $result = DB::transaction(function () use ($summary, $legalUpdate, $request): array {
            $lockedUpdate = LegalCaseUpdate::query()->whereKey($legalUpdate->id)->lockForUpdate()->firstOrFail();
            $summaries = LegalUpdateSummary::query()
                ->where('legal_case_update_id', $summary->legal_case_update_id)
                ->lockForUpdate()
                ->get();
            $locked = $summaries->firstWhere('id', $summary->id);
            if (! $locked || $locked->status !== 'approved') {
                return ['error' => 'Somente um resumo aprovado pode ser publicado.'];
            }
            if (! $lockedUpdate->is_visible_to_client) {
                return ['error' => 'O andamento original precisa estar explicitamente liberado ao cliente antes do resumo.'];
            }
            if (! hash_equals($locked->source_sha256, hash('sha256', $this->sourceText($lockedUpdate)))) {
                return ['error' => 'O conteúdo original mudou após a geração. Gere e revise uma nova versão do resumo.'];
            }

            LegalUpdateSummary::query()
                ->whereIn('id', $summaries->where('status', 'published')->pluck('id'))
                ->update(['status' => 'approved', 'published_by' => null, 'published_at' => null]);

            $locked->forceFill([
                'status' => 'published',
                'published_by' => $request->user()?->id,
                'published_at' => now(),
            ])->save();

            return ['summary' => $locked];
        });
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 422);
        }
        $summary = $result['summary'];

        activity_log('legal_update_summaries', 'published', $summary, [
            'legal_case_update_id' => $summary->legal_case_update_id,
            'version' => $summary->version,
        ], 'Resumo revisado publicado no portal do cliente.');

        return response()->json(['message' => 'Resumo aprovado publicado no portal.', 'redirect' => $this->redirectFor($legalUpdate)]);
    }

    private function resolveUpdate(string $id, Request $request): LegalCaseUpdate
    {
        return LegalCaseUpdate::query()->visibleTo($request->user())->findOrFail($id);
    }

    private function guardSummary(LegalUpdateSummary $summary, Request $request): LegalCaseUpdate
    {
        return $this->resolveUpdate((string) $summary->legal_case_update_id, $request);
    }

    private function sourceText(LegalCaseUpdate $legalUpdate): string
    {
        $source = trim(strip_tags((string) $legalUpdate->body));

        return mb_substr($source, 0, (int) config('legal_productivity.ai.max_source_characters', 30000));
    }

    private function redirectFor(LegalCaseUpdate $legalUpdate): string
    {
        $legalUpdate->loadMissing('djenPublication:id,legal_case_update_id');

        if ($legalUpdate->djenPublication) {
            return route('admin.djen-publications.show', $legalUpdate->djenPublication);
        }

        return route('admin.legal-cases.workspace', $legalUpdate->legal_case_id);
    }
}
