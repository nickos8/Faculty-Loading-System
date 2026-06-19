@php
    /** @var \App\Models\User $user */
@endphp

<section class="space-y-4">

    {{-- Verification pill (nice UX) --}}
    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail)
        <div class="flex flex-wrap gap-2 text-xs">
            @if ($user->hasVerifiedEmail())
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-800">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Email Verified
                </span>
            @else
                <span class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-rose-800">
                    <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                    Email Not Verified
                </span>

                <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                    @csrf
                </form>

                <button form="send-verification"
                        class="text-xs font-medium underline text-slate-600 hover:text-slate-900">
                    Resend verification email
                </button>
            @endif
        </div>
    @endif

    {{-- Read-only fields --}}
    <div class="space-y-5">

        <div class="space-y-1">
            <x-input-label for="school_id" :value="__('School ID')" class="text-xs font-semibold text-slate-700" />
            <x-text-input id="school_id"
                          type="text"
                          class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-100 text-slate-900 shadow-sm"
                          :value="$user->school_id"
                          disabled />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
                <x-input-label for="first_name" :value="__('First Name')" class="text-xs font-semibold text-slate-700" />
                <x-text-input id="first_name"
                              type="text"
                              class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-100 text-slate-900 shadow-sm"
                              :value="$user->first_name"
                              disabled />
            </div>

            <div class="space-y-1">
                <x-input-label for="last_name" :value="__('Last Name')" class="text-xs font-semibold text-slate-700" />
                <x-text-input id="last_name"
                              type="text"
                              class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-100 text-slate-900 shadow-sm"
                              :value="$user->last_name"
                              disabled />
            </div>
        </div>

        <div class="space-y-1">
            <x-input-label for="email_readonly" :value="__('Email')" class="text-xs font-semibold text-slate-700" />
            <x-text-input id="email_readonly"
                          type="email"
                          class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-100 text-slate-900 shadow-sm"
                          :value="$user->email"
                          disabled />
        </div>

    </div>
</section>
