<?php

namespace App\Console\Commands;

use App\Models\{FormCuti, Kasbon, NotaPenggantianBiaya, PerjalananDinas, SuratPerintahBayar};
use App\Notifications\ReminderNotification;
use Illuminate\Console\Command;

class SendWorkflowReminders extends Command
{
    protected $signature = 'workflow:send-reminders {--dry-run : Preview reminders without sending} {--limit=50 : Maximum reminders per run}';
    protected $description = 'Send one daily reminder for overdue pending requests, upcoming leave, and unpaid approved SPB.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run'); $count = 0; $limit = max(1, (int) $this->option('limit'));
        foreach ([Kasbon::class, PerjalananDinas::class, NotaPenggantianBiaya::class, SuratPerintahBayar::class, FormCuti::class] as $model) {
            $model::query()->with('user')->where('status', 'Pending Approval')->whereBetween('created_at', [now()->subDays(30), now()->subDays(3)])->limit($limit)->each(function ($record) use (&$count, $dryRun, $limit): void {
                if ($count >= $limit) return;
                $this->notify($record->user, 'pending:'.get_class($record).":{$record->id}", 'Approval Pending', class_basename($record).' has been pending for more than three days.', $dryRun, $count);
            });
        }
        FormCuti::query()->with('user')->where('status', 'Approved')->whereDate('tanggal_mulai', now()->addDay())->limit($limit)->each(function (FormCuti $leave) use (&$count, $dryRun, $limit): void {
            if ($count >= $limit) return;
            $this->notify($leave->user, "leave:{$leave->id}:".now()->toDateString(), 'Leave Starts Tomorrow', 'Your approved leave starts tomorrow.', $dryRun, $count);
        });
        SuratPerintahBayar::query()->with('user')->where('status', 'Approved')->whereNull('paid_at')->limit($limit)->each(function (SuratPerintahBayar $spb) use (&$count, $dryRun, $limit): void {
            if ($count >= $limit) return;
            $this->notify($spb->user, "unpaid-spb:{$spb->id}", 'Payment Application Unpaid', 'Your approved payment application is still unpaid.', $dryRun, $count);
        });
        $this->info("{$count} reminder(s) ".($dryRun ? 'would be sent.' : 'sent.'));
        return self::SUCCESS;
    }

    private function notify($user, string $key, string $title, string $message, bool $dryRun, int &$count): void
    {
        if (! $user || $user->notifications()->where('data->reminder_key', $key)->exists()) return;
        if (! $dryRun) $user->notify(new ReminderNotification($title, $message, $key));
        $count++;
    }
}
