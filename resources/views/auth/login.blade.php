<x-login-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />



    <form method="POST" action="{{ route('login') }}">

        @if (session('error'))
        <div class="text-red-600 bg-red-100 border-l-4 border-red-500 p-4 mt-4">
            {{ session('error') }}
        </div>
        @endif
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

         <!-- Register link -->
        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600">
                Don’t have an account?
                <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-900 font-semibold">
                    Register here
                </a>
            </p>
        </div>

        <div class="mt-4 text-center">
            <x-primary-button class="ms-8">
                {{ __('Log in') }}
            </x-primary-button>
        </div>


        <div class="flex items-center justify-center mt-4">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
        </div>

    </form>
</x-login-layout>
