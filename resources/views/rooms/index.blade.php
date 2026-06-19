<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 space-y-6">

            {{-- PAGE HEADER (Subjects style, not layout header) --}}
            <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
                <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div class="space-y-1">
                        <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                            Rooms
                        </h2>
                        <p class="text-sm text-slate-600">
                            Manage room capacity, status, and daily availability.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('rooms.create') }}"
                           class="px-3 py-2 text-xs font-medium rounded-xl bg-slate-900 text-white hover:bg-slate-800">
                            Add Room
                        </a>
                    </div>
                </div>
            </div>

            {{-- FLASH / ERRORS --}}
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
                    <div class="font-semibold">Success</div>
                    <div class="text-sm mt-1">{{ session('status') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
                    <div class="font-semibold mb-1">Please fix the following:</div>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- CONTENT --}}
            @if($rooms->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center shadow-sm">
                    <div class="text-sm font-semibold text-slate-900">No rooms found</div>
                    <div class="mt-1 text-xs text-slate-500">
                        Create a room to start assigning schedules.
                    </div>

                    <div class="mt-5">
                        <a href="{{ route('rooms.create') }}"
                           class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                            + Add Room
                        </a>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">Room List</div>
                            <div class="text-xs text-slate-500">
                                Edit room details or remove rooms that are no longer used.
                            </div>
                        </div>

                        <div class="text-xs text-slate-500">
                            Showing
                            <span class="font-medium text-slate-700">{{ $rooms->count() }}</span>
                            room(s)
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs sm:text-sm">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-semibold text-slate-600">
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">Capacity</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Availability</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @foreach($rooms as $room)
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-slate-900">
                                                {{ $room->name }}
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $room->capacity }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php($available = $room->status === 'available')
                                            <span class="text-xs px-2 py-1 rounded-full border
                                                {{ $available
                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                    : 'border-slate-200 bg-slate-50 text-slate-700' }}">
                                                {{ ucfirst($room->status) }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-slate-900">
                                                {{ $room->daily_start_time_formatted }}
                                                –
                                                {{ $room->daily_end_time_formatted }}
                                            </div>
                                            <div class="text-xs text-slate-500">Daily window</div>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <a href="{{ route('rooms.edit', $room) }}"
                                                   class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                                    Edit
                                                </a>

                                              {{-- -   <form action="{{ route('rooms.destroy', $room) }}"
                                                      method="POST"
                                                      class="inline"
                                                      onsubmit="return confirm('Delete this room?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-rose-200 bg-rose-50 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                                        Delete
                                                    </button>
                                                </form>--}}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if(method_exists($rooms, 'links'))
                        <div class="px-6 py-4 border-t border-slate-100">
                            {{ $rooms->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
