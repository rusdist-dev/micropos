<x-layouts.guest title="Masuk">
    <div class="mb-6 text-center">
        <h1 class="text-xl font-bold text-gray-900">Selamat Datang</h1>
        <p class="mt-1 text-sm text-gray-500">Masuk untuk melanjutkan ke MicroPOS</p>
    </div>

    {{-- Status sesi --}}
    @if (session('status'))
        <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-ui.input
            type="email" name="email" label="Email" required autofocus
            placeholder="admin@pos.test"
            :value="old('email')"
            :error="$errors->first('email')"
        />

        <x-ui.input
            type="password" name="password" label="Password" required
            placeholder="••••••••"
            :error="$errors->first('password')"
        />

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" name="remember"
                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                <span class="ms-2 text-sm text-gray-600">Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-primary-600 hover:underline" href="{{ route('password.request') }}">
                    Lupa password?
                </a>
            @endif
        </div>

        <x-ui.button type="submit" size="lg" class="w-full">Masuk</x-ui.button>
    </form>

    <div class="mt-6 rounded-lg bg-gray-50 px-4 py-3 text-center text-xs text-gray-500">
        <p class="font-medium text-gray-600">Akun demo (prototype)</p>
        <p class="mt-1">admin@pos.test &middot; kasir@pos.test</p>
        <p>password: <span class="font-mono">password</span></p>
    </div>
</x-layouts.guest>
