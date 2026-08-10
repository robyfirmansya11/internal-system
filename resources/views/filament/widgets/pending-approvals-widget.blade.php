@php use App\Services\ApprovalDashboardService as Dash; @endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Pending Approvals
        </x-slot>

        @php $items = $this->getItems(); @endphp

        <style>
            .pa-scroll {
                max-height: 340px;
                overflow-y: auto;
                scrollbar-width: thin;
                scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
            }
            .dark .pa-scroll { scrollbar-color: rgba(255, 255, 255, 0.15) transparent; }
            .pa-scroll::-webkit-scrollbar { width: 6px; }
            .pa-scroll::-webkit-scrollbar-track { background: transparent; }
            .pa-scroll::-webkit-scrollbar-thumb {
                background-color: rgba(0, 0, 0, 0.2);
                border-radius: 999px;
            }
            .dark .pa-scroll::-webkit-scrollbar-thumb { background-color: rgba(255, 255, 255, 0.15); }

            .pa-list { display: flex; flex-direction: column; }

            .pa-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.06);
                text-decoration: none;
                transition: opacity .15s ease;
            }
            .dark .pa-item { border-bottom-color: rgba(255, 255, 255, 0.06); }
            .pa-item:last-child { border-bottom: none; padding-bottom: 0; }
            .pa-item:first-child { padding-top: 0; }
            .pa-item:hover { opacity: .8; }

            .pa-icon {
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                border-radius: 10px;
            }
            .pa-icon svg { width: 20px; height: 20px; }

            .pa-body { min-width: 0; flex: 1; }
            .pa-title {
                font-size: 14px;
                font-weight: 500;
                color: #111827;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .dark .pa-title { color: #f3f4f6; }
            .pa-meta { font-size: 12px; color: #6b7280; margin-top: 2px; }
            .dark .pa-meta { color: #9ca3af; }

            .pa-badge {
                flex-shrink: 0;
                border-radius: 999px;
                padding: 4px 10px;
                font-size: 11px;
                font-weight: 500;
            }

            .pa-empty {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 32px 0;
                text-align: center;
            }
            .pa-empty svg { width: 40px; height: 40px; color: #22c55e; margin-bottom: 8px; }
            .pa-empty p { font-size: 14px; color: #6b7280; }
            .dark .pa-empty p { color: #9ca3af; }
        </style>

        @if($items->isEmpty())
            <div class="pa-empty">
                <x-filament::icon icon="heroicon-o-check-circle" />
                <p>No pending requests waiting for your approval.</p>
            </div>
        @else
            <div class="pa-scroll">
                <div class="pa-list">
                    @foreach($items as $item)
                        @php $hex = Dash::colorHex($item['color']); @endphp

                        <a href="{{ $item['url'] }}" class="pa-item">
                            <span class="pa-icon" style="background-color: {{ $hex }}1A;">
                                <x-filament::icon :icon="$item['icon']" style="color: {{ $hex }};" />
                            </span>

                            <div class="pa-body">
                                <p class="pa-title">{{ $item['title'] }}</p>
                                <p class="pa-meta">{{ $item['requester'] }} &middot; {{ $item['created_at']->diffForHumans() }}</p>
                            </div>

                            <span class="pa-badge" style="background-color: {{ $hex }}1A; color: {{ $hex }};">
                                {{ $item['label'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
