@props(['paginator'])
@if($paginator->hasPages())<div {{ $attributes->class(['d-flex', 'justify-content-between', 'align-items-center', 'flex-wrap', 'gap-2']) }}><div class="text-secondary small">Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}</div>{{ $paginator->onEachSide(1)->links() }}</div>@endif
