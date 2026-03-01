<div class="w-96 bg-white rounded-lg shadow-lg p-4">
    <h3 class="font-semibold mb-3">{{ __('ui.my_help') }}</h3>
    @if(empty($assigned))
        <div class="text-sm text-gray-500">{{ __('ui.no_assigned') }}</div>
    @else
        <ul class="space-y-2">
            @foreach($assigned as $r)
                <li class="p-2 border rounded flex items-center justify-between">
                    <div>
                        <div class="font-medium">{{ $r['title'] }}</div>
                        <div class="text-xs text-gray-500">{{ __('requests.status') }}: {{ $r['status'] }}</div>
                        <div class="text-xs text-gray-600">{{ __('ui.requested_by') }}: {{ $r['requester_name'] }} — {{ $r['requester_contact'] }}</div>
                    </div>
                    <div>
                        <button class="text-xs text-indigo-600">{{ __('ui.view') }}</button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
