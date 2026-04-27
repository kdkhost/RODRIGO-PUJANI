<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        foreach ($this->settings() as $item) {
            $exists = DB::table('settings')->where('key', $item['key'])->exists();

            if ($exists) {
                DB::table('settings')
                    ->where('key', $item['key'])
                    ->update([
                        'group' => $item['group'],
                        'label' => $item['label'],
                        'type' => $item['type'],
                        'is_public' => $item['is_public'],
                        'sort_order' => $item['sort_order'],
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('settings')->insert([
                'group' => $item['group'],
                'key' => $item['key'],
                'label' => $item['label'],
                'type' => $item['type'],
                'value' => $item['value'],
                'json_value' => null,
                'is_public' => $item['is_public'],
                'sort_order' => $item['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->whereIn('key', array_column($this->settings(), 'key'))
            ->delete();
    }

    private function settings(): array
    {
        return [
            ['group' => 'auth', 'key' => 'auth.panel_eyebrow', 'label' => 'Tela de login - chamada curta', 'type' => 'text', 'value' => 'Admin Suite', 'is_public' => true, 'sort_order' => 400],
            ['group' => 'auth', 'key' => 'auth.panel_title', 'label' => 'Tela de login - título', 'type' => 'text', 'value' => 'Gestão jurídica com acesso seguro.', 'is_public' => true, 'sort_order' => 401],
            ['group' => 'auth', 'key' => 'auth.panel_description', 'label' => 'Tela de login - descrição', 'type' => 'text', 'value' => 'Painel administrativo para conteúdo, agenda, mídias, usuários e permissões do escritório.', 'is_public' => true, 'sort_order' => 402],
            ['group' => 'auth', 'key' => 'auth.metric_1_title', 'label' => 'Tela de login - métrica 1 título', 'type' => 'text', 'value' => 'Laravel 13', 'is_public' => true, 'sort_order' => 410],
            ['group' => 'auth', 'key' => 'auth.metric_1_subtitle', 'label' => 'Tela de login - métrica 1 subtítulo', 'type' => 'text', 'value' => 'Base atual', 'is_public' => true, 'sort_order' => 411],
            ['group' => 'auth', 'key' => 'auth.metric_2_title', 'label' => 'Tela de login - métrica 2 título', 'type' => 'text', 'value' => 'ACL', 'is_public' => true, 'sort_order' => 412],
            ['group' => 'auth', 'key' => 'auth.metric_2_subtitle', 'label' => 'Tela de login - métrica 2 subtítulo', 'type' => 'text', 'value' => 'Permissões', 'is_public' => true, 'sort_order' => 413],
            ['group' => 'auth', 'key' => 'auth.metric_3_title', 'label' => 'Tela de login - métrica 3 título', 'type' => 'text', 'value' => 'PWA', 'is_public' => true, 'sort_order' => 414],
            ['group' => 'auth', 'key' => 'auth.metric_3_subtitle', 'label' => 'Tela de login - métrica 3 subtítulo', 'type' => 'text', 'value' => 'Experiência em app', 'is_public' => true, 'sort_order' => 415],
        ];
    }
};
