<x-app-layout>
    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            {{-- PAGE HEADER --}}
            <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
                <div class="p-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-2xl space-y-1">
                        <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                            Profile
                        </h1>
                        <p class="text-sm text-slate-600">
                            Manage your profile info, email address, and password.
                        </p>
                    </div>
                </div>
            </div>

            {{-- EMAIL VERIFICATION FLASH --}}
            @if (session('status') === 'verification-link-sent')
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
                    <div class="font-semibold">Verification Email Sent</div>
                    <div class="text-sm mt-1">
                        A new verification link has been sent to your email address.
                    </div>
                </div>
            @endif


            {{-- PROFILE INFORMATION --}}
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/60">
                    <div class="text-sm font-semibold text-slate-900">
                        Profile Information
                    </div>
                    <div class="text-xs text-slate-500">
                        Read-only account details.
                    </div>
                </div>

                <div class="p-5">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>


            {{-- EMAIL SETTINGS --}}
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/60">
                    <div class="text-sm font-semibold text-slate-900">
                        Email Address
                    </div>
                    <div class="text-xs text-slate-500">
                        Update the email tied to your account.
                    </div>
                </div>

                <div class="p-5">
                    @include('profile.partials.change-user-email')
                </div>
            </div>


            {{-- PASSWORD --}}
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/60">
                    <div class="text-sm font-semibold text-slate-900">
                        Update Password
                    </div>
                    <div class="text-xs text-slate-500">
                        Use a strong password to keep your account secure.
                    </div>
                </div>

                <div class="p-5">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
