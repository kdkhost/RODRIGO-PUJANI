<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        foreach (range((int) now()->year - 1, (int) now()->year + 5) as $year) {
            foreach ($this->holidaysForYear($year) as $holiday) {
                DB::table('calendar_events')->updateOrInsert(
                    [
                        'title' => $holiday['title'],
                        'category' => 'Feriado nacional',
                        'start_at' => $holiday['start_at'],
                    ],
                    [
                        'description' => $holiday['description'],
                        'location' => null,
                        'url' => null,
                        'status' => 'confirmed',
                        'visibility' => 'team',
                        'color' => '#C49A3C',
                        'text_color' => '#111318',
                        'end_at' => $holiday['end_at'],
                        'all_day' => true,
                        'editable' => true,
                        'overlap' => false,
                        'display' => 'background',
                        'extended_props' => json_encode([
                            'is_holiday' => true,
                            'holiday_scope' => 'national',
                            'holiday_code' => $holiday['code'],
                            'holiday_year' => $year,
                        ], JSON_UNESCAPED_UNICODE),
                        'owner_id' => null,
                        'created_by' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        DB::table('calendar_events')
            ->where('category', 'Feriado nacional')
            ->whereIn('title', [
                'Confraternização Universal',
                'Paixão de Cristo',
                'Tiradentes',
                'Dia do Trabalho',
                'Independência do Brasil',
                'Nossa Senhora Aparecida',
                'Finados',
                'Proclamação da República',
                'Dia Nacional de Zumbi e da Consciência Negra',
                'Natal',
            ])
            ->delete();
    }

    private function holidaysForYear(int $year): array
    {
        $easter = Carbon::createFromTimestamp(easter_date($year))->startOfDay();

        return [
            $this->holiday('confraternizacao-universal', 'Confraternização Universal', Carbon::create($year, 1, 1), 'Feriado nacional fixo de abertura do ano.'),
            $this->holiday('paixao-de-cristo', 'Paixão de Cristo', $easter->copy()->subDays(2), 'Feriado nacional móvel calculado a partir da Páscoa.'),
            $this->holiday('tiradentes', 'Tiradentes', Carbon::create($year, 4, 21), 'Feriado nacional em homenagem a Tiradentes.'),
            $this->holiday('dia-do-trabalho', 'Dia do Trabalho', Carbon::create($year, 5, 1), 'Feriado nacional dedicado ao trabalho.'),
            $this->holiday('independencia-do-brasil', 'Independência do Brasil', Carbon::create($year, 9, 7), 'Feriado nacional da independência.'),
            $this->holiday('nossa-senhora-aparecida', 'Nossa Senhora Aparecida', Carbon::create($year, 10, 12), 'Feriado nacional religioso.'),
            $this->holiday('finados', 'Finados', Carbon::create($year, 11, 2), 'Feriado nacional de finados.'),
            $this->holiday('proclamacao-da-republica', 'Proclamação da República', Carbon::create($year, 11, 15), 'Feriado nacional da república.'),
            $this->holiday('consciencia-negra', 'Dia Nacional de Zumbi e da Consciência Negra', Carbon::create($year, 11, 20), 'Feriado nacional da consciência negra.'),
            $this->holiday('natal', 'Natal', Carbon::create($year, 12, 25), 'Feriado nacional de Natal.'),
        ];
    }

    private function holiday(string $code, string $title, Carbon $date, string $description): array
    {
        return [
            'code' => $code,
            'title' => $title,
            'description' => $description,
            'start_at' => $date->copy()->startOfDay()->toDateTimeString(),
            'end_at' => $date->copy()->endOfDay()->toDateTimeString(),
        ];
    }
};
