<div class="bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[80vh] flex flex-col overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-900 text-base">{{ __('ui.my_help') }}</h3>
        <span class="text-xs text-gray-400">{{ count($assigned) }}</span>
    </div>

    @if(empty($assigned))
        <div class="text-sm text-gray-500 p-6 text-center">
            {{ __('ui.no_assigned') }}
        </div>
    @else
        <ul class="flex-1 overflow-y-auto divide-y divide-gray-100">
            @foreach($assigned as $r)
                <li class="p-4 space-y-2">
                    {{-- Title + status --}}
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-medium text-gray-900 text-sm leading-snug">{{ $r['title'] }}</span>
                        <span @class([
                            'shrink-0 text-xs px-2 py-0.5 rounded-full font-medium',
                            'bg-green-100 text-green-700'  => $r['status'] === 'open',
                            'bg-yellow-100 text-yellow-700'=> $r['status'] === 'in_progress',
                            'bg-gray-100 text-gray-500'    => $r['status'] === 'fulfilled',
                        ])>
                            {{ \App\Models\HelpRequest::statuses()[$r['status']] ?? $r['status'] }}
                        </span>
                    </div>

                    {{-- Category + deadline --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 font-medium">
                            {{ \App\Models\HelpRequest::categories()[$r['category']] ?? $r['category'] }}
                        </span>
                        <span class="text-xs text-gray-400">⏱ {{ __('requests.expires_at') }}: {{ $r['expires_at'] }}</span>
                    </div>

                    {{-- Requester contact --}}
                    <div class="text-xs text-gray-600">
                        <span class="font-medium">{{ __('ui.requested_by') }}:</span>
                        {{ $r['requester_name'] }}
                        @if($r['requester_contact'])
                            &mdash;
                            <span class="font-mono">{{ $r['requester_contact'] }}</span>
                        @endif
                    </div>

                    {{-- Actions --}}
                    @if ($r['status'] !== 'fulfilled')
                        <div>
                            <button wire:click="giveUp({{ $r['id'] }})"
                                    wire:confirm="{{ __('ui.confirm_give_up') }}"
                                    class="text-xs px-2 py-1 rounded bg-red-50 text-red-700 hover:bg-red-100 transition">
                                {{ __('ui.give_up') }}
                            </button>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
