@php use App\Services\ApprovalDashboardService as Dash; @endphp

<x-filament-widgets::widget>
    @php $actions = $this->getActions(); @endphp

    @if(count($actions) > 0)
        <x-filament::section>
            <x-slot name="heading">
                Quick Actions Menu
            </x-slot>

            <style>
                .qa-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 12px;
                }
                @media (min-width: 640px) {
                    .qa-grid { grid-template-columns: repeat(3, 1fr); }
                }
                @media (min-width: 1024px) {
                    .qa-grid { grid-template-columns: repeat(4, 1fr); }
                }

                .qa-card {
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    text-align: center;
                    padding: 20px 12px;
                    border-radius: 12px;
                    text-decoration: none;
                    background: #ffffff;
                    border: 1px solid rgba(0, 0, 0, 0.08);
                    transition: transform .15s ease, box-shadow .15s ease;
                }
                .dark .qa-card {
                    background: rgba(255, 255, 255, 0.04);
                    border-color: rgba(255, 255, 255, 0.1);
                }
                .qa-card:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
                }
                .dark .qa-card:hover {
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
                }

                .qa-icon-circle {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 48px;
                    height: 48px;
                    border-radius: 999px;
                    transition: transform .15s ease;
                }
                .qa-card:hover .qa-icon-circle { transform: scale(1.1); }
                .qa-icon-circle svg { width: 24px; height: 24px; }

                .qa-label {
                    font-size: 13px;
                    font-weight: 500;
                    color: #374151;
                }
                .dark .qa-label { color: #d1d5db; }

                .qa-accent {
                    position: absolute;
                    left: 16px;
                    right: 16px;
                    bottom: 0;
                    height: 2px;
                    border-radius: 999px;
                    transform: scaleX(0);
                    transition: transform .15s ease;
                }
                .qa-card:hover .qa-accent { transform: scaleX(1); }
            </style>

            <div class="qa-grid">
                @foreach($actions as $action)
                    @php $hex = Dash::colorHex($action['color']); @endphp

                    <a href="{{ $action['url'] }}" class="qa-card">
                        <span class="qa-icon-circle" style="background-color: {{ $hex }}1A;">
                            <x-filament::icon :icon="$action['icon']" style="color: {{ $hex }};" />
                        </span>

                        <span class="qa-label">{{ $action['label'] }}</span>

                        <span class="qa-accent" style="background-color: {{ $hex }};"></span>
                    </a>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
