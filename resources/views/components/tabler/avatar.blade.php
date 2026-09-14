@props(['src' => null, 'alt' => '', 'initials' => null, 'size' => 'md', 'status' => null])
<span {{ $attributes->class(['avatar', "avatar-$size", 'position-relative']) }} @if($src) style="background-image:url('{{ $src }}')" @endif aria-label="{{ $alt }}">
    @unless($src){{ $initials }}@endunless
    @if($status)<span class="badge bg-{{ $status }} position-absolute bottom-0 end-0 p-1 border border-white rounded-circle"><span class="visually-hidden">{{ $status }}</span></span>@endif
</span>
