<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class SensitiveActivityObserver
{
    public function updated(Model $model): void
    {
        $changes = collect($model->getChanges())
            ->except(['updated_at', 'password', 'remember_token'])
            ->map(fn ($value, $key) => ['before' => $model->getOriginal($key), 'after' => $value])
            ->all();

        if ($changes) {
            $this->record($model, 'updated', $changes);
        }
    }

    public function deleted(Model $model): void { $this->record($model, 'deleted'); }

    private function record(Model $model, string $event, array $changes = []): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(), 'event' => $event, 'changes' => $changes,
            'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent(),
        ])->subject()->associate($model)->save();
    }
}
