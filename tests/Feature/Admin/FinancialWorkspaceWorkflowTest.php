<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\FinancialEntry;
use App\Models\LegalCase;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialWorkspaceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
    }

    public function test_financial_crud_normalizes_money_and_records_payment(): void
    {
        $administrator = $this->administrator();
        [$client, $legalCase] = $this->portfolio($administrator, 'CRUD');

        $this->actingAs($administrator)
            ->postJson(route('admin.financial-entries.store'), $this->financialPayload($client, $legalCase, [
                'amount' => 'R$ 1.234,56',
                'installment_count' => 1,
            ]))
            ->assertOk();

        $entry = FinancialEntry::query()->sole();
        $this->assertSame('1234.56', $entry->amount);
        $this->assertSame($administrator->id, $entry->created_by);

        $this->actingAs($administrator)
            ->putJson(route('admin.financial-entries.update', $entry), $this->financialPayload($client, $legalCase, [
                'amount' => '1.234,56',
                'status' => 'paid',
                'installment_count' => null,
            ]))
            ->assertOk();

        $this->assertSame('paid', $entry->refresh()->status);
        $this->assertNotNull($entry->paid_at);

        $this->actingAs($administrator)
            ->deleteJson(route('admin.financial-entries.destroy', $entry))
            ->assertOk();

        $this->assertDatabaseMissing('financial_entries', ['id' => $entry->id]);
    }

    public function test_installments_are_consistent_and_updating_one_does_not_duplicate_the_group(): void
    {
        $administrator = $this->administrator();
        [$client, $legalCase] = $this->portfolio($administrator, 'Parcelas');

        $this->actingAs($administrator)
            ->postJson(route('admin.financial-entries.store'), $this->financialPayload($client, $legalCase, [
                'amount' => '100,00',
                'due_date' => '2026-01-31',
                'installment_count' => 3,
            ]))
            ->assertOk();

        $entries = FinancialEntry::query()->orderBy('installment_number')->get();
        $this->assertCount(3, $entries);
        $this->assertNotNull($entries->first()->installment_group);
        $this->assertCount(1, $entries->pluck('installment_group')->unique());
        $this->assertSame([1, 2, 3], $entries->pluck('installment_number')->all());
        $this->assertSame(['33.33', '33.33', '33.34'], $entries->pluck('amount')->all());
        $this->assertSame(['2026-01-31', '2026-02-28', '2026-03-31'], $entries->map(fn (FinancialEntry $entry): string => $entry->due_date->format('Y-m-d'))->all());
        $this->assertSame('100.00', number_format((float) $entries->sum('amount'), 2, '.', ''));

        $first = $entries->firstOrFail();
        $this->actingAs($administrator)
            ->putJson(route('admin.financial-entries.update', $first), $this->financialPayload($client, $legalCase, [
                'amount' => '33,33',
                'description' => 'Parcela revisada',
                'installment_count' => null,
            ]))
            ->assertOk();

        $this->assertDatabaseCount('financial_entries', 3);
        $this->assertSame('Parcela revisada', $first->refresh()->description);
    }

    public function test_financial_entry_rejects_a_case_owned_by_another_client(): void
    {
        $administrator = $this->administrator();
        [$clientA] = $this->portfolio($administrator, 'Cliente A');
        [, $caseB] = $this->portfolio($administrator, 'Cliente B');

        $this->actingAs($administrator)
            ->postJson(route('admin.financial-entries.store'), $this->financialPayload($clientA, $caseB))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('client_id');

        $this->assertDatabaseCount('financial_entries', 0);
    }

    public function test_financial_records_and_workspaces_are_isolated_between_lawyers(): void
    {
        $lawyerA = $this->editor('Advogada A');
        $lawyerB = $this->editor('Advogada B');
        [$clientA, $caseA] = $this->portfolio($lawyerA, 'Carteira A');
        [$clientB, $caseB] = $this->portfolio($lawyerB, 'Carteira B');
        $entryA = $this->entry($clientA, $caseA, $lawyerA, 'Honorários A');
        $entryB = $this->entry($clientB, $caseB, $lawyerB, 'Honorários B');

        $this->actingAs($lawyerA)
            ->get(route('admin.financial-entries.index'))
            ->assertOk()
            ->assertSee('Honorários A')
            ->assertDontSee('Honorários B');

        $this->actingAs($lawyerA)
            ->getJson(route('admin.financial-entries.edit', $entryB))
            ->assertNotFound();

        $this->actingAs($lawyerA)
            ->get(route('admin.legal-cases.workspace', $caseA))
            ->assertOk()
            ->assertSee('Carteira A');
        $this->actingAs($lawyerA)
            ->get(route('admin.clients.workspace', $clientA))
            ->assertOk()
            ->assertSee('Carteira A');

        $this->actingAs($lawyerA)
            ->get(route('admin.legal-cases.workspace', $caseB))
            ->assertNotFound();
        $this->actingAs($lawyerA)
            ->get(route('admin.clients.workspace', $clientB))
            ->assertNotFound();

        $this->assertTrue(FinancialEntry::query()->visibleTo($lawyerA)->whereKey($entryA)->exists());
        $this->assertFalse(FinancialEntry::query()->visibleTo($lawyerA)->whereKey($entryB)->exists());
    }

    private function administrator(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('Administrador');

        return $user;
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
            'portal_enabled' => true,
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
    private function financialPayload(Client $client, LegalCase $legalCase, array $overrides = []): array
    {
        return array_replace([
            'client_id' => $client->id,
            'legal_case_id' => $legalCase->id,
            'responsible_user_id' => $legalCase->primary_lawyer_id,
            'entry_type' => 'income',
            'category' => 'fees',
            'description' => 'Honorários contratuais',
            'reference' => 'CTR-001',
            'amount' => '300,00',
            'due_date' => '2026-09-10',
            'paid_at' => null,
            'status' => 'pending',
            'payment_method' => 'pix',
            'notes' => null,
            'installment_count' => 1,
        ], $overrides);
    }

    private function entry(Client $client, LegalCase $legalCase, User $lawyer, string $description): FinancialEntry
    {
        return FinancialEntry::query()->create([
            'client_id' => $client->id,
            'legal_case_id' => $legalCase->id,
            'responsible_user_id' => $lawyer->id,
            'created_by' => $lawyer->id,
            'installment_number' => 1,
            'installment_count' => 1,
            'entry_type' => 'income',
            'category' => 'fees',
            'description' => $description,
            'amount' => '100.00',
            'due_date' => '2026-09-10',
            'status' => 'pending',
        ]);
    }
}
