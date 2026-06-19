<section class="space-y-4">
    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div class="space-y-1">
            <x-input-label for="update_password_current_password" :value="__('Current Password')" class="text-xs font-semibold text-slate-700" />
            <x-text-input id="update_password_current_password"
                          name="current_password"
                          type="password"
                          autocomplete="current-password"
                          class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                 focus:border-slate-300 focus:ring-slate-900/10" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1 text-xs" />
        </div>

        <div class="space-y-1">
            <x-input-label for="update_password_password" :value="__('New Password')" class="text-xs font-semibold text-slate-700" />
            <x-text-input id="update_password_password"
                          name="password"
                          type="password"
                          autocomplete="new-password"
                          class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                 focus:border-slate-300 focus:ring-slate-900/10" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1 text-xs" />
        </div>

        <div class="space-y-1">
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" class="text-xs font-semibold text-slate-700" />
            <x-text-input id="update_password_password_confirmation"
                          name="password_confirmation"
                          type="password"
                          autocomplete="new-password"
                          class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                 focus:border-slate-300 focus:ring-slate-900/10" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1 text-xs" />
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
            <div class="text-xs text-slate-500">
                Use a long password with letters, numbers, and symbols.
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button class="rounded-xl">
                    {{ __('Save Changes') }}
                </x-primary-button>

                @if (session('status') === 'password-updated')
                    <p x-data="{ show: true }"
                       x-show="show"
                       x-transition
                       x-init="setTimeout(() => show = false, 2000)"
                       class="text-sm text-emerald-600">
                        {{ __('Password updated.') }}
                    </p>
                @endif
            </div>
        </div>
    </form>
</section>
