<div class="fixed inset-0 z-[1500] flex items-center justify-center p-4">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50" wire:click="$dispatch('close')"></div>

    {{-- Modal --}}
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('ui.create_request') }}</h2>
            <button wire:click="$dispatch('close')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Form --}}
        <form wire:submit="submit" class="p-4 space-y-4">
            {{-- Title --}}
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">{{ __('requests.title') }} *</label>
                <input type="text" id="title" wire:model="title" maxlength="100"
                       placeholder="{{ __('requests.title_placeholder') }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('requests.description') }}</label>
                <textarea id="description" wire:model="description" rows="3" maxlength="1000"
                          placeholder="{{ __('requests.description_placeholder') }}"
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Category --}}
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">{{ __('requests.category') }} *</label>
                <select id="category" wire:model="category"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @foreach (\App\Models\HelpRequest::categories() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Contact type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('requests.contact_type') }} *</label>
                <div class="flex gap-3">
                    @foreach (\App\Models\HelpRequest::contactTypes() as $key => $label)
                        <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer">
                            <input type="radio" wire:model.live="contactType" value="{{ $key }}"
                                   class="text-indigo-600 focus:ring-indigo-500">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Contact value --}}
            <div>
                <label for="contactValue" class="block text-sm font-medium text-gray-700 mb-1">{{ __('requests.contact_value') }} *</label>
                <input type="text" id="contactValue" wire:model="contactValue"
                       placeholder="{{ __('requests.contact_value_placeholder_' . $contactType) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                @error('contactValue') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Expiration --}}
            <div>
                <label for="expiresIn" class="block text-sm font-medium text-gray-700 mb-1">{{ __('requests.expires_at') }}</label>
                <select id="expiresIn" wire:model="expiresIn"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="1">{{ __('requests.expires_1h') }}</option>
                    <option value="6">{{ __('requests.expires_6h') }}</option>
                    <option value="24" selected>{{ __('requests.expires_24h') }}</option>
                    <option value="72">{{ __('requests.expires_3d') }}</option>
                    <option value="168">{{ __('requests.expires_7d') }}</option>
                </select>
            </div>

            {{-- Location display --}}
            <div class="text-xs text-gray-400">
                {{ __('requests.location') }}: {{ number_format($lat, 5) }}, {{ number_format($lng, 5) }}
            </div>

            {{-- reCAPTCHA placeholder (rendered only in production) --}}
            @if (!app()->isLocal() && config('localhelp.recaptcha.site_key'))
                <div id="recaptcha-container"
                     x-data
                     x-init="
                        if (typeof grecaptcha !== 'undefined') {
                            grecaptcha.render('recaptcha-container', {
                                sitekey: '{{ config('localhelp.recaptcha.site_key') }}',
                                callback: (token) => $wire.set('recaptchaToken', token)
                            });
                        }
                     ">
                </div>
                @error('recaptchaToken') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            @endif

            {{-- Actions --}}
            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 bg-indigo-600 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-indigo-700 transition shadow-sm disabled:opacity-50"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('ui.save') }}</span>
                    <span wire:loading>{{ __('ui.loading') }}</span>
                </button>
                <button type="button" wire:click="$dispatch('close')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                    {{ __('ui.cancel') }}
                </button>
            </div>
        </form>
    </div>
</div>
