<div class="bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[80vh] flex flex-col overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-900 text-base">{{ __('ui.my_needs') }}</h3>
        <span class="text-xs text-gray-400">{{ count($requests) }}</span>
    </div>

    @if(empty($requests))
        <div class="text-sm text-gray-500 p-6 text-center">
            {{ __('ui.no_requests') }}
        </div>
    @else
        <ul class="flex-1 overflow-y-auto divide-y divide-gray-100">
            @foreach($requests as $r)
                <li class="p-4 space-y-2">
                    {{-- Title + status --}}
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-medium text-gray-900 text-sm leading-snug">{{ $r['title'] }}</span>
                        <span @class([
                            'shrink-0 text-xs px-2 py-0.5 rounded-full font-medium',
                            'bg-green-100 text-green-700'   => $r['status'] === 'open',
                            'bg-yellow-100 text-yellow-700' => $r['status'] === 'in_progress',
                            'bg-gray-100 text-gray-500'     => $r['status'] === 'fulfilled',
                        ])>
                            {{ \App\Models\HelpRequest::statuses()[$r['status']] ?? $r['status'] }}
                        </span>
                    </div>

                    {{-- Category + time --}}
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <span @class([
                            'inline-flex items-center px-1.5 py-0.5 rounded',
                            'bg-blue-50 text-blue-700'   => $r['category'] === 'products',
                            'bg-red-50 text-red-700'     => $r['category'] === 'medicine',
                            'bg-purple-50 text-purple-700' => $r['category'] === 'transport',
                            'bg-gray-50 text-gray-600'   => $r['category'] === 'other',
                        ])>
                            {{ \App\Models\HelpRequest::categories()[$r['category']] ?? $r['category'] }}
                        </span>
                        <span>{{ $r['created_at'] }}</span>
                        <span class="text-gray-400">&bull; ⏱ {{ $r['expires_at'] }}</span>
                    </div>

                    {{-- Contact --}}
                    <div class="text-xs text-gray-600">
                        <span class="font-medium">{{ \App\Models\HelpRequest::contactTypes()[$r['contact_type']] ?? $r['contact_type'] }}:</span>
                        <span class="font-mono">{{ $r['contact_value'] }}</span>
                    </div>

                    {{-- Helper info when assigned --}}
                    @if($r['helper_name'])
                        <div class="text-xs text-emerald-700 bg-emerald-50 rounded px-2 py-1">
                            <span class="font-medium">{{ __('ui.helped_by') }}:</span>
                            {{ $r['helper_name'] }}
                            @if($r['helper_contact'])
                                &mdash; <span class="font-mono">{{ $r['helper_contact'] }}</span>
                            @endif
                        </div>
                    @endif

                    {{-- Actions --}}
                    @if ($r['status'] !== 'fulfilled')
                        <div>
                            <button wire:click="markDone({{ $r['id'] }})"
                                    class="text-xs px-2 py-1 rounded bg-green-50 text-green-700 hover:bg-green-100 transition">
                                {{ __('ui.mark_done') }}
                            </button>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
