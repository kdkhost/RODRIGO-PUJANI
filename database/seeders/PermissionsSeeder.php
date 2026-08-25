<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'admin.access',
            'clients.manage',
            'pages.manage',
            'page-sections.manage',
            'practice-areas.manage',
            'team-members.manage',
            'testimonials.manage',
            'contact-messages.manage',
            'mail-templates.manage',
            'media-assets.manage',
            'seo-metas.manage',
            'users.manage',
            'roles.manage',
            'permissions.manage',
            'settings.manage',
            'analytics.view',
            'system-files.manage',
            'calendar.manage',
            'legal-cases.manage',
            'legal-case-updates.manage',
            'legal-tasks.manage',
            'legal-documents.manage',
            'client-portal.manage',
            'preloader.manage',
            'impersonate.users',
            'signature-requests.view',
            'signature-requests.create',
            'signature-requests.manage',
            'signature-requests.cancel',
            'signature-requests.download',
            'signature-requests.audit',
            'legal-workspace.view',
            'djen-publications.view',
            'djen-publications.review',
            'djen-monitors.manage',
            'djen-monitors.sync',
            'legal-deadlines.reminders',
            'google-calendar.manage',
            'legal-document-templates.view',
            'legal-document-templates.manage',
            'legal-document-templates.generate',
            'legal-ai.configure',
            'legal-ai.generate',
            'legal-ai.review',
            'legal-ai.approve',
            'legal-ai.publish',
            'hearing-transcriptions.manage',
            'hearing-transcriptions.approve',
            'financial.manage',
        ];

        foreach ($permissions as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::query()->firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $administrator = Role::query()->firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $associatedLawyer = Role::query()->firstOrCreate(['name' => 'Advogado Associado', 'guard_name' => 'web']);
        $editor = Role::query()->firstOrCreate(['name' => 'Editor', 'guard_name' => 'web']);

        $superAdmin->syncPermissions(Permission::all());
        $administrator->syncPermissions(
            Permission::query()
                ->where('name', '!=', 'system-files.manage')
                ->pluck('name')
                ->all()
        );
        $editor->syncPermissions([
            'admin.access',
            'clients.manage',
            'pages.manage',
            'page-sections.manage',
            'practice-areas.manage',
            'team-members.manage',
            'testimonials.manage',
            'contact-messages.manage',
            'mail-templates.manage',
            'media-assets.manage',
            'seo-metas.manage',
            'calendar.manage',
            'legal-cases.manage',
            'legal-case-updates.manage',
            'legal-tasks.manage',
            'legal-documents.manage',
            'client-portal.manage',
            'legal-workspace.view',
            'djen-publications.view',
            'djen-publications.review',
            'djen-monitors.manage',
            'djen-monitors.sync',
            'legal-deadlines.reminders',
            'google-calendar.manage',
            'legal-document-templates.view',
            'legal-document-templates.manage',
            'legal-document-templates.generate',
            'legal-ai.generate',
            'legal-ai.review',
            'legal-ai.approve',
            'hearing-transcriptions.manage',
            'hearing-transcriptions.approve',
            'financial.manage',
        ]);

        $associatedLawyer->syncPermissions([
            'admin.access',
            'calendar.manage',
            'clients.manage',
            'legal-cases.manage',
            'legal-case-updates.manage',
            'legal-tasks.manage',
            'legal-documents.manage',
            'legal-workspace.view',
            'djen-publications.view',
            'djen-publications.review',
            'djen-monitors.sync',
            'legal-deadlines.reminders',
            'google-calendar.manage',
            'legal-document-templates.view',
            'legal-document-templates.generate',
            'legal-ai.generate',
            'legal-ai.review',
            'hearing-transcriptions.manage',
        ]);

        $configuredPassword = env('APP_ADMIN_PASSWORD');
        $admin = User::query()->firstOrCreate(
            ['email' => env('APP_ADMIN_EMAIL', 'admin@pujani.adv.br')],
            [
                'name' => env('APP_ADMIN_NAME', 'Administrador Pujani'),
                'password' => Hash::make(filled($configuredPassword) ? (string) $configuredPassword : Str::password(32)),
                'timezone' => 'America/Sao_Paulo',
                'is_active' => true,
            ]
        );

        $admin->forceFill([
            'name' => env('APP_ADMIN_NAME', 'Administrador Pujani'),
            'timezone' => 'America/Sao_Paulo',
            'is_active' => true,
        ]);

        if (filled($configuredPassword)) {
            $admin->password = Hash::make((string) $configuredPassword);
        }

        $admin->save();

        $admin->syncRoles([$superAdmin->name]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
