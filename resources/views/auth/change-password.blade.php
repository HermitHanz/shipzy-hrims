<x-layouts.guest title="Change password">
    <h1 class="text-lg font-semibold text-gray-900">Change your password</h1>

    @if ($forced)
        <p class="mt-2 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800" role="alert">
            You're using a temporary password. Please choose a new one to continue.
        </p>
    @endif

    <form method="POST" action="{{ route('password.change.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700">Current password</label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            @error('current_password')
                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">New password</label>
            @php $min = (int) \App\Support\Settings\Settings::value('security.password_min_length', 12); @endphp
            <x-ui.input id="password" name="password" type="password" :hint="'At least ' . $min . ' characters.'" :minlength="$min" required autocomplete="new-password"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            <p class="mt-1 text-xs text-gray-500">At least 12 characters, with upper and lower case letters and a number.</p>
            @error('password')
                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
        </div>

        <div class="flex items-center justify-between gap-x-4">
            @unless ($forced)
                <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
            @endunless

            <button type="submit"
                    class="ml-auto rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Update password
            </button>
        </div>
    </form>

    @if ($forced)
        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-xs text-gray-500 hover:text-gray-700">Sign out instead</button>
        </form>
    @endif
</x-layouts.guest>