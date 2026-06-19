<x-guest-layout>
     <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
    @csrf
<div class="form-grid form-grid-2 mb-4">





        <!-- First Name -->
<div class="mb-4">
    <label for="first_name" class="block text-sm font-medium text-gray-700">First Naame</label>
    <input type="text"
        name="first_name"
        value="{{ old('first_name') }}"
        required
        pattern="[A-Za-zÑñ\s]+"
        oninput="this.value = this.value.replace(/[^A-Za-zÑñ\s]/g, '');"
        class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm
               focus:ring-indigo-500 focus:border-indigo-500">
    @error('first_name')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<!-- Last Name -->
<div class="mb-4">
    <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
    <input type="text"
        name="last_name"
        value="{{ old('last_name') }}"
        required
        pattern="[A-Za-zÑñ\s]+"
        oninput="this.value = this.value.replace(/[^A-Za-zÑñ\s]/g, '');"
        class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm
               focus:ring-indigo-500 focus:border-indigo-500">
    @error('last_name')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<!-- Phone Number -->
<div class="mb-4">
    <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
    <input type="text"
        name="phone_number"
        value="{{ old('phone_number') }}"
        required
        pattern="[0-9]+"
        oninput="this.value = this.value.replace(/[^0-9]/g, '');"
        maxlength="11"
        class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm
               focus:ring-indigo-500 focus:border-indigo-500">
    @error('phone_number')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

  <!-- Email Address -->
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
</div>



<!-- Gender -->
<div class="mb-4">
    <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
    <select name="gender" required
        class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
        <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
    </select>
    @error('gender')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
<!-- Address -->
<div class="mb-4">
    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
    <textarea name="address" required
        class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('address') }}</textarea>
    @error('address')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>



<div class="form-grid form-grid-2 mb-4">

        <!-- Program Dropdown -->
        <div class="mb-4">
            <label for="program_id" class="block text-sm font-medium text-gray-700">{{ __('Program') }}</label>
            <select name="program_id" id="program_id" class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                <option value="">Select Program</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}" {{ old('program_id') == $program->id ? 'selected' : '' }}>
                        {{ $program->program_name }}
                    </option>
                @endforeach
            </select>
            @error('program_id')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- School ID (GC- prefix fixed) -->
  <div class="mb-4">
    <label for="school_id" class="block text-sm font-medium text-gray-700">
        {{ __('School ID') }}
    </label>

    <div class="mt-1 flex rounded-md shadow-sm">
        {{-- Fixed prefix that user cannot delete --}}
        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
            GC-
        </span>

        {{-- User only types numbers here --}}
        <input
            id="school_id"
            type="text"
            name="school_id"
            value="{{ old('school_id') }}"
            inputmode="numeric"
            pattern="[0-9]*"
            class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 p-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Leave blank if not yet assigned"
        >
    </div>

    @error('school_id')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

        <!-- Role Selection -->
        <div class="mb-4">
            <label for="role" class="block text-sm font-medium text-gray-700">{{ __('Role') }}</label>
            <select id="role" name="role" class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required onchange="toggleAvailabilityFields()">
                <option value="">Select Role</option>
                @foreach ($roles as $role)
                    @if ($role->name !== 'super_admin') <!-- Exclude Super Admin -->
                        <option value="{{ $role->id }}" {{ old('role') == $role->id ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                        </option>
                    @endif
                @endforeach
            </select>
            @error('role')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4" id="employmentTypeWrapper" style="display:none;">
    <label class="block text-sm font-medium text-gray-700">Employment Type</label>
    <select name="employment_type"
        class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm">
        <option value="regular" {{ old('employment_type') === 'regular' ? 'selected' : '' }}>Regular</option>
        <option value="part_time" {{ old('employment_type') === 'part_time' ? 'selected' : '' }}>Part-time</option>
    </select>
    @error('employment_type')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>


        {{-- Proof Documents (PDF only) --}}
<div class="mb-4">
    <label for="documents" class="block text-sm font-medium text-gray-700">
        Proof Documents (PDF only)
    </label>

    <input
        type="file"
        name="documents[]"
        id="documents"
        accept="application/pdf"
        multiple
        required
        class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm
               focus:ring-indigo-500 focus:border-indigo-500" />

    @error('documents')
      <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error('documents.*')
      <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

        <!-- Password -->
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
            <input id="password" type="password" name="password" required
                class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('Confirm Password') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            @error('password_confirmation')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
</div>





        <!-- Submit Button -->
        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600">
                already have an account?
                <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-900 font-semibold">
                    Log-in here
                </a>
            </p>
            </div>



        <div class="mt-4 text-center">
            <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-md shadow hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                {{ __('Register') }}
            </button>
        </div>


    </form>
    <script>
document.addEventListener('DOMContentLoaded', function () {

    /* ===============================
       PART 1: School ID formatting
    ================================ */
    const form         = document.querySelector('form[action="{{ route('register') }}"]');
    const numericInput = document.getElementById('school_id_numeric');
    const hiddenField  = document.getElementById('school_id_hidden');

    if (form && numericInput && hiddenField) {
        form.addEventListener('submit', function () {
            const raw = (numericInput.value || '').trim();

            hiddenField.value = raw !== ''
                ? 'GC-' + raw
                : '';
        });
    }

    /* ===============================
       PART 2: Employment type toggle
    ================================ */
    const roleSelect = document.getElementById('role');
    const wrapper    = document.getElementById('employmentTypeWrapper');

    const TEACHER_ROLE_ID = 3; // teacher role ID

    function toggleEmploymentType() {
        if (!roleSelect || !wrapper) return;

        wrapper.style.display =
            parseInt(roleSelect.value, 10) === TEACHER_ROLE_ID
                ? 'block'
                : 'none';
    }

    if (roleSelect && wrapper) {
        roleSelect.addEventListener('change', toggleEmploymentType);
        toggleEmploymentType(); // run on page load (handles old() values)
    }

});
</script>




</x-guest-layout>
