@props(['name', 'label' => null, 'required' => false, 'help' => null])
<div class="mb-3">@if($label)<label class="form-label" for="{{ $name }}">{{ $label }}@if($required)<span class="text-danger" aria-hidden="true">*</span>@endif</label>@endif{{ $slot }}@error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror@if($help)<div class="form-hint">{{ $help }}</div>@endif</div>
