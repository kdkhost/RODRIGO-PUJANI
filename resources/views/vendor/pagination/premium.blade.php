@if ($paginator->hasPages())
    <nav class="admin-pagination-wrapper" role="navigation" aria-label="Paginação">
        <div class="admin-pagination-info">
            Mostrando <strong>{{ $paginator->firstItem() }}</strong> até <strong>{{ $paginator->lastItem() }}</strong> de <strong>{{ $paginator->total() }}</strong> resultados
        </div>

        <ul class="admin-pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="admin-pagination-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="admin-pagination-link" aria-hidden="true">
                        <i class="bi bi-chevron-left"></i>
                    </span>
                </li>
            @else
                <li class="admin-pagination-item">
                    <a class="admin-pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="admin-pagination-item disabled" aria-disabled="true"><span class="admin-pagination-link">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="admin-pagination-item active" aria-current="page"><span class="admin-pagination-link">{{ $page }}</span></li>
                        @else
                            <li class="admin-pagination-item"><a class="admin-pagination-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="admin-pagination-item">
                    <a class="admin-pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            @else
                <li class="admin-pagination-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="admin-pagination-link" aria-hidden="true">
                        <i class="bi bi-chevron-right"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
