<?php

namespace App\Jobs;

use App\Models\HearingTranscription;
use App\Services\HearingAudioStorage;
use App\Services\LegalProductivityProviderManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProcessHearingTranscription implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 900;
    public int $uniqueFor = 3600;

    public function __construct(public readonly int $transcriptionId)
    {
        $this->onQueue('legal-productivity');
    }

    public function uniqueId(): string
    {
        return (string) $this->transcriptionId;
    }

    public function handle(HearingAudioStorage $storage, LegalProductivityProviderManager $manager): void
    {
        $record = HearingTranscription::query()->find($this->transcriptionId);
        if (! $record || in_array($record->status, ['pending_review', 'approved'], true)) {
            return;
        }

        $record->forceFill(['status' => 'processing', 'processing_error' => null])->save();

        try {
            $absolutePath = $storage->absolutePath($record->disk, $record->path);
            if (! hash_equals($record->sha256, (string) hash_file('sha256', $absolutePath))) {
                throw new RuntimeException('A integridade do áudio não pôde ser confirmada.');
            }

            $provider = $manager->provider();
            $transcriptResult = filled($record->transcript_original)
                ? [
                    'text' => $record->transcript_original,
                    'provider' => $record->provider ?: 'configured',
                    'model' => data_get($record->metadata, 'transcription_model'),
                    'reference' => $record->provider_reference,
                    'metadata' => [],
                ]
                : $provider->transcribe($absolutePath, $record->mime_type);
            $minutesResult = $provider->draftMinutes((string) $transcriptResult['text']);

            $metadata = is_array($record->metadata) ? $record->metadata : [];
            $metadata['transcription_model'] = $transcriptResult['model'] ?? null;
            $metadata['minutes_model'] = $minutesResult['model'] ?? null;

            $record->forceFill([
                'status' => 'pending_review',
                'provider' => $transcriptResult['provider'] ?? null,
                'provider_reference' => $transcriptResult['reference'] ?? null,
                'transcript_original' => $record->transcript_original ?: $transcriptResult['text'],
                'transcript_edited' => $record->transcript_edited ?: $transcriptResult['text'],
                'minutes_draft' => $record->minutes_draft ?: $minutesResult['text'],
                'review_status' => 'draft',
                'processing_error' => null,
                'metadata' => $metadata,
            ])->save();

            activity_log('hearing_transcriptions', 'processed', $record, [
                'status' => $record->status,
                'provider' => $record->provider,
            ], 'Transcrição de audiência processada e mantida para revisão humana.');
        } catch (Throwable $exception) {
            $record->forceFill([
                'status' => 'failed',
                'processing_error' => Str::limit($exception->getMessage(), 1000, ''),
            ])->save();

            throw $exception;
        }
    }
}
