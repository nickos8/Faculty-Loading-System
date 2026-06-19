<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

            {{-- HEADER (Subjects style) --}}
            <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
                <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Add Room</h1>
                        <p class="text-sm text-slate-600">Create a room with capacity and daily availability hours.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        @if(\Illuminate\Support\Facades\Route::has('rooms.index'))
                            <a href="{{ route('rooms.index') }}"
                               class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                                Back to Rooms
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- GLOBAL ERRORS (dashboard style) --}}
            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
                    <div class="font-semibold mb-1">Please fix the following:</div>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- FORM CARD --}}
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
                <form method="POST" action="{{ route('rooms.store') }}" class="p-6 space-y-6">
                    @csrf

                    {{-- TIP --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                        <span class="font-semibold text-slate-900">Tip:</span>
                        Set realistic daily hours—this helps the scheduler avoid invalid time placements.
                    </div>

                    {{-- ROOM DETAILS --}}
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Room Details</h3>
                                <p class="text-xs text-slate-500 mt-1">Use a clear name (e.g., “Room 301” or “Lab A”).</p>
                            </div>
                            <div class="text-xs text-slate-500">
                                Required fields are marked <span class="text-rose-600 font-medium">*</span>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="name" class="block text-xs font-medium text-slate-500 mb-1">
                                    Room Name <span class="text-rose-600">*</span>
                                </label>

                                <x-text-input
                                    id="name"
                                    name="name"
                                    type="text"
                                    class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                           focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                                    value="{{ old('name') }}"
                                    required
                                />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <label for="capacity" class="block text-xs font-medium text-slate-500 mb-1">
                                    Capacity <span class="text-rose-600">*</span>
                                </label>

                                <x-text-input
                                    id="capacity"
                                    name="capacity"
                                    type="number"
                                    min="1"
                                    class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                           focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                                    value="{{ old('capacity') }}"
                                    required
                                />
                                <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="description" class="block text-xs font-medium text-slate-500 mb-1">
                                Description
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                       focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                                placeholder="Optional notes (e.g., Projector available, Computer lab)"
                            >{{ old('description') }}</textarea>

                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                    </div>

                    {{-- AVAILABILITY --}}
                    <div class="pt-2 border-t border-slate-100">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Daily Availability</h3>
                                <p class="text-xs text-slate-500 mt-1">This sets the time window the room can be scheduled each day.</p>
                            </div>
                            <span class="text-xs text-slate-500">Required</span>
                        </div>

                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="daily_start_time" class="block text-xs font-medium text-slate-500 mb-1">
                                    Daily Start Time <span class="text-rose-600">*</span>
                                </label>

                                <input
                                    id="daily_start_time"
                                    name="daily_start_time"
                                    type="time"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                           focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                                    value="{{ old('daily_start_time','08:00') }}"
                                    required
                                >
                                <x-input-error :messages="$errors->get('daily_start_time')" class="mt-2" />
                            </div>

                            <div>
                                <label for="daily_end_time" class="block text-xs font-medium text-slate-500 mb-1">
                                    Daily End Time <span class="text-rose-600">*</span>
                                </label>

                                <input
                                    id="daily_end_time"
                                    name="daily_end_time"
                                    type="time"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                           focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                                    value="{{ old('daily_end_time','17:00') }}"
                                    required
                                >
                                <x-input-error :messages="$errors->get('daily_end_time')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- ACTIONS --}}
                    <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        @if(\Illuminate\Support\Facades\Route::has('rooms.index'))
                            <a href="{{ route('rooms.index') }}"
                               class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                                Cancel
                            </a>
                        @endif

                        <x-primary-button class="px-4 py-2 rounded-xl">
                            Save Room
                        </x-primary-button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
