<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClientPortalController extends Controller
{
    public function login(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('portal_client_id')) {
            return redirect()->route('portal.dashboard');
        }

        return view('site.portal.login', [
            'portalPanel' => $this->portalPanel(),
        ]);
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'document_number' => ['required', 'string', 'max:32'],
            'access_code' => ['required', 'string', 'max:32'],
        ]);

        $documentDigits = preg_replace('/\D+/', '', $data['document_number']);

        $client = Client::query()
            ->where('portal_enabled', true)
            ->where('is_active', true)
            ->get()
            ->first(fn (Client $item): bool => preg_replace('/\D+/', '', (string) $item->document_number) === $documentDigits);

        if (! $client || ! Hash::check($data['access_code'], (string) $client->portal_access_code)) {
            return back()
                ->withInput($request->except('access_code'))
                ->withErrors([
                    'document_number' => 'Não foi possível validar o acesso com os dados informados.',
                ]);
        }

        $request->session()->put('portal_client_id', $client->id);
        $request->session()->regenerate();

        $client->forceFill([
            'portal_last_login_at' => now(),
            'portal_last_login_ip' => $request->ip(),
        ])->save();

        return redirect()->route('portal.dashboard');
    }

    public function dashboard(Request $request): View
    {
        $client = $this->portalClient($request);
        $cases = $this->portalCasesQuery($client)
            ->with(['primaryLawyer:id,name,email,phone'])
            ->withCount([
                'updates as visible_updates_count' => fn ($query) => $query->where('is_visible_to_client', true),
                'legalDocuments as shared_documents_count' => fn ($query) => $query->where('shared_with_client', true),
            ])
            ->orderByRaw('next_deadline_at is null, next_deadline_at asc')
            ->get();

        $recentUpdates = $client->legalCaseUpdates()
            ->with('legalCase:id,title')
            ->where('is_visible_to_client', true)
            ->orderByDesc('occurred_at')
            ->limit(8)
            ->get();

        $sharedDocuments = LegalDocument::query()
            ->where('client_id', $client->id)
            ->where('shared_with_client', true)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('site.portal.dashboard', [
            'portalPanel' => $this->portalPanel(),
            'client' => $client,
            'cases' => $cases,
            'recentUpdates' => $recentUpdates,
            'sharedDocuments' => $sharedDocuments,
            'stats' => [
                'cases' => $cases->count(),
                'deadlines' => $cases->filter(fn (LegalCase $legalCase) => filled($legalCase->next_deadline_at))->count(),
                'documents' => $sharedDocuments->count(),
                'updates' => $recentUpdates->count(),
            ],
        ]);
    }

    public function showCase(Request $request, string $case): View
    {
        $client = $this->portalClient($request);

        $legalCase = $this->portalCasesQuery($client)
            ->with(['primaryLawyer:id,name,email,phone', 'client:id,name,whatsapp,email'])
            ->findOrFail($case);

        $updates = $legalCase->updates()
            ->where('is_visible_to_client', true)
            ->orderByDesc('occurred_at')
            ->get();

        $documents = $legalCase->legalDocuments()
            ->where('shared_with_client', true)
            ->orderByDesc('created_at')
            ->get();

        return view('site.portal.case', [
            'portalPanel' => $this->portalPanel(),
            'client' => $client,
            'legalCase' => $legalCase,
            'updates' => $updates,
            'documents' => $documents,
        ]);
    }

    public function downloadDocument(Request $request, string $document): BinaryFileResponse
    {
        $client = $this->portalClient($request);

        $legalDocument = LegalDocument::query()
            ->whereKey($document)
            ->where('client_id', $client->id)
            ->where('shared_with_client', true)
            ->firstOrFail();

        $path = public_path(ltrim((string) $legalDocument->path, '/'));

        abort_unless(is_file($path), 404);

        return response()->download($path, $legalDocument->original_name ?: basename($path));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('portal_client_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('portal.login')
            ->with('portal_status', 'Você saiu do portal do cliente.');
    }

    private function portalClient(Request $request): Client
    {
        /** @var Client $client */
        $client = $request->attributes->get('portalClient');

        return $client;
    }

    private function portalCasesQuery(Client $client)
    {
        return LegalCase::query()
            ->where('client_id', $client->id)
            ->where('is_active', true)
            ->where('portal_visible', true);
    }

    private function portalPanel(): array
    {
        return [
            'eyebrow' => (string) setting('portal.login_eyebrow', 'Acompanhamento digital'),
            'title' => (string) setting('portal.login_title', 'Acompanhe seus processos com clareza e segurança.'),
            'description' => (string) setting('portal.login_description', 'Consulte movimentações, prazos relevantes e documentos compartilhados pelo escritório em um ambiente reservado.'),
            'support_text' => (string) setting('portal.support_text', 'Para suporte de acesso, fale com a equipe do escritório pelo telefone ou WhatsApp cadastrado.'),
            'metrics' => collect([1, 2, 3])->map(fn (int $index): array => [
                'title' => (string) setting("portal.metric_{$index}_title", match ($index) {
                    1 => 'Processos',
                    2 => 'Documentos',
                    default => 'Prazos',
                }),
                'subtitle' => (string) setting("portal.metric_{$index}_subtitle", match ($index) {
                    1 => 'Histórico organizado',
                    2 => 'Arquivos compartilhados',
                    default => 'Visão objetiva do caso',
                }),
            ])->filter(fn (array $metric): bool => filled($metric['title']) || filled($metric['subtitle']))->values(),
            'brand' => [
                'name' => config('app.name'),
                'short' => Str::upper(Str::substr(config('app.name'), 0, 1)),
            ],
        ];
    }
}
