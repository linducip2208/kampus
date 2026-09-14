@props(['value' => 0, 'max' => 100, 'color' => 'primary', 'label' => null])
@php($percent = $max > 0 ? min(100, max(0, ($value / $max) * 100)) : 0)
@if($label)<div class="d-flex justify-content-between mb-1"><span>{{ $label }}</span><span class="text-secondary">{{ number_format($percent) }}%</span></div>@endif<div class="progress" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="{{ $max }}"><div class="progress-bar bg-{{ $color }}" style="width: {{ $percent }}%"></div></div>
