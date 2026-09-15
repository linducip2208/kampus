@props(['paginator'])
@if($paginator->hasPages())
@php
    $start = max(1, $paginator->currentPage() - 2);
    $end = min($paginator->lastPage(), $paginator->currentPage() + 2);
@endphp
<div {{ $attributes->class(['d-flex', 'justify-content-between', 'align-items-center', 'flex-wrap', 'gap-2']) }}>
    <div class="text-secondary small">Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}</div>
    <ul class="pagination m-0">
        <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}"><a class="page-link" href="{{ $paginator->previousPageUrl() ?? '' }}" aria-label="Halaman sebelumnya"><i class="ti ti-chevron-left"></i></a></li>
        @foreach($paginator->getUrlRange($start, $end) as $page => $url)<li class="page-item {{ $page === $paginator->currentPage() ? 'active' : '' }}"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>@endforeach
        <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}"><a class="page-link" href="{{ $paginator->nextPageUrl() ?? '' }}" aria-label="Halaman berikutnya"><i class="ti ti-chevron-right"></i></a></li>
    </ul>
</div>
@endif