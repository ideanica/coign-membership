@props(['paginator'])
<div class="portal-pagination"><p>{{ $paginator->total() ? $paginator->firstItem().'–'.$paginator->lastItem().' '.__('dari').' '.$paginator->total().' '.__('data') : __('Belum ada data') }}</p>
    @if($paginator->hasPages())
        <nav class="inline-actions" aria-label="{{ __('Halaman data') }}">
            @if($paginator->onFirstPage())<span class="page-button" aria-disabled="true"><x-icon name="chevron-left" size="16" /></span>@else<a class="page-button" href="{{ $paginator->previousPageUrl() }}" aria-label="{{ __('Halaman sebelumnya') }}"><x-icon name="chevron-left" size="16" /></a>@endif
            <span class="badge">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            @if($paginator->hasMorePages())<a class="page-button" href="{{ $paginator->nextPageUrl() }}" aria-label="{{ __('Halaman berikutnya') }}"><x-icon name="chevron-right" size="16" /></a>@else<span class="page-button" aria-disabled="true"><x-icon name="chevron-right" size="16" /></span>@endif
        </nav>
    @endif
</div>
