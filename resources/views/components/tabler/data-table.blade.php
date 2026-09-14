@props(['caption' => null, 'striped' => false])
<div {{ $attributes->class(['table-responsive']) }}><table class="table table-vcenter {{ $striped ? 'table-striped' : '' }}">@if($caption)<caption class="visually-hidden">{{ $caption }}</caption>@endif{{ $slot }}</table></div>
