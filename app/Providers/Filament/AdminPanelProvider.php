<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AbsentTodayWidget;
use App\Filament\Widgets\ApprovalSummaryWidget;
use App\Filament\Widgets\AttendanceChartWidget;
use App\Filament\Widgets\AttendanceTodayWidget;
use App\Filament\Widgets\PendingApprovalsWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\StatusDistributionChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandName('Internal System')
            ->brandLogo(asset('Logo_InSys.png'))
            ->brandLogoHeight('7rem')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            // discoverWidgets() dihapus — sebelumnya dipakai BERSAMAAN
            // dengan ->widgets([...]) di bawah, yang berisiko widget
            // terdaftar dobel (sekali via auto-discovery, sekali lagi
            // via daftar manual). Sekarang kontrol penuh lewat ->widgets()
            // saja, termasuk urutan tampil & column span-nya.
            ->widgets([
                QuickActionsWidget::class,
                AttendanceTodayWidget::class,
                AbsentTodayWidget::class, // ← BARU
                AttendanceChartWidget::class,

                ApprovalSummaryWidget::class,
                PendingApprovalsWidget::class,

                RecentActivityWidget::class,
                StatusDistributionChart::class,
                AccountWidget::class,
                FilamentInfoWidget::class,

            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => <<<'HTML'
                    <style>
                        main.fi-main.fi-width-7xl {
                            max-width: 100% !important;
                        }

                        /* Signature Pad — border + garis dasar tanda tangan */
                        [x-ref="canvas"] {
                            border: 2px solid #d1d5db;
                            border-radius: 8px;
                            background-image: linear-gradient(
                                to bottom,
                                transparent calc(100% - 40px),
                                #9ca3af calc(100% - 40px),
                                #9ca3af calc(100% - 39px),
                                transparent calc(100% - 39px)
                            );
                        }
                    </style>
                    HTML
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::BODY_END,
                fn (): string => <<<'HTML'
                    <script>
                        document.addEventListener('livewire:init', () => {
                            Livewire.on('open-new-tab', (event) => {
                                window.open(event.url, '_blank');
                            });
                        });
                    </script>
                    HTML
            )
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
