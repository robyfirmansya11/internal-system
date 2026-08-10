@php use App\Services\ApprovalDashboardService as Dash; @endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Recent Activity
        </x-slot>

        @php $items = $this->getItems(); @endphp

        <style>
            .ra-scroll {
                max-height: 300px;
                overflow-y: auto;
                scrollbar-width: thin;
                scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
            }
            .dark .ra-scroll { scrollbar-color: rgba(255, 255, 255, 0.15) transparent; }
            .ra-scroll::-webkit-scrollbar { width: 6px; }
            .ra-scroll::-webkit-scrollbar-track { background: transparent; }
            .ra-scroll::-webkit-scrollbar-thumb {
                background-color: rgba(0, 0, 0, 0.2);
                border-radius: 999px;
            }
            .dark .ra-scroll::-webkit-scrollbar-thumb { background-color: rgba(255, 255, 255, 0.15); }

            .ra-list { display: flex; flex-direction: column; gap: 2px; }

            .ra-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 8px;
                margin: 0 -8px;
                border-radius: 10px;
                text-decoration: none;
                transition: background-color .15s ease;
            }
            .ra-item:hover { background-color: rgba(0, 0, 0, 0.04); }
            .dark .ra-item:hover { background-color: rgba(255, 255, 255, 0.05); }

            .ra-icon {
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 32px;
                height: 32px;
                border-radius: 999px;
            }
            .ra-icon svg { width: 16px; height: 16px; }

            .ra-body { min-width: 0; flex: 1; }
            .ra-title {
                font-size: 14px;
                color: #111827;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .dark .ra-title { color: #f3f4f6; }
            .ra-title strong { font-weight: 500; }
            .ra-meta { font-size: 12px; color: #6b7280; margin-top: 2px; }
            .dark .ra-meta { color: #9ca3af; }

            .ra-badge {
                flex-shrink: 0;
                border-radius: 999px;
                padding: 4px 10px;
                font-size: 11px;
                font-weight: 500;
            }

            .ra-empty {
                text-align: center;
                padding: 16px 0;
                font-size: 14px;
                color: #6b7280;
            }
            .dark .ra-empty { color: #9ca3af; }
        </style>

        @if($items->isEmpty())
            <p class="ra-empty">No recent activity yet.</p>
        @else
            <div class="ra-scroll">
                <div class="ra-list">
                    @foreach($items as $item)
                        @php $hex = Dash::colorHex($item['color']); @endphp

                        <a href="{{ $item['url'] }}" class="ra-item">
                            <span class="ra-icon" style="background-color: {{ $hex }}1A;">
                                <x-filament::icon :icon="$item['icon']" style="color: {{ $hex }};" />
                            </span>

                            <div class="ra-body">
                                <p class="ra-title">
                                    <strong>{{ $item['requester'] }}</strong> — {{ $item['title'] }}
                                </p>
                                <p class="ra-meta">{{ $item['label'] }} &middot; {{ $item['updated_at']->diffForHumans() }}</p>
                            </div>

                            <span class="ra-badge" style="background-color: {{ $hex }}1A; color: {{ $hex }};">
                                {{ $item['status'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
