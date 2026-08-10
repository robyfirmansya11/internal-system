<?php

namespace App\Filament\Widgets;

use App\Services\ApprovalDashboardService;
use Filament\Widgets\Widget;

class RecentActivityWidget extends Widget
{
    protected string $view = 'filament.widgets.recent-activity-widget';

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 12;

    public function getItems()
    {
        return ApprovalDashboardService::recentActivity(auth()->user(), 15);
    }
}
