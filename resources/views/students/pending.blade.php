@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-4">
    <h1 class="text-xl font-semibold mb-4">Pending Freshmen Applicants</h1>

    @if (session('status'))
      <div class="mb-4 rounded bg-green-50 p-3 text-green-700">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
      <div class="mb-4 rounded bg-red-50 p-3 text-red-700">
        <ul class="list-disc ml-5">
          @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @forelse ($pending as $u)
      <div class="mb-3 rounded border p-3">
        <div class="font-semibold">{{ $u->first_name ?? $u->name }} {{ $u->last_name ?? '' }}</div>
        <div class="text-sm text-gray-600">
          Email: {{ $u->email }} • Program ID: {{ $u->program_id }}
        </div>

        <form method="POST" action="{{ route('students.approve.freshman') }}" class="mt-2">
          @csrf
          <input type="hidden" name="user_id" value="{{ $u->id }}">
          <button class="rounded bg-green-600 px-3 py-2 text-white">Approve as Freshman</button>
        </form>
      </div>
    @empty
      <p class="text-sm text-gray-600">No pending freshmen.</p>
    @endforelse

    <div class="mt-4">
      {{ $pending->links() }}
    </div>
</div>
@endsection
