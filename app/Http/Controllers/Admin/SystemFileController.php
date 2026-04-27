<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemFileManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SystemFileController extends Controller
{
    public function __construct(protected SystemFileManagerService $manager)
    {
    }

    public function index(): View
    {
        return view('admin.system-files.index', [
            'pageTitle' => 'Arquivos do Sistema',
            'files' => $this->manager->all(),
        ]);
    }

    public function update(Request $request, string $fileKey): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);

        try {
            $this->manager->update($fileKey, $data['content'], auth()->id());
            activity_log('system-files', 'updated', null, ['file' => $fileKey], 'Arquivo do sistema atualizado.');

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
        $data = $request->validate([
            'backup_name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->manager->restore($fileKey, $data['backup_name'], auth()->id());
            activity_log('system-files', 'restored', null, [
                'file' => $fileKey,
                'backup' => $data['backup_name'],
            ], 'Backup de arquivo do sistema restaurado.');

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
}
