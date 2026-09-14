@props(['color' => 'secondary', 'pill' => false])
<span {{ $attributes->class(['badge', "bg-$color-lt", "text-$color", 'rounded-pill' => $pill]) }}>{{ $slot }}</span>
