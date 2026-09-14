@props(['type' => 'info', 'title' => null, 'dismissible' => false])
<div {{ $attributes->class(['alert', "alert-$type", 'alert-dismissible' => $dismissible]) }} role="alert">
    @if($title)<h4 class="alert-title">{{ $title }}</h4>@endif
    <div class="text-secondary">{{ $slot }}</div>
    @if($dismissible)<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>@endif
</div>
