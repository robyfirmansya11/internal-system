@props([
    'isCancelled' => false,
    'isApproved' => false,
    'isRejected' => false,
    'isPending' => true,
    'isChecked' => false,
    'type' => 'standard', // standard | checked | approved
])

@php
    $color = match(true) {
        $isCancelled => '#6b7280',
        $isApproved  => '#16a34a',
        $isRejected  => '#dc2626',
        $isChecked   => '#2563eb',
        default      => '#d97706',
    };

    $text = match(true) {
        $isCancelled => '✘ CANCELLED',
        $isApproved  => '✔ APPROVED',
        $isRejected  => '✘ REJECTED',
        $isChecked   => '✔ CHECKED',
        default      => '⏳ PENDING',
    };
@endphp

<div style="
    position:absolute; top:50%; left:50%;
    transform:translate(-50%,-50%) rotate(-15deg);
    border:3px solid {{ $color }};
    border-radius:6px;
    padding:3px 10px;
    color:{{ $color }};
    font-size:14px;
    font-weight:bold;
    letter-spacing:2px;
    opacity:0.6;
    white-space:nowrap;
">{{ $text }}</div>
