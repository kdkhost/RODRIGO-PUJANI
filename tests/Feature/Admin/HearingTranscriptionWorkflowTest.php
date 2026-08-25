<?php

namespace Tests\Feature\Admin;

use App\Contracts\LegalProductivityProviderInterface;
use App\Jobs\ProcessHearingTranscription;
use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\HearingTranscription;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\HearingAudioStorage;
use App\Services\LegalProductivityProviderManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class HearingTranscriptionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        Storage::fake('hearing_audio');
        Queue::fake();
    }

    public function test_audio_upload_is_private_and_waits_for_provider_configuration(): void
    {
        $lawyer = $this->lawyer('Operadora');
        [$client, $legalCase] = $this->portfolio($lawyer, 'Audiência');

        $this->actingAs($lawyer)
            ->postJson(route('admin.hearing-transcriptions.store'), $this->uploadPayload($client, $legalCase))
            ->assertOk();

        $record = HearingTranscription::query()->sole();
        $this->assertSame('configuration_required', $record->status);
        $this->assertSame('hearing_audio', $record->disk);
        $this->assertSame('wav', $record->extension);
        $this->assertSame(hash('sha256', $this->wav()), $record->sha256);
        $this->assertStringNotContainsString('audiencia-controle', $record->path);
        Storage::disk('hearing_audio')->assertExists($record->path);
        Queue::assertNothingPushed();
    }

    public function test_upload_rejects_invalid_mime_signature_size_and_duration(): void
    {
        $lawyer = $this->lawyer('Validadora');
        [$client, $legalCase] = $this->portfolio($lawyer, 'Limites');

        $invalid = $this->uploadPayload($client, $legalCase, [
            'audio' => UploadedFile::fake()->createWithContent('falso.mp3', 'conteúdo que não é áudio'),
        ]);
        $this->actingAs($lawyer)
            ->postJson(route('admin.hearing-transcriptions.store'), $invalid)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('audio');

        config()->set('legal_productivity.hearing_audio.max_size_kb', 1);
        $tooLarge = $this->uploadPayload($client, $legalCase, [
            'audio' => UploadedFile::fake()->createWithContent('grande.wav', $this->wav(3000)),
        ]);
        $this->actingAs($lawyer)
            ->postJson(route('admin.hearing-transcriptions.store'), $tooLarge)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('audio');

        config()->set('legal_productivity.hearing_audio.max_size_kb', 262144);
        config()->set('legal_productivity.hearing_audio.max_duration_seconds', 10);
        $tooLong = $this->uploadPayload($client, $legalCase, ['duration_seconds' => 11]);
        $this->actingAs($lawyer)
            ->postJson(route('admin.hearing-transcriptions.store'), $tooLong)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('duration_seconds');

        $this->assertDatabaseCount('hearing_transcriptions', 0);
        Storage::disk('hearing_audio')->assertDirectoryEmpty('/');
    }

    public function test_upload_rejects_mismatched_client_case_and_foreign_calendar_event_ids(): void
    {
        $lawyerA = $this->lawyer('Advogada A');
        $lawyerB = $this->lawyer('Advogada B');
        [$clientA, $caseA] = $this->portfolio($lawyerA, 'A');
        [$clientB, $caseB] = $this->portfolio($lawyerB, 'B');
        $eventB = CalendarEvent::query()->create([
            'client_id' => $clientB->id,
            'legal_case_id' => $caseB->id,
            'title' => 'Audiência privada B',
            'category' => 'Audiência',
            'event_type' => 'hearing',
            'status' => 'scheduled',
            'visibility' => 'private',
            'start_at' => now()->addDay(),
            'owner_id' => $lawyerB->id,
            'created_by' => $lawyerB->id,
        ]);

        $this->actingAs($lawyerA)
            ->postJson(route('admin.hearing-transcriptions.store'), $this->uploadPayload($clientB, $caseA))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('client_id');

        $this->actingAs($lawyerA)
            ->postJson(route('admin.hearing-transcriptions.store'), $this->uploadPayload($clientA, $caseA, [
                'calendar_event_id' => $eventB->id,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('calendar_event_id');

        $this->assertDatabaseCount('hearing_transcriptions', 0);
    }

    public function test_private_audio_download_is_authorized_and_isolated_between_lawyers(): void
    {
        $lawyerA = $this->lawyer('Advogada A');
        $lawyerB = $this->lawyer('Advogada B');
        [$clientA, $caseA] = $this->portfolio($lawyerA, 'Download A');
        $record = $this->record($clientA, $caseA, $lawyerA, 'private/audiencia-a.wav');

        $download = $this->actingAs($lawyerA)
            ->get(route('admin.hearing-transcriptions.download', $record));

        $download
            ->assertOk()
            ->assertDownload('audiencia-controle.wav');
        $this->assertStringContainsString('private', (string) $download->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $download->headers->get('Cache-Control'));

        $this->actingAs($lawyerB)
            ->get(route('admin.hearing-transcriptions.download', $record))
            ->assertForbidden();
    }

    public function test_processing_job_uses_provider_mock_and_keeps_transcript_and_minutes_as_drafts(): void
    {
        $lawyer = $this->lawyer('Revisora');
        [$client, $legalCase] = $this->portfolio($lawyer, 'Processamento');
        $record = $this->record($client, $legalCase, $lawyer, 'private/processar.wav');
        $manager = $this->providerManager(
            transcript: 'Transcrição original e imutável da audiência.',
            minutes: 'Minuta de ata gerada para revisão humana.',
        );

        (new ProcessHearingTranscription($record->id))->handle(app(HearingAudioStorage::class), $manager);

        $record->refresh();
        $this->assertSame('pending_review', $record->status);
        $this->assertSame('draft', $record->review_status);
        $this->assertSame('Transcrição original e imutável da audiência.', $record->transcript_original);
        $this->assertSame($record->transcript_original, $record->transcript_edited);
        $this->assertSame('Minuta de ata gerada para revisão humana.', $record->minutes_draft);
        $this->assertNull($record->approved_at);
        $this->assertSame('fake-transcription', data_get($record->metadata, 'transcription_model'));
        $this->assertSame('fake-minutes', data_get($record->metadata, 'minutes_model'));
    }

    public function test_processing_job_refuses_an_audio_with_a_divergent_hash(): void
    {
        $lawyer = $this->lawyer('Auditora');
        [$client, $legalCase] = $this->portfolio($lawyer, 'Integridade');
        $record = $this->record($client, $legalCase, $lawyer, 'private/integridade.wav');
        Storage::disk('hearing_audio')->put($record->path, $this->wav(1000));
        $manager = $this->providerManager('Não deve transcrever.', 'Não deve gerar ata.');

        try {
            (new ProcessHearingTranscription($record->id))->handle(app(HearingAudioStorage::class), $manager);
            $this->fail('Era esperada uma falha de integridade do áudio.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('integridade', mb_strtolower($exception->getMessage()));
        }

        $this->assertSame('failed', $record->refresh()->status);
        $this->assertNotNull($record->processing_error);
    }

    public function test_minutes_require_review_before_human_approval(): void
    {
        $lawyer = $this->editor('Aprovadora');
        [$client, $legalCase] = $this->portfolio($lawyer, 'Aprovação');
        $record = $this->record($client, $legalCase, $lawyer, 'private/aprovar.wav');
        $record->forceFill([
            'status' => 'pending_review',
            'transcript_original' => 'Texto original preservado.',
            'transcript_edited' => 'Texto original preservado.',
            'minutes_draft' => 'Minuta inicial.',
            'review_status' => 'draft',
        ])->save();

        $this->actingAs($lawyer)
            ->postJson(route('admin.hearing-transcriptions.approve', $record))
            ->assertUnprocessable();

        $this->actingAs($lawyer)
            ->putJson(route('admin.hearing-transcriptions.update', $record), [
                'transcript_edited' => 'Transcrição conferida por pessoa.',
                'minutes_draft' => 'Ata conferida por pessoa.',
            ])
            ->assertOk();
        $this->assertSame('reviewed', $record->refresh()->review_status);

        $this->actingAs($lawyer)
            ->postJson(route('admin.hearing-transcriptions.approve', $record))
            ->assertOk();

        $this->assertSame('approved', $record->refresh()->review_status);
        $this->assertSame($lawyer->id, $record->approved_by);
        $this->assertNotNull($record->approved_at);
        $this->assertSame('Texto original preservado.', $record->transcript_original);
    }

    public function test_transcription_operator_cannot_bypass_the_approval_permission(): void
    {
        $operator = $this->lawyer('Operadora sem alçada');
        [$client, $legalCase] = $this->portfolio($operator, 'Alçada de ata');
        $record = $this->record($client, $legalCase, $operator, 'private/alcada.wav');
        $record->forceFill([
            'status' => 'pending_review',
            'transcript_original' => 'Transcrição original.',
            'transcript_edited' => 'Transcrição conferida.',
            'minutes_draft' => 'Ata conferida.',
            'review_status' => 'reviewed',
            'reviewed_by' => $operator->id,
            'reviewed_at' => now(),
        ])->save();

        $this->actingAs($operator)
            ->postJson(route('admin.hearing-transcriptions.approve', $record))
            ->assertForbidden();

        $this->assertSame('reviewed', $record->refresh()->review_status);
        $this->assertNull($record->approved_at);
    }

    private function lawyer(string $name): User
    {
        $user = User::factory()->create(['name' => $name, 'is_active' => true]);
        $user->assignRole('Advogado Associado');

        return $user;
    }

    private function editor(string $name): User
    {
        $user = User::factory()->create(['name' => $name, 'is_active' => true]);
        $user->assignRole('Editor');

        return $user;
    }

    /** @return array{Client, LegalCase} */
    private function portfolio(User $lawyer, string $suffix): array
    {
        $client = Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente '.$suffix,
            'assigned_lawyer_id' => $lawyer->id,
            'created_by' => $lawyer->id,
            'is_active' => true,
        ]);
        $legalCase = LegalCase::query()->create([
            'client_id' => $client->id,
            'primary_lawyer_id' => $lawyer->id,
            'created_by' => $lawyer->id,
            'title' => 'Processo '.$suffix,
            'status' => 'active',
            'phase' => 'initial',
            'priority' => 'medium',
            'is_active' => true,
            'portal_visible' => true,
        ]);

        return [$client, $legalCase];
    }

    /** @param array<string, mixed> $overrides */
    private function uploadPayload(Client $client, LegalCase $legalCase, array $overrides = []): array
    {
        return array_replace([
            'title' => 'Audiência de controle',
            'client_id' => $client->id,
            'legal_case_id' => $legalCase->id,
            'calendar_event_id' => null,
            'duration_seconds' => 2,
            'audio' => UploadedFile::fake()->createWithContent('audiencia-controle.wav', $this->wav()),
            'recording_legal_notice' => '1',
        ], $overrides);
    }

    private function record(Client $client, LegalCase $legalCase, User $lawyer, string $path): HearingTranscription
    {
        $contents = $this->wav();
        Storage::disk('hearing_audio')->put($path, $contents);

        return HearingTranscription::query()->create([
            'client_id' => $client->id,
            'legal_case_id' => $legalCase->id,
            'uploaded_by' => $lawyer->id,
            'title' => 'Audiência de controle',
            'original_name' => 'audiencia-controle.wav',
            'disk' => 'hearing_audio',
            'path' => $path,
            'mime_type' => 'audio/x-wav',
            'extension' => 'wav',
            'size' => strlen($contents),
            'sha256' => hash('sha256', $contents),
            'duration_seconds' => 2,
            'status' => 'uploaded',
            'review_status' => 'not_reviewed',
        ]);
    }

    private function providerManager(string $transcript, string $minutes): LegalProductivityProviderManager
    {
        $provider = new class($transcript, $minutes) implements LegalProductivityProviderInterface
        {
            public function __construct(
                private readonly string $transcript,
                private readonly string $minutes,
            ) {}

            public function summarize(string $source): array
            {
                throw new \LogicException('Não utilizado neste teste.');
            }

            public function transcribe(string $absolutePath, string $mimeType): array
            {
                return [
                    'text' => $this->transcript,
                    'provider' => 'fake',
                    'model' => 'fake-transcription',
                    'reference' => 'request-test',
                    'metadata' => [],
                ];
            }

            public function draftMinutes(string $transcript): array
            {
                return [
                    'text' => $this->minutes,
                    'provider' => 'fake',
                    'model' => 'fake-minutes',
                    'metadata' => [],
                ];
            }
        };

        return new class($provider) extends LegalProductivityProviderManager
        {
            public function __construct(private readonly LegalProductivityProviderInterface $fakeProvider) {}

            public function provider(): LegalProductivityProviderInterface
            {
                return $this->fakeProvider;
            }
        };
    }

    private function wav(int $sampleBytes = 1600): string
    {
        $samples = str_repeat("\0", $sampleBytes);

        return 'RIFF'
            .pack('V', 36 + strlen($samples))
            .'WAVEfmt '
            .pack('VvvVVvv', 16, 1, 1, 8000, 8000, 1, 8)
            .'data'
            .pack('V', strlen($samples))
            .$samples;
    }
}
