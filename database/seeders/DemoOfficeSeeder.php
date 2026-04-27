<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\ContactMessage;
use App\Models\LegalCase;
use App\Models\LegalCaseUpdate;
use App\Models\LegalDocument;
use App\Models\LegalTask;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class DemoOfficeSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::query()->firstWhere('email', env('APP_ADMIN_EMAIL', 'admin@pujani.adv.br'))
            ?? User::query()->first();

        $administrator = User::query()->updateOrCreate(
            ['email' => 'gestor.demo@pujani.adv.br'],
            [
                'name' => 'Gestor Demonstracao',
                'phone' => '(11) 98765-4001',
                'document_number' => '215.334.890-41',
                'whatsapp' => '(11) 99876-4001',
                'address_zip' => '01414-001',
                'address_street' => 'Rua Augusta',
                'address_number' => '2150',
                'address_complement' => 'Sala 501',
                'address_district' => 'Jardins',
                'address_city' => 'Sao Paulo',
                'address_state' => 'SP',
                'timezone' => 'America/Sao_Paulo',
                'is_active' => true,
                'password' => Hash::make('Admin@12345'),
            ],
        );
        $administrator->syncRoles(['Administrador']);

        $associatedLawyer = User::query()->updateOrCreate(
            ['email' => 'associado.demo@pujani.adv.br'],
            [
                'name' => 'Advogado Associado Demo',
                'phone' => '(11) 98765-4002',
                'document_number' => '318.556.120-09',
                'whatsapp' => '(11) 99876-4002',
                'address_zip' => '01311-200',
                'address_street' => 'Alameda Santos',
                'address_number' => '1441',
                'address_complement' => 'Conjunto 1204',
                'address_district' => 'Cerqueira Cesar',
                'address_city' => 'Sao Paulo',
                'address_state' => 'SP',
                'timezone' => 'America/Sao_Paulo',
                'is_active' => true,
                'password' => Hash::make('Associado@123'),
            ],
        );
        $associatedLawyer->syncRoles(['Advogado Associado']);

        $clientA = Client::query()->updateOrCreate(
            ['document_number' => '123.456.789-09'],
            [
                'person_type' => 'individual',
                'name' => 'Helena Martins',
                'email' => 'helena.martins@cliente.demo',
                'phone' => '(11) 3123-4401',
                'whatsapp' => '(11) 99876-4401',
                'profession' => 'Empresaria',
                'address_zip' => '01310-200',
                'address_street' => 'Avenida Paulista',
                'address_number' => '1842',
                'address_complement' => 'Conjunto 2101',
                'address_district' => 'Bela Vista',
                'address_city' => 'Sao Paulo',
                'address_state' => 'SP',
                'notes' => '<p>Cliente de exemplo com portal ativo e caso consultivo recorrente.</p>',
                'assigned_lawyer_id' => $associatedLawyer->id,
                'created_by' => $superAdmin?->id,
                'is_active' => true,
                'portal_enabled' => true,
                'portal_access_code' => Hash::make('CLT12345'),
                'portal_access_code_updated_at' => now(),
            ],
        );

        $clientB = Client::query()->updateOrCreate(
            ['document_number' => '45.678.912/0001-55'],
            [
                'person_type' => 'company',
                'name' => 'Martins Tecnologia Ltda',
                'trade_name' => 'Martins Tech',
                'email' => 'juridico@martinstech.demo',
                'phone' => '(11) 3333-5500',
                'whatsapp' => '(11) 99876-5500',
                'address_zip' => '04538-132',
                'address_street' => 'Rua Funchal',
                'address_number' => '418',
                'address_district' => 'Vila Olimpia',
                'address_city' => 'Sao Paulo',
                'address_state' => 'SP',
                'notes' => '<p>Conta empresarial de demonstracao para contratos, societario e tributario.</p>',
                'assigned_lawyer_id' => $administrator->id,
                'created_by' => $superAdmin?->id,
                'is_active' => true,
                'portal_enabled' => false,
            ],
        );

        $caseA = LegalCase::query()->updateOrCreate(
            ['internal_code' => 'PJ-2026-001'],
            [
                'client_id' => $clientA->id,
                'primary_lawyer_id' => $associatedLawyer->id,
                'supervising_lawyer_id' => $superAdmin?->id,
                'title' => 'Revisao contratual e estrategia de recuperacao',
                'process_number' => '5001234-56.2026.8.26.0100',
                'practice_area' => 'Direito Civil',
                'counterparty' => 'Instituicao Financeira Exemplo S/A',
                'court_name' => 'Tribunal de Justica de Sao Paulo',
                'court_division' => '12a Vara Civel',
                'court_city' => 'Sao Paulo',
                'court_state' => 'SP',
                'status' => 'active',
                'phase' => 'instruction',
                'priority' => 'high',
                'filing_date' => now()->subMonths(3)->toDateString(),
                'next_hearing_at' => now()->addDays(9)->setTime(14, 30),
                'next_deadline_at' => now()->addDays(5)->setTime(18, 0),
                'claim_amount' => 185000.00,
                'contract_value' => 24000.00,
                'success_fee_percent' => 15.0,
                'summary' => '<p>Caso de exemplo com cronograma de audiencias, prazos e documentos compartilhados no portal.</p>',
                'strategy_notes' => '<p>Priorizar acordo qualificado, manter reserva documental e acompanhar atualizacao de pericia.</p>',
                'is_confidential' => false,
                'is_active' => true,
                'portal_visible' => true,
                'portal_summary' => '<p>Seu processo segue ativo com audiencia ja designada e prazo de memoriais em acompanhamento.</p>',
                'tribunal_alias' => 'tjsp',
                'datajud_sync_enabled' => false,
                'created_by' => $superAdmin?->id,
            ],
        );

        $caseB = LegalCase::query()->updateOrCreate(
            ['internal_code' => 'PJ-2026-002'],
            [
                'client_id' => $clientB->id,
                'primary_lawyer_id' => $administrator->id,
                'supervising_lawyer_id' => $superAdmin?->id,
                'title' => 'Planejamento tributario e defesa administrativa',
                'process_number' => '1023344-21.2026.8.26.0053',
                'practice_area' => 'Direito Tributario',
                'counterparty' => 'Fazenda do Estado de Sao Paulo',
                'court_name' => 'Tribunal de Justica de Sao Paulo',
                'court_division' => 'Vara da Fazenda Publica',
                'court_city' => 'Sao Paulo',
                'court_state' => 'SP',
                'status' => 'active',
                'phase' => 'analysis',
                'priority' => 'medium',
                'filing_date' => now()->subMonths(1)->toDateString(),
                'next_deadline_at' => now()->addDays(12)->setTime(17, 0),
                'claim_amount' => 420000.00,
                'contract_value' => 38000.00,
                'success_fee_percent' => 12.5,
                'summary' => '<p>Projeto de exemplo com foco em contingencia fiscal e recuperacao de creditos.</p>',
                'strategy_notes' => '<p>Validar base de calculo, revisar autos e consolidar historico de documentos fiscais.</p>',
                'is_confidential' => true,
                'is_active' => true,
                'portal_visible' => false,
                'portal_summary' => '',
                'tribunal_alias' => 'tjsp',
                'datajud_sync_enabled' => false,
                'created_by' => $superAdmin?->id,
            ],
        );

        LegalTask::query()->updateOrCreate(
            ['title' => 'Preparar memoriais da audiencia', 'legal_case_id' => $caseA->id],
            [
                'client_id' => $clientA->id,
                'assigned_user_id' => $associatedLawyer->id,
                'task_type' => 'deadline',
                'priority' => 'high',
                'status' => 'in_progress',
                'start_at' => now()->subDay()->setTime(9, 0),
                'due_at' => now()->addDays(5)->setTime(18, 0),
                'reminder_minutes' => 120,
                'billable_minutes' => 180,
                'location' => 'Escritorio',
                'description' => '<p>Consolidar documentos do cliente, revisar tese principal e alinhar sustentacao.</p>',
                'result_notes' => '',
                'created_by' => $superAdmin?->id,
            ],
        );

        LegalTask::query()->updateOrCreate(
            ['title' => 'Conferencia de documentos fiscais', 'legal_case_id' => $caseB->id],
            [
                'client_id' => $clientB->id,
                'assigned_user_id' => $administrator->id,
                'task_type' => 'analysis',
                'priority' => 'medium',
                'status' => 'pending',
                'start_at' => now()->addDay()->setTime(10, 0),
                'due_at' => now()->addDays(4)->setTime(16, 0),
                'reminder_minutes' => 90,
                'billable_minutes' => 120,
                'location' => 'Remoto',
                'description' => '<p>Revisar notas, autos de infracao e planilha de creditos aproveitaveis.</p>',
                'result_notes' => '',
                'created_by' => $superAdmin?->id,
            ],
        );

        LegalCaseUpdate::query()->updateOrCreate(
            ['legal_case_id' => $caseA->id, 'title' => 'Audiencia redesignada'],
            [
                'client_id' => $clientA->id,
                'created_by' => $associatedLawyer->id,
                'external_id' => 'demo-update-case-a-1',
                'source' => 'manual',
                'update_type' => 'hearing',
                'body' => '<p>A audiencia foi redesignada para a proxima semana, mantendo-se a necessidade de apresentacao de memoriais.</p>',
                'occurred_at' => now()->subDay()->setTime(16, 0),
                'is_visible_to_client' => true,
                'metadata' => ['origin' => 'demo'],
            ],
        );

        LegalCaseUpdate::query()->updateOrCreate(
            ['legal_case_id' => $caseB->id, 'title' => 'Recebimento de notificacao fiscal'],
            [
                'client_id' => $clientB->id,
                'created_by' => $administrator->id,
                'external_id' => 'demo-update-case-b-1',
                'source' => 'manual',
                'update_type' => 'notice',
                'body' => '<p>Notificacao recebida e em analise pela equipe tributaria para resposta administrativa.</p>',
                'occurred_at' => now()->subHours(6),
                'is_visible_to_client' => false,
                'metadata' => ['origin' => 'demo'],
            ],
        );

        $documentPath = $this->createDemoDocument(
            'uploads/demo-documents',
            'resumo-processo-helena.txt',
            "Resumo do processo de demonstracao\nCliente: Helena Martins\nPrazo relevante: ".now()->addDays(5)->format('d/m/Y H:i')
        );

        LegalDocument::query()->updateOrCreate(
            ['legal_case_id' => $caseA->id, 'title' => 'Resumo do processo'],
            [
                'client_id' => $clientA->id,
                'uploaded_by' => $associatedLawyer->id,
                'category' => 'Relatorio',
                'original_name' => 'resumo-processo-helena.txt',
                'file_name' => 'resumo-processo-helena.txt',
                'path' => $documentPath,
                'mime_type' => 'text/plain',
                'extension' => 'txt',
                'size' => filesize(public_path($documentPath)) ?: 0,
                'notes' => '<p>Arquivo simples de demonstracao para exibir o fluxo de download no portal do cliente.</p>',
                'is_sensitive' => false,
                'shared_with_client' => true,
            ],
        );

        CalendarEvent::query()->updateOrCreate(
            ['title' => 'Audiencia de conciliacao demo', 'start_at' => now()->addDays(9)->setTime(14, 30)],
            [
                'description' => '<p>Evento de exemplo integrado ao caso da cliente Helena Martins.</p>',
                'location' => 'Forum Central - Sala 12',
                'category' => 'Audiencia',
                'status' => 'confirmed',
                'visibility' => 'team',
                'color' => '#198754',
                'text_color' => '#ffffff',
                'end_at' => now()->addDays(9)->setTime(15, 30),
                'all_day' => false,
                'editable' => true,
                'overlap' => true,
                'display' => 'auto',
                'extended_props' => ['case' => $caseA->internal_code],
                'owner_id' => $associatedLawyer->id,
                'created_by' => $superAdmin?->id,
            ],
        );

        CalendarEvent::query()->updateOrCreate(
            ['title' => 'Bloqueio interno de revisao fiscal', 'start_at' => now()->addDays(4)->startOfDay()],
            [
                'description' => '<p>Reserva interna para revisao da defesa administrativa do cliente empresarial.</p>',
                'location' => 'Backoffice tributario',
                'category' => 'Operacao',
                'status' => 'scheduled',
                'visibility' => 'team',
                'color' => '#c49a3c',
                'text_color' => '#111318',
                'end_at' => now()->addDays(5)->startOfDay(),
                'all_day' => true,
                'editable' => true,
                'overlap' => true,
                'display' => 'background',
                'extended_props' => ['case' => $caseB->internal_code],
                'owner_id' => $administrator->id,
                'created_by' => $superAdmin?->id,
            ],
        );

        ContactMessage::query()->updateOrCreate(
            ['email' => 'contato.novo@lead.demo', 'subject' => 'Consulta societaria'],
            [
                'name' => 'Ricardo Tavares',
                'phone' => '(11) 99777-1100',
                'area_interest' => 'empresarial',
                'message' => 'Gostaria de entender o fluxo de reorganizacao societaria para uma nova rodada de investimento.',
                'consent' => true,
                'status' => 'new',
                'source_page' => 'contato',
                'source_url' => 'https://pujani.kdkhost.com.br/contato',
                'referrer' => 'https://pujani.kdkhost.com.br/',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder Demo',
                'notes' => '<p>Lead de exemplo para visualizacao da fila comercial.</p>',
            ],
        );
    }

    private function createDemoDocument(string $directory, string $fileName, string $content): string
    {
        $absoluteDirectory = public_path(trim($directory, '/'));
        File::ensureDirectoryExists($absoluteDirectory, 0755, true);
        File::put($absoluteDirectory.'/'.$fileName, $content);

        return trim($directory, '/').'/'.$fileName;
    }
}
