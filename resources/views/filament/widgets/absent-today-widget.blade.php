<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div style="display:flex; align-items:center; gap:8px;">
                <span>Employees Pending Check-In</span>
                @php $count = $this->getAbsentUsers()->count(); @endphp
                @if($count > 0)
                    <span style="
                        background: #ef44441A;
                        color: #ef4444;
                        border-radius: 999px;
                        padding: 2px 10px;
                        font-size: 12px;
                        font-weight: 600;
                    ">{{ $count }} Employee's</span>
                @else
                    <span style="
                        background: #22c55e1A;
                        color: #22c55e;
                        border-radius: 999px;
                        padding: 2px 10px;
                        font-size: 12px;
                        font-weight: 600;
                    ">All Employees Present ✅ ✓</span>
                @endif
            </div>
        </x-slot>

        @php $users = $this->getAbsentUsers(); @endphp

        @if($users->isEmpty())
            <div style="
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 32px 0;
                text-align: center;
            ">
                <x-filament::icon
                    icon="heroicon-o-check-circle"
                    style="width:48px; height:48px; color:#22c55e; margin-bottom:12px;"
                />
                <p style="font-size:15px; font-weight:600; color:#22c55e;">
                    All employees have checked in today.
                </p>
                <p style="font-size:13px; color:#6b7280; margin-top:4px;">
                    {{ today()->translatedFormat('l, d F Y') }}
                </p>
            </div>
        @else
            <div style="
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 10px;
                margin-top: 4px;
            ">
                @foreach($users as $user)
                    <div style="
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        padding: 10px 12px;
                        background: rgba(239, 68, 68, 0.05);
                        border: 1px solid rgba(239, 68, 68, 0.15);
                        border-radius: 10px;
                    ">
                        {{-- Avatar inisial --}}
                        <div style="
                            width: 36px;
                            height: 36px;
                            border-radius: 50%;
                            background: rgba(239, 68, 68, 0.15);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 13px;
                            font-weight: 700;
                            color: #ef4444;
                            flex-shrink: 0;
                        ">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>

                        <div style="min-width:0;">
                            {{-- Ganti inline style warna → pakai class Tailwind + dark --}}
                            <p class="text-sm font-semibold truncate text-gray-800 dark:text-gray-100">
                                {{ $user->name }}
                            </p>
                            <p class="text-xs truncate text-gray-500 dark:text-gray-400" style="margin-top:2px;">
                                {{ $user->departments->first()?->nama_department ?? $user->jabatan ?? '-' }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
