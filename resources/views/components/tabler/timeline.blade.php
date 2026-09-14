@props(['items' => []])
<ul class="steps steps-vertical">@foreach($items as $item)<li class="step-item {{ ($item['complete'] ?? false) ? 'active' : '' }}"><div class="h4 m-0">{{ $item['title'] }}</div>@if(isset($item['description']))<div class="text-secondary">{{ $item['description'] }}</div>@endif</li>@endforeach</ul>
