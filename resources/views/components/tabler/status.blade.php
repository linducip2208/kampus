@props(['value'])
@php
    $map = ['active'=>'green','paid'=>'green','approved'=>'green','published'=>'blue','open'=>'azure','pending'=>'yellow','submitted'=>'yellow','leave'=>'orange','overdue'=>'red','rejected'=>'red','failed'=>'red','inactive'=>'secondary','draft'=>'secondary','locked'=>'purple'];
    $color = $map[strtolower((string) $value)] ?? 'secondary';
@endphp
<span {{ $attributes->class(['badge', "bg-$color-lt", "text-$color"]) }}><span class="status-dot status-dot-animated bg-{{ $color }} me-1"></span>{{ str($value)->replace('_', ' ')->title() }}</span>