@if(isset($breadcrumbs) && count($breadcrumbs) > 0)
<nav class="bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-8 py-3">
    <div class="max-w-7xl mx-auto">
        <ol class="flex items-center space-x-2 text-sm">
            @foreach($breadcrumbs as $title => $url)
                @if($loop->last)
                    <li class="flex items-center">
                        <span class="text-gray-900 font-medium">{{ $title }}</span>
                    </li>
                @else
                    <li class="flex items-center">
                        <a href="{{ $url }}" class="text-indigo-600 hover:text-indigo-800">{{ $title }}</a>
                        <svg class="w-4 h-4 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </li>
                @endif
            @endforeach
        </ol>
    </div>
</nav>
@endif
