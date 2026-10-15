@if ($paginator->hasPages())
    <nav class="{{ $class }}" aria-label="{{ $label ?? 'Navigasi halaman' }}">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true">Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
        @endif
        <small>Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</small>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
        @else
            <span aria-disabled="true">Berikutnya</span>
        @endif
    </nav>
@endif
