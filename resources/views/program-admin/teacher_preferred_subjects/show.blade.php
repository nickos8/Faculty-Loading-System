@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- FLASH / ERRORS --}}
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="text-sm mt-1">{{ session('success') }}</div>
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

    {{-- HEADER --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    Teacher Preferred Subjects
                </h1>
                <p class="text-sm text-slate-600">
                    {{ $teacher->last_name }}, {{ $teacher->first_name }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('program-admin.teacher-availabilities.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    Back to teachers
                </a>
            </div>
        </div>
    </div>

    {{-- ADD FORM --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <div class="text-sm font-semibold text-slate-900">Add Preferred Subject</div>
            <div class="text-xs text-slate-500 mt-1">
                Select a subject and set how strongly the teacher prefers to teach it.
            </div>
        </div>

        <form method="POST"
              action="{{ route('program-admin.teacher-preferred-subjects.store', $teacher) }}"
              class="p-6 grid gap-4 md:grid-cols-3">
            @csrf

            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-slate-500 mb-1">Subject</label>
                <select id="subject-select"
        name="subject_id"
        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm"
        required>
    <option value="">Search subject...</option>

    @foreach ($subjects as $subject)
        @if (!isset($preferredSubjects[$subject->id]))
            <option value="{{ $subject->id }}">
                {{ $subject->code }} - {{ $subject->name }}
            </option>
        @endif
    @endforeach
</select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Preference Level</label>
                <select name="preference_level"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                        required>
                    <option value="1" {{ old('preference_level') == 1 ? 'selected' : '' }}>Least Preferred</option>
                    <option value="2" {{ old('preference_level', 2) == 2 ? 'selected' : '' }}>Preferred</option>
                    <option value="3" {{ old('preference_level') == 3 ? 'selected' : '' }}>Most Preferred</option>
                </select>
            </div>

            <div class="md:col-span-3 flex justify-end">
                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    Add Preferred Subject
                </button>
            </div>
        </form>
    </div>

    {{-- CURRENT PREFERRED SUBJECTS --}}
    @if($preferredSubjects->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="text-sm font-semibold text-slate-900">No preferred subjects yet</div>
            <div class="mt-1 text-xs text-slate-500">
                Add at least one preferred subject so future auto-draft scheduling can prioritize this teacher.
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Preferred Subject List</div>
                    <div class="text-xs text-slate-500">Update priority or remove a subject.</div>
                </div>
                <div class="text-xs text-slate-500">
                    Total:
                    <span class="font-medium text-slate-700">{{ $preferredSubjects->count() }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3">Subject Code</th>
                            <th class="px-6 py-3">Subject Name</th>
                            <th class="px-6 py-3">Preference Level</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach($preferredSubjects as $item)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-900">
                                        {{ $item->subject->code }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">
                                        {{ $item->subject->name }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <form method="POST"
                                          action="{{ route('program-admin.teacher-preferred-subjects.update', [$teacher, $item]) }}"
                                          class="flex items-center gap-2 justify-start">
                                        @csrf
                                        @method('PATCH')

                                        <select name="preference_level"
                                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs sm:text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                            <option value="1" {{ $item->preference_level == 1 ? 'selected' : '' }}>Least Preferred</option>
                                            <option value="2" {{ $item->preference_level == 2 ? 'selected' : '' }}>Preferred</option>
                                            <option value="3" {{ $item->preference_level == 3 ? 'selected' : '' }}>Most Preferred</option>
                                        </select>

                                        <button type="submit"
                                                class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                            Update
                                        </button>
                                    </form>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <form method="POST"
                                          action="{{ route('program-admin.teacher-preferred-subjects.destroy', [$teacher, $item]) }}"
                                          class="inline"
                                          onsubmit="return confirm('Remove this preferred subject?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-rose-200 bg-rose-50 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new TomSelect('#subject-select', {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        },
        searchField: ['text'],
        placeholder: 'Type subject code or name...',
        maxOptions: 100,
    });
});
</script>
@endsection
