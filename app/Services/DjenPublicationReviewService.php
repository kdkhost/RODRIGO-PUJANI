<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\DjenMonitor;
use App\Models\DjenPublication;
use App\Models\LegalCase;
use App\Models\LegalCaseUpdate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DjenPublicationReviewService
{
    public function approve(
        DjenPublication $publication,
        User $reviewer,
        ?LegalCase $legalCase = null,
        ?string $notes = null,
    ): DjenPublication {
        return DB::transaction(function () use ($publication, $reviewer, $legalCase, $notes): DjenPublication {
            $locked = DjenPublication::query()->lockForUpdate()->findOrFail($publication->id);
            $case = $legalCase ?? $locked->legalCase;

            if (! $case) {
                throw new RuntimeException('Vincule a publicação a um processo antes de aprová-la.');
            }

            if ($locked->legal_case_id && (int) $locked->legal_case_id !== (int) $case->id) {
                throw new RuntimeException('A publicação já está vinculada a outro processo.');
            }

            $publicationProcess = DjenMonitor::normalizeProcessNumber($locked->process_number_normalized);
            $caseProcess = DjenMonitor::normalizeProcessNumber($case->process_number);
            if (strlen($publicationProcess) === 20 && strlen($caseProcess) === 20 && $publicationProcess !== $caseProcess) {
                throw new RuntimeException('O processo selecionado não corresponde ao processo da publicação.');
            }

            if ($locked->review_status === DjenPublication::STATUS_APPROVED && $locked->legal_case_update_id) {
                return $locked->load('legalCaseUpdate');
            }

            $text = trim((string) $locked->raw_text);
            $type = trim((string) $locked->communication_type);
            $title = 'DJEN: '.($type !== '' ? $type : 'Comunicação processual');
            $body = $text !== ''
                ? '<p>'.nl2br(e($text), false).'</p>'
                : '<p>Comunicação importada do DJEN/CNJ.</p>';

            $update = LegalCaseUpdate::query()->firstOrNew([
                'legal_case_id' => $case->id,
                'external_id' => 'djen:'.$locked->external_key,
            ]);

            $update->fill([
                'client_id' => $case->client_id,
                'created_by' => $update->created_by ?: $reviewer->id,
                'source' => 'djen',
                'update_type' => 'comunicacao',
                'title' => $title,
                'body' => $body,
                'occurred_at' => $locked->availability_date?->startOfDay() ?? now(),
                'is_visible_to_client' => true,
                'metadata' => [
                    'djen_publication_id' => $locked->id,
                    'communication_number' => $locked->communication_number,
                    'tribunal' => $locked->tribunal,
                    'court_body' => $locked->court_body,
                    'document_type' => $locked->document_type,
                    'link' => $locked->source_link,
                    'source_hash' => $locked->source_hash,
                    'content_hash' => $locked->content_hash,
                ],
            ]);
            $update->save();

            $locked->forceFill([
                'legal_case_id' => $case->id,
                'client_id' => $case->client_id,
                'review_status' => DjenPublication::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
                'legal_case_update_id' => $update->id,
            ])->save();

            $this->audit($locked, $reviewer, 'approved', $notes);

            return $locked->refresh()->load(['legalCase', 'legalCaseUpdate', 'reviewer']);
        });
    }

    public function reject(DjenPublication $publication, User $reviewer, string $notes): DjenPublication
    {
        return DB::transaction(function () use ($publication, $reviewer, $notes): DjenPublication {
            $locked = DjenPublication::query()->lockForUpdate()->findOrFail($publication->id);

            if ($locked->review_status === DjenPublication::STATUS_APPROVED) {
                throw new RuntimeException('Reabra a revisão antes de rejeitar uma publicação aprovada.');
            }

            $locked->forceFill([
                'review_status' => DjenPublication::STATUS_REJECTED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ])->save();

            $this->audit($locked, $reviewer, 'rejected', $notes);

            return $locked->refresh();
        });
    }

    public function reopen(DjenPublication $publication, User $reviewer, ?string $notes = null): DjenPublication
    {
        return DB::transaction(function () use ($publication, $reviewer, $notes): DjenPublication {
            $locked = DjenPublication::query()->lockForUpdate()->findOrFail($publication->id);

            if ($locked->legal_case_update_id) {
                LegalCaseUpdate::query()->whereKey($locked->legal_case_update_id)->update([
                    'is_visible_to_client' => false,
                    'updated_at' => now(),
                ]);
            }

            $locked->forceFill([
                'review_status' => DjenPublication::STATUS_PENDING,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_notes' => $notes,
            ])->save();

            $this->audit($locked, $reviewer, 'reopened', $notes);

            return $locked->refresh();
        });
    }

    private function audit(DjenPublication $publication, User $reviewer, string $event, ?string $notes): void
    {
        ActivityLog::query()->create([
            'user_id' => $reviewer->id,
            'module' => 'djen_publications',
            'event' => $event,
            'description' => 'Revisão de publicação do DJEN: '.$event.'.',
            'subject_type' => DjenPublication::class,
            'subject_id' => $publication->id,
            'properties' => [
                'review_status' => $publication->review_status,
                'legal_case_id' => $publication->legal_case_id,
                'content_hash' => $publication->content_hash,
                'notes' => $notes,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
