<section class="space-y-4">
    <form method="post" action="{{ route('profile.email.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div class="space-y-1">
            <x-input-label for="email" :value="__('Email')" class="text-xs font-semibold text-slate-700" />
            <x-text-input id="email"
                          name="email"
                          type="email"
                          autocomplete="email"
                          required
                          :value="old('email', $user->email)"
                          class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                 focus:border-slate-300 focus:ring-slate-900/10" />
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs" />
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
            <div class="text-xs text-slate-500">
                After updating, a verification email may be sent.
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button class="rounded-xl">
                    {{ __('Save Email') }}
                </x-primary-button>

                @if (session('status') === 'email-updated')
                    <p x-data="{ show: true }"
                       x-show="show"
                       x-transition
                       x-init="setTimeout(() => show = false, 2000)"
                       class="text-sm text-emerald-600">
                        {{ __('Email updated.') }}
                    </p>
                @endif
            </div>
        </div>
    </form>
</section>
