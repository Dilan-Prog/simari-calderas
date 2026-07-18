{{--
    Shared pagination component for every admin dataTable that uses
    Laravel's paginate(). Usage: {{ $items->links('admin.components.pagination') }}

    Laravel automatically supplies $paginator and $elements (via
    Illuminate\Pagination\UrlWindow) to whatever view is passed to
    ->links(), same contract as the built-in pagination views.
--}}
@if ($paginator->hasPages())
    <nav class="admin-pagination" role="navigation" aria-label="Paginación">
        <ul class="admin-pagination-list">
            {{-- First --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span class="admin-pagination-btn disabled" aria-disabled="true">&laquo;</span>
                @else
                    <a href="{{ $paginator->url(1) }}" class="admin-pagination-btn" aria-label="Primera página">&laquo;</a>
                @endif
            </li>

            {{-- Previous --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span class="admin-pagination-btn disabled" aria-disabled="true">&lsaquo;</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="admin-pagination-btn" rel="prev" aria-label="Página anterior">&lsaquo;</a>
                @endif
            </li>

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="admin-pagination-btn admin-pagination-dots" aria-hidden="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li>
                                <span class="admin-pagination-btn active" aria-current="page">{{ $page }}</span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}" class="admin-pagination-btn">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="admin-pagination-btn" rel="next" aria-label="Página siguiente">&rsaquo;</a>
                @else
                    <span class="admin-pagination-btn disabled" aria-disabled="true">&rsaquo;</span>
                @endif
            </li>

            {{-- Last --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->url($paginator->lastPage()) }}" class="admin-pagination-btn" aria-label="Última página">&raquo;</a>
                @else
                    <span class="admin-pagination-btn disabled" aria-disabled="true">&raquo;</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
