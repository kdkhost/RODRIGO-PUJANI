<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemFileManagerService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SystemFileController extends Controller
{
    public function __construct(protected SystemFileManagerService $manager)
    {
    }

    public function showConfirmation(): View|RedirectResponse
    {
        if (session('system_files.page_confirmed') === true) {
            return redirect()->route('admin.system-files.index');
        }

        session()->forget(['system_files.access_token']);

        return view('admin.system-files.confirm', [
            'pageTitle' => 'Confirmar Identidade',
        ]);
    }

    public function storeConfirmation(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->string('password')->toString(),
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('system_files.page_confirmed', true);

        return redirect()->to(
            $request->session()->pull('system_files.intended_url', route('admin.system-files.index'))
        );
    }

    public function index(Request $request): View
    {
        $files = $this->manager->all();
        $accessToken = (string) Str::uuid();

        $request->session()->forget('system_files.page_confirmed');
        $request->session()->put('system_files.access_token', $accessToken);

        return view('admin.system-files.index', [
            'pageTitle' => 'Arquivos do Sistema',
            'files' => $files,
            'accessToken' => $accessToken,
            'systemFileStats' => [
                'total' => count($files),
                'writable' => collect($files)->where('writable', true)->count(),
                'backups' => collect($files)->sum('backup_count'),
                'critical' => collect($files)->where('risk_level', 'Crítico')->count(),
            ],
        ]);
    }

    public function update(Request $request, string $fileKey): JsonResponse|RedirectResponse
    {
        $this->ensureAccessToken($request);

        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);

        try {
            $this->manager->update($fileKey, $data['content'], auth()->id());
            activity_log('system-files', 'updated', null, ['file' => $fileKey], 'Arquivo do sistema atualizado.');
            $request->session()->put('system_files.page_confirmed', true);

            return response()->json([
                'message' => 'Arquivo atualizado com sucesso.',
                'closeModal' => false,
                'redirect' => route('admin.system-files.index').'#arquivo-'.$fileKey,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Não foi possível salvar o arquivo solicitado.',
            ], 500);
        }
    }

    public function restore(Request $request, string $fileKey): JsonResponse|RedirectResponse
    {
        $this->ensureAccessToken($request);

        $data = $request->validate([
            'backup_name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->manager->restore($fileKey, $data['backup_name'], auth()->id());
            activity_log('system-files', 'restored', null, [
                'file' => $fileKey,
                'backup' => $data['backup_name'],
            ], 'Backup de arquivo do sistema restaurado.');
            $request->session()->put('system_files.page_confirmed', true);

            return response()->json([
                'message' => 'Backup restaurado com sucesso.',
                'closeModal' => false,
                'redirect' => route('admin.system-files.index').'#arquivo-'.$fileKey,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Não foi possível restaurar o backup selecionado.',
            ], 500);
        }
    }

    private function ensureAccessToken(Request $request): void
    {
        $expectedToken = (string) $request->session()->get('system_files.access_token', '');
        $providedToken = (string) $request->input('access_token', '');

        if ($expectedToken !== '' && hash_equals($expectedToken, $providedToken)) {
            return;
        }

        $request->session()->forget('system_files.access_token');
        $request->session()->put('system_files.intended_url', route('admin.system-files.index'));

        throw new HttpResponseException(response()->json([
            'message' => 'A liberação desta área expirou. Confirme sua senha novamente para continuar.',
            'redirect' => route('admin.system-files.confirm'),
        ], 423));
    }
}
