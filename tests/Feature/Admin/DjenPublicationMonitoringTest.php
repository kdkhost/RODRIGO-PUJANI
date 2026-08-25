<?php

namespace Tests\Feature\Admin;

use App\Exceptions\DjenRateLimitException;
use App\Models\Client;
use App\Models\DjenMonitor;
use App\Models\DjenPublication;
use App\Models\DjenSyncRun;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\DjenClient;
use App\Services\DjenPublicationReviewService;
use App\Services\DjenPublicationSyncService;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use RuntimeException;

class DjenPublicationMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_walks_all_official_pages_with_one_hundred_items(): void
    {
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $page = (int) ($query['pagina'] ?? 1);

            return Http::response([
                'status' => 'success',
                'count' => $page === 1 ? 100 : 1,
                'items' => $page === 1
                    ? array_map(fn (int $id): array => ['id' => $id], range(1, 100))
                    : [['id' => 101]],
            ], 200, ['X-RateLimit-Limit' => '120', 'X-RateLimit-Remaining' => '80']);
        });

        $items = app(DjenClient::class)->searchCommunications('10000001020268260100');

        $this->assertCount(101, $items);
        Http::assertSentCount(2);
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['pagina'] ?? null) === '2' && ($query['itensPorPagina'] ?? null) === '100';
        });
    }

    public function test_client_accepts_official_five_item_page_and_sends_oab_with_state(): void
    {
        Http::fakeSequence()
            ->push(['items' => array_fill(0, 5, ['hash' => 'x'])], 200)
            ->push(['items' => []], 200);
        $pages = [];

        app(DjenClient::class)->paginate(
            ['numeroOab' => '123.456', 'ufOab' => 'sp'],
            function (array $items, int $page) use (&$pages): void {
                $pages[] = [$page, count($items)];
            },
            5,
        );

        $this->assertSame([[1, 5]], $pages);
        Http::assertSentCount(2);
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['numeroOab'] ?? null) === '123456'
                && ($query['ufOab'] ?? null) === 'SP'
                && ($query['itensPorPagina'] ?? null) === '5';
        });
    }

    public function test_rate_limit_is_not_retried_and_exposes_safe_retry_time(): void
    {
        Http::fake(['*' => Http::response([], 429, [
            'Retry-After' => '60',
            'X-RateLimit-Limit' => '100',
            'X-RateLimit-Remaining' => '0',
        ])]);

        try {
            app(DjenClient::class)->searchCommunications('10000001020268260100');
            $this->fail('Era esperada uma exceção de limite de taxa.');
        } catch (DjenRateLimitException $exception) {
            $this->assertSame(100, $exception->limit);
            $this->assertSame(0, $exception->remaining);
            $this->assertTrue($exception->retryAt->isFuture());
        }

        Http::assertSentCount(1);
    }

    public function test_sync_records_rate_limit_without_changing_publications(): void
    {
        [$admin, , $monitor] = $this->processMonitor();
        Http::fake(['*' => Http::response([], 429, [
            'Retry-After' => '60',
            'X-RateLimit-Limit' => '100',
            'X-RateLimit-Remaining' => '0',
        ])]);

        $run = app(DjenPublicationSyncService::class)->syncMonitor($monitor, $admin->id);

        $this->assertSame(DjenSyncRun::STATUS_RATE_LIMITED, $run->status);
        $this->assertTrue($run->retry_at->isFuture());
        $this->assertSame(0, $run->items_fetched);
        $this->assertDatabaseCount('djen_publications', 0);
        $this->assertTrue($monitor->refresh()->rate_limited_until->isFuture());
        Http::assertSentCount(1);
    }

    public function test_sync_deduplicates_atomically_preserves_raw_payload_and_never_publishes_before_review(): void
    {
        [$admin, $legalCase, $monitor] = $this->processMonitor();
        Http::fake(['*' => Http::response(['items' => [$this->communication('same-hash')]], 200)]);
        $service = app(DjenPublicationSyncService::class);

        $first = $service->syncMonitor($monitor, $admin->id, 'manual');
        $second = $service->syncMonitor($monitor->refresh(), $admin->id, 'manual');

        $this->assertSame(DjenSyncRun::STATUS_SUCCEEDED, $first->status);
        $this->assertSame(1, $first->items_created);
        $this->assertSame(0, $second->items_created);
        $this->assertDatabaseCount('djen_publications', 1);
        $this->assertDatabaseCount('djen_monitor_publication', 1);
        $this->assertDatabaseCount('legal_case_updates', 0);
        $publication = DjenPublication::query()->firstOrFail();
        $this->assertSame(DjenPublication::STATUS_PENDING, $publication->review_status);
        $this->assertSame('<script>alert(1)</script> Prazo iniciado.', $publication->raw_payload['texto']);
        $this->assertSame($legalCase->id, $publication->legal_case_id);
    }

    public function test_oab_monitor_links_publication_to_the_matching_process(): void
    {
        $admin = $this->admin();
        $legalCase = $this->legalCase();
        $monitor = DjenMonitor::query()->create([
            'created_by' => $admin->id,
            'type' => DjenMonitor::TYPE_OAB,
            'label' => 'OAB 123456/SP',
            'oab_number_normalized' => '123456',
            'oab_state' => 'SP',
            'fingerprint' => DjenMonitor::fingerprintFor(DjenMonitor::TYPE_OAB, null, '123456', 'SP'),
            'enabled' => true,
            'sync_interval_minutes' => 60,
            'lookback_days' => 30,
            'overlap_days' => 2,
        ]);
        Http::fake(['*' => Http::response(['items' => [$this->communication('oab-hash')]], 200)]);

        app(DjenPublicationSyncService::class)->syncMonitor($monitor, $admin->id);

        $this->assertDatabaseHas('djen_publications', ['legal_case_id' => $legalCase->id, 'client_id' => $legalCase->client_id]);
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['numeroOab'] ?? null) === '123456' && ($query['ufOab'] ?? null) === 'SP';
        });
    }

    public function test_second_page_failure_keeps_first_page_and_marks_run_partial(): void
    {
        [$admin, , $monitor] = $this->processMonitor();
        $items = array_map(fn (int $id): array => $this->communication('partial-'.$id, $id), range(1, 100));
        $requests = 0;
        Http::fake(function (Request $request) use ($items, &$requests) {
            $requests++;
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return (int) ($query['pagina'] ?? 1) === 1
                ? Http::response(['items' => $items], 200)
                : Http::response([], 500);
        });

        $run = app(DjenPublicationSyncService::class)->syncMonitor($monitor, $admin->id);

        $this->assertSame(DjenSyncRun::STATUS_PARTIAL, $run->status);
        $this->assertSame(1, $run->pages_processed);
        $this->assertSame(100, $run->items_fetched);
        $this->assertDatabaseCount('djen_publications', 100);
        $this->assertSame(4, $requests, 'A segunda página deve respeitar apenas as três tentativas seguras para erro 5xx.');
    }

    public function test_review_is_idempotent_sanitized_and_reopen_hides_the_portal_update(): void
    {
        [$admin, $legalCase, $monitor] = $this->processMonitor();
        Http::fake(['*' => Http::response(['items' => [$this->communication('review-hash')]], 200)]);
        app(DjenPublicationSyncService::class)->syncMonitor($monitor, $admin->id);
        $publication = DjenPublication::query()->firstOrFail();
        $service = app(DjenPublicationReviewService::class);

        $approved = $service->approve($publication, $admin, $legalCase, 'Conferida.');
        $service->approve($approved, $admin, $legalCase, 'Conferida novamente.');

        $this->assertDatabaseCount('legal_case_updates', 1);
        $update = $approved->legalCaseUpdate()->firstOrFail();
        $this->assertTrue($update->is_visible_to_client);
        $this->assertStringNotContainsString('<script>', $update->body);
        $this->assertSame(DjenPublication::STATUS_APPROVED, $approved->review_status);

        $service->reopen($approved, $admin, 'Nova conferência necessária.');
        $this->assertFalse($update->refresh()->is_visible_to_client);
        $this->assertSame(DjenPublication::STATUS_PENDING, $approved->refresh()->review_status);
    }

    public function test_review_rejects_a_case_with_a_different_process_number(): void
    {
        [$admin, $legalCase, $monitor] = $this->processMonitor();
        $otherCase = $this->legalCase($admin, '20000002020268260100');
        Http::fake(['*' => Http::response(['items' => [$this->communication('binding-hash')]], 200)]);
        app(DjenPublicationSyncService::class)->syncMonitor($monitor, $admin->id);
        $publication = DjenPublication::query()->firstOrFail();
        $publication->forceFill(['legal_case_id' => null, 'client_id' => null])->save();

        try {
            app(DjenPublicationReviewService::class)->approve($publication, $admin, $otherCase);
            $this->fail('Era esperada a rejeição de um processo incompatível.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('não corresponde', $exception->getMessage());
        }

        $this->assertDatabaseHas('djen_publications', [
            'id' => $publication->id,
            'legal_case_id' => null,
            'review_status' => DjenPublication::STATUS_PENDING,
        ]);
    }

    public function test_publication_scope_isolates_associated_lawyers_and_rejection_creates_no_client_update(): void
    {
        $this->seed(PermissionsSeeder::class);
        Permission::findOrCreate('djen-publications.review', 'web');
        $lawyerA = User::factory()->create(['is_active' => true]);
        $lawyerB = User::factory()->create(['is_active' => true]);
        $lawyerA->assignRole('Advogado Associado');
        $lawyerB->assignRole('Advogado Associado');
        $lawyerA->givePermissionTo('djen-publications.review');
        $lawyerB->givePermissionTo('djen-publications.review');
        $caseA = $this->legalCase($lawyerA);
        $caseB = $this->legalCase($lawyerB, '20000002020268260100');
        $publicationA = $this->publication($caseA, 'scope-a');
        $publicationB = $this->publication($caseB, 'scope-b');

        $this->assertTrue(DjenPublication::query()->visibleTo($lawyerA)->whereKey($publicationA)->exists());
        $this->assertFalse(DjenPublication::query()->visibleTo($lawyerA)->whereKey($publicationB)->exists());

        app(DjenPublicationReviewService::class)->reject($publicationA, $lawyerA, 'Publicação sem relação com o caso.');
        $this->assertSame(DjenPublication::STATUS_REJECTED, $publicationA->refresh()->review_status);
        $this->assertDatabaseCount('legal_case_updates', 0);
    }

    /** @return array{User,LegalCase,DjenMonitor} */
    private function processMonitor(): array
    {
        $admin = $this->admin();
        $legalCase = $this->legalCase();
        $monitor = DjenMonitor::query()->create([
            'legal_case_id' => $legalCase->id,
            'created_by' => $admin->id,
            'type' => DjenMonitor::TYPE_PROCESS,
            'label' => 'Processo teste',
            'process_number_normalized' => '10000001020268260100',
            'fingerprint' => DjenMonitor::fingerprintFor(DjenMonitor::TYPE_PROCESS, '10000001020268260100'),
            'enabled' => true,
            'sync_interval_minutes' => 60,
            'lookback_days' => 30,
            'overlap_days' => 2,
        ]);

        return [$admin, $legalCase, $monitor];
    }

    private function admin(): User
    {
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');

        return $admin;
    }

    private function legalCase(?User $lawyer = null, string $process = '10000001020268260100'): LegalCase
    {
        $client = Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente '.$process,
            'assigned_lawyer_id' => $lawyer?->id,
            'is_active' => true,
        ]);

        return LegalCase::query()->create([
            'client_id' => $client->id,
            'primary_lawyer_id' => $lawyer?->id,
            'title' => 'Processo '.$process,
            'process_number' => $process,
            'status' => 'active',
            'phase' => 'initial',
            'priority' => 'medium',
            'portal_visible' => true,
            'is_confidential' => true,
            'is_active' => true,
        ]);
    }

    /** @return array<string, mixed> */
    private function communication(string $hash, int $number = 123): array
    {
        return [
            'hash' => $hash,
            'numeroComunicacao' => $number,
            'numero_processo' => '10000001020268260100',
            'data_disponibilizacao' => '2026-08-20',
            'siglaTribunal' => 'TJSP',
            'tipoComunicacao' => 'Intimação',
            'nomeOrgao' => '1ª Vara',
            'tipoDocumento' => 'Intimação',
            'texto' => '<script>alert(1)</script> Prazo iniciado.',
            'link' => 'https://comunica.pje.jus.br/consulta/'.$hash,
        ];
    }

    private function publication(LegalCase $legalCase, string $key): DjenPublication
    {
        return DjenPublication::query()->create([
            'legal_case_id' => $legalCase->id,
            'client_id' => $legalCase->client_id,
            'external_key' => hash('sha256', $key),
            'process_number_normalized' => DjenMonitor::normalizeProcessNumber($legalCase->process_number),
            'raw_text' => 'Conteúdo de teste.',
            'raw_payload' => ['key' => $key],
            'content_hash' => hash('sha256', 'content-'.$key),
            'review_status' => DjenPublication::STATUS_PENDING,
            'discovered_at' => now(),
            'last_seen_at' => now(),
        ]);
    }
}
