@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="pag-disabled" aria-disabled="true">
                <i class="bi bi-chevron-left"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior">
                <i class="bi bi-chevron-left"></i>
            </a>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pag-dots">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pag-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente">
                <i class="bi bi-chevron-right"></i>
            </a>
        @else
            <span class="pag-disabled" aria-disabled="true">
                <i class="bi bi-chevron-right"></i>
            </span>
        @endif

    </nav>
@endif
