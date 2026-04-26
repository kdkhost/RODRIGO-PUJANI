<?php

namespace App\Http\Controllers;

use App\Services\InstallerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class InstallController extends Controller
{
    public function __construct(protected InstallerService $installer)
    {
    }

    public function index(): View|RedirectResponse
    {
        if ($this->installer->isInstalled()) {
            return redirect()->route('site.home');
        }

        return view('install.index', [
            'status' => $this->installer->status(),
            'defaults' => [
                'app_name' => env('APP_NAME', 'Pujani Advogados'),
                'app_url' => env('APP_URL', 'http://localhost'),
                'db_connection' => env('DB_CONNECTION', 'mariadb'),
                'db_host' => env('DB_HOST', '127.0.0.1'),
                'db_port' => env('DB_PORT', '3306'),
                'db_database' => env('DB_DATABASE', ''),
                'db_username' => env('DB_USERNAME', ''),
                'admin_name' => env('APP_ADMIN_NAME', 'Administrador'),
                'admin_email' => env('APP_ADMIN_EMAIL', 'admin@pujani.adv.br'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->installer->isInstalled()) {
            return redirect()->route('site.home');
        }

        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'app_url' => ['required', 'url', 'max:255'],
            'db_connection' => ['required', Rule::in(['mysql', 'mariadb'])],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'fresh_install' => ['nullable', 'boolean'],
        ]);

        try {
            $user = $this->installer->install($data, $request->boolean('fresh_install'));

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()
                ->route('admin.dashboard')
                ->with('status', 'Sistema instalado com sucesso.');
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['admin_password', 'admin_password_confirmation']))
                ->withErrors([
                    'installer' => 'Nao foi possivel concluir a instalacao. Revise os dados do banco, as permissoes de escrita e tente novamente.',
                ]);
        }
    }
}
