<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Clinic messaging (reminders, birthday, no-show) — PREPARATION ONLY
    |--------------------------------------------------------------------------
    | The selection logic and scheduled command exist now; actual sending
    | (WhatsApp/e-mail) is wired later. Toggle each channel when ready.
    */

    'messaging' => [
        // Master switch — keep false until a sending channel is connected.
        'enabled' => (bool) env('CLINIC_MESSAGING_ENABLED', false),

        // How many hours before the appointment to send a reminder.
        'reminder_hours_before' => (int) env('CLINIC_REMINDER_HOURS_BEFORE', 24),

        // Birthday greeting send hour (local time), processed daily.
        'birthday_enabled' => (bool) env('CLINIC_BIRTHDAY_ENABLED', true),

        // No-show follow-up (cobrança de ausentes).
        'no_show_followup_enabled' => (bool) env('CLINIC_NOSHOW_FOLLOWUP_ENABLED', true),
    ],

];
