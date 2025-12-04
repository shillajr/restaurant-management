@props([
    'paginator',
    'containerClass' => 'border-t border-gray-200 bg-white px-4 py-3',
    'onEachSide' => null,
    'view' => null,
])

@if ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && $paginator->hasPages())
    <div class="{{ trim($containerClass) }}">
        @php
            $paginatorToRender = $onEachSide === null ? $paginator : $paginator->onEachSide($onEachSide);
        @endphp

        @if ($view)
            {{ $paginatorToRender->links($view) }}
        @else
            {{ $paginatorToRender->links() }}
        @endif
    </div>
@endif
