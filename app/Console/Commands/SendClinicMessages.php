<?php

namespace App\Console\Commands;

use App\Services\Clinic\ClinicMessenger;
use Illuminate\Console\Command;

class SendClinicMessages extends Command
{
    protected $signature = 'clinic:send-messages
        {--type=all : reminder|birthday|no_show|all}
        {--dry-run : Only report who would be messaged}';

    protected $description = 'Send clinic reminders, birthday greetings and no-show follow-ups.';

    public function handle(ClinicMessenger $messenger): int
    {
        $type = $this->option('type');
        $dry = (bool) $this->option('dry-run');
        $enabled = (bool) config('clinic.messaging.enabled');

        if (! $enabled && ! $dry) {
            $this->warn('Mensageria desativada (clinic.messaging.enabled=false). Use --dry-run para simular.');

            return self::SUCCESS;
        }

        $total = 0;

        if (in_array($type, ['reminder', 'all'], true)) {
            foreach ($messenger->dueReminders() as $appt) {
                $this->line("Lembrete → {$appt->customer->name} ({$appt->starts_at->format('d/m H:i')})");
                if (! $dry && $messenger->send('reminder', $appt->customer, ['appointment_id' => $appt->id])) {
                    $appt->forceFill(['reminder_sent_at' => now()])->save();
                }
                $total++;
            }
        }

        if (in_array($type, ['birthday', 'all'], true) && config('clinic.messaging.birthday_enabled')) {
            foreach ($messenger->birthdaysToday() as $customer) {
                $this->line("Aniversário → {$customer->name}");
                if (! $dry) {
                    $messenger->send('birthday', $customer);
                }
                $total++;
            }
        }

        if (in_array($type, ['no_show', 'all'], true) && config('clinic.messaging.no_show_followup_enabled')) {
            foreach ($messenger->noShowFollowups() as $appt) {
                $this->line("Cobrança ausente → {$appt->customer->name} ({$appt->starts_at->format('d/m H:i')})");
                if (! $dry && $messenger->send('no_show', $appt->customer, ['appointment_id' => $appt->id])) {
                    $appt->forceFill(['reminder_sent_at' => now()])->save();
                }
                $total++;
            }
        }

        $this->info(($dry ? '[dry-run] ' : '')."Processados: {$total}");

        return self::SUCCESS;
    }
}
