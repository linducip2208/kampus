@props(['items' => []])
<nav aria-label="Breadcrumb"><ol class="breadcrumb breadcrumb-arrows">@foreach($items as $label => $url)<li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}" @if($loop->last) aria-current="page" @endif>@if(!$loop->last && $url)<a href="{{ $url }}">{{ $label }}</a>@else{{ $label }}@endif</li>@endforeach</ol></nav>
