<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalDeadlinePreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegalDeadlinePreferenceController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deadline_reminders_enabled' => ['nullable', 'boolean'],
            'daily_summary_enabled' => ['nullable', 'boolean'],
            'daily_summary_time' => ['required', 'date_format:H:i'],
            'daily_summary_days_ahead' => ['required', 'integer', 'min:1', 'max:30'],
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $preference = LegalDeadlinePreference::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'deadline_reminders_enabled' => $request->boolean('deadline_reminders_enabled'),
                'daily_summary_enabled' => $request->boolean('daily_summary_enabled'),
                'daily_summary_time' => $validated['daily_summary_time'].':00',
                'daily_summary_days_ahead' => $validated['daily_summary_days_ahead'],
                'timezone' => $validated['timezone'],
                'email' => filled($validated['email'] ?? null) ? mb_strtolower(trim((string) $validated['email'])) : null,
            ],
        );
        activity_log('legal_deadline_preferences', 'updated', $preference, [
            'deadline_reminders_enabled' => $preference->deadline_reminders_enabled,
            'daily_summary_enabled' => $preference->daily_summary_enabled,
            'daily_summary_time' => $preference->daily_summary_time,
            'daily_summary_days_ahead' => $preference->daily_summary_days_ahead,
            'timezone' => $preference->timezone,
            'custom_email' => filled($preference->email),
        ], 'Preferências de prazos atualizadas.');

        return response()->json(['message' => 'Preferências de prazos atualizadas.']);
    }
}
