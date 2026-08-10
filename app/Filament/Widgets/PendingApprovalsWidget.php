<?php

namespace App\Filament\Widgets;

use App\Services\ApprovalDashboardService;
use Filament\Widgets\Widget;

class PendingApprovalsWidget extends Widget
{
    protected string $view = 'filament.widgets.pending-approvals-widget';

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 12;

    public function getItems()
    {
        return ApprovalDashboardService::pendingItems(auth()->user(), 15);
    }
}
