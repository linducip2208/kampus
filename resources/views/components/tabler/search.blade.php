@props(['name' => 'search', 'value' => null, 'placeholder' => 'Cari data…'])
<div class="input-icon"><span class="input-icon-addon"><i class="ti ti-search"></i></span><input type="search" name="{{ $name }}" value="{{ $value }}" placeholder="{{ $placeholder }}" {{ $attributes->class(['form-control']) }} autocomplete="off"></div>
