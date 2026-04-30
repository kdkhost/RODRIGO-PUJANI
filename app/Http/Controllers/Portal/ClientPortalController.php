<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalDocument;
use App\Services\RecaptchaService;
use App\Support\PublicUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
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

    public function authenticate(Request $request, RecaptchaService $recaptcha): RedirectResponse
    {
        $data = $request->validate([
            'document_number' => ['required', 'string', 'max:32'],
            'access_code' => ['required', 'string', 'max:32'],
        ]);

        $recaptcha->validateOrFail($request, 'portal_login');

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

        $rawUpdatesTrend = $client->legalCaseUpdates()
            ->where('is_visible_to_client', true)
            ->where('occurred_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get()
            ->groupBy(fn ($update) => $update->occurred_at?->copy()->startOfMonth()->format('Y-m-01'))
            ->map(fn ($group) => $group->count());

        $updatesTrend = collect(range(5, 0))->map(function (int $monthsAgo) use ($rawUpdatesTrend): array {
            $date = now()->subMonths($monthsAgo)->startOfMonth();

            return [
                'label' => $date->format('m/Y'),
                'total' => (int) ($rawUpdatesTrend[$date->format('Y-m-01')] ?? 0),
            ];
        });

        return view('site.portal.dashboard', [
            'portalPanel' => $this->portalPanel(),
            'client' => $client,
            'portalWhatsappContacts' => $this->portalWhatsappContacts($client),
            'cases' => $cases,
            'recentUpdates' => $recentUpdates,
            'sharedDocuments' => $sharedDocuments,
            'stats' => [
                'cases' => $cases->count(),
                'deadlines' => $cases->filter(fn (LegalCase $legalCase) => filled($legalCase->next_deadline_at))->count(),
                'hearings' => $cases->filter(fn (LegalCase $legalCase) => filled($legalCase->next_hearing_at))->count(),
                'documents' => $sharedDocuments->count(),
                'updates' => $recentUpdates->count(),
            ],
            'caseStatusBreakdown' => $cases
                ->groupBy('status')
                ->map(fn ($group, $status): array => [
                    'label' => str((string) $status)->replace('_', ' ')->headline()->toString(),
                    'total' => $group->count(),
                ])
                ->values(),
            'documentCategoryBreakdown' => $sharedDocuments
                ->groupBy(fn ($document) => $document->category ?: 'Documento')
                ->map(fn ($group, $category): array => [
                    'label' => (string) $category,
                    'total' => $group->count(),
                ])
                ->values(),
            'updatesTrend' => $updatesTrend,
            'upcomingMilestones' => $cases
                ->flatMap(function (LegalCase $legalCase) {
                    $items = collect();

                    if ($legalCase->next_deadline_at) {
                        $items->push([
                            'type' => 'Prazo',
                            'title' => $legalCase->title,
                            'subtitle' => $legalCase->process_number ?: ($legalCase->practice_area ?: 'Processo interno'),
                            'at' => $legalCase->next_deadline_at,
                        ]);
                    }

                    if ($legalCase->next_hearing_at) {
                        $items->push([
                            'type' => 'Audiência',
                            'title' => $legalCase->title,
                            'subtitle' => $legalCase->court_name ?: ($legalCase->process_number ?: 'Processo'),
                            'at' => $legalCase->next_hearing_at,
                        ]);
                    }

                    return $items;
                })
                ->sortBy('at')
                ->take(6)
                ->values(),
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
            'portalWhatsappContacts' => $this->portalWhatsappContacts($client),
            'legalCase' => $legalCase,
            'updates' => $updates,
            'documents' => $documents,
        ]);
    }

    public function profile(Request $request): View
    {
        $client = $this->portalClient($request);

        return view('site.portal.profile', [
            'portalPanel' => $this->portalPanel(),
            'client' => $client,
            'portalWhatsappContacts' => $this->portalWhatsappContacts($client),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $client = $this->portalClient($request);

        $request->validate([
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $canUpdateRegistration = (bool) $client->portal_profile_update_allowed;

        if ($canUpdateRegistration) {
            $validated = $request->validate([
                'person_type' => ['required', Rule::in(['individual', 'company'])],
                'name' => ['required', 'string', 'max:255'],
                'trade_name' => ['nullable', 'string', 'max:255'],
                'document_number' => ['nullable', 'string', 'max:32'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'],
                'whatsapp' => ['nullable', 'string', 'max:30'],
                'alternate_phone' => ['nullable', 'string', 'max:30'],
                'birth_date' => ['nullable', 'date'],
                'profession' => ['nullable', 'string', 'max:255'],
                'address_zip' => ['nullable', 'string', 'max:12'],
                'address_street' => ['nullable', 'string', 'max:255'],
                'address_number' => ['nullable', 'string', 'max:20'],
                'address_complement' => ['nullable', 'string', 'max:255'],
                'address_district' => ['nullable', 'string', 'max:255'],
                'address_city' => ['nullable', 'string', 'max:255'],
                'address_state' => ['nullable', 'string', 'max:8'],
            ]);

            $validated['address_state'] = filled($validated['address_state'] ?? null)
                ? strtoupper((string) $validated['address_state'])
                : null;

            if (($validated['person_type'] ?? null) === 'individual') {
                unset($validated['trade_name']);
            }

            $client->fill($validated);
        }

        if ($request->hasFile('avatar')) {
            $client->avatar_path = PublicUpload::store(
                $request->file('avatar'),
                'client-avatars',
                $client->avatar_path,
                null,
            );
        }

        $client->save();

        return redirect()
            ->route('portal.profile')
            ->with('portal_status', $canUpdateRegistration
                ? 'Dados cadastrais atualizados com sucesso.'
                : 'Foto de perfil atualizada com sucesso. A edição cadastral depende de liberação do escritório.');
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

    private function portalWhatsappContacts(Client $client): Collection
    {
        return $this->portalCasesQuery($client)
            ->whereNotIn('status', ['closed', 'archived'])
            ->with([
                'primaryLawyer:id,name,email,phone,whatsapp,avatar_path',
                'supervisingLawyer:id,name,email,phone,whatsapp,avatar_path',
            ])
            ->get()
            ->flatMap(function (LegalCase $legalCase): array {
                return [
                    [
                        'lawyer' => $legalCase->primaryLawyer,
                        'role' => 'Advogado responsável',
                        'case' => $legalCase->title,
                    ],
                    [
                        'lawyer' => $legalCase->supervisingLawyer,
                        'role' => 'Supervisor jurídico',
                        'case' => $legalCase->title,
                    ],
                ];
            })
            ->filter(fn (array $item): bool => filled($item['lawyer']?->whatsapp))
            ->unique(fn (array $item): ?int => $item['lawyer']?->id)
            ->map(function (array $item): array {
                $lawyer = $item['lawyer'];

                return [
                    'id' => $lawyer->id,
                    'name' => $lawyer->name,
                    'role' => $item['role'],
                    'case' => $item['case'],
                    'whatsapp' => preg_replace('/\D+/', '', (string) $lawyer->whatsapp),
                    'avatar_url' => $lawyer->avatar_path ? site_asset_url($lawyer->avatar_path) : null,
                ];
            })
            ->filter(fn (array $item): bool => strlen($item['whatsapp']) >= 10)
            ->values();
    }

    private function portalPanel(): array
    {
        $branding = branding_config();

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
                'name' => $branding['brand_name'],
                'short' => $branding['brand_short_name'],
                'logo_url' => $branding['logo_url'],
            ],
        ];
    }
}
