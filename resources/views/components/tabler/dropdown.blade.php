@props(['label' => 'Menu', 'icon' => 'ti-dots-vertical', 'align' => 'end'])
<div class="dropdown"><button type="button" {{ $attributes->class(['btn', 'dropdown-toggle']) }} data-bs-toggle="dropdown" aria-expanded="false"><i class="ti {{ $icon }} me-1"></i>{{ $label }}</button><div class="dropdown-menu dropdown-menu-{{ $align }}">{{ $slot }}</div></div>
