@props(['name', 'label' => null, 'accept' => null, 'required' => false, 'help' => null])
<x-tabler.form-field :name="$name" :label="$label" :required="$required" :help="$help"><input type="file" name="{{ $name }}" id="{{ $name }}" accept="{{ $accept }}" {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }} @required($required)></x-tabler.form-field>
