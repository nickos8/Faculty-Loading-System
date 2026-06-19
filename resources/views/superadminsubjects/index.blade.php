@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto p-6">

        <h1 class="text-2xl font-semibold text-bice-blue">SUBJECTS</h1>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-non-photo-blue text-indigo-dye px-4 py-3 shadow-sm">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-pale-azure text-indigo-dye px-4 py-3 shadow-sm border border-bice-blue/40">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
             <!-- Search Bar -->
        <form action="{{ route('superadminsubjects.index') }}" method="GET" class="mb-4 flex justify-end gap-4">
            <input type="text" name="search" class="px-4 py-2 border rounded-lg" placeholder="SUBJECT NAME or CODE" value="{{ request()->search }}">
            <button type="submit" class="bg-picton-blue text-white px-4 py-2 rounded-lg">Search</button>
        </form>



        @if($subjects->isEmpty())
            <p class="text-slate-500">No subjects available.</p>
        @else
            <div class="overflow-x-auto rounded-lg border border-bice-blue/30 shadow">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-dye text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">Code</th>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Units</th>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-left">Creator ID</th>
                            <th class="px-4 py-2 text-left">Creator name</th>

                        </tr>
                    </thead>

                    <tbody class="divide-y divide-pale-azure/40">
                        @foreach ($subjects as $subject)
                            <tr class="hover:bg-non-photo-blue/30 transition">
                                <td class="px-4 py-2">{{ $subject->code }}</td>
                                <td class="px-4 py-2">{{ $subject->name }}</td>
                                <td class="px-4 py-2">{{ $subject->units }}</td>
                                <td class="px-4 py-2">{{ $subject->type }}</td>
                                <!-- <td class="px-4 py-2">{} $subject->created_by }}</td> -->
                               <td class="px-4 py-2">{{ $subject->created_by }}</td>
                                    <!-- Display the created_by and creator's name (if exists) with conditional red color -->
                                <!-- <td class="px-4 py-2">{0{ $subject->creator ? $subject->creator->first_name : 'UNKNOWN' }}</td>-->
                                <td class="px-4 py-2">
                                    {{ $subject->creator ? $subject->creator->last_name . ', ' . $subject->creator->first_name : 'UNKNOWN' }}
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
@endsection
