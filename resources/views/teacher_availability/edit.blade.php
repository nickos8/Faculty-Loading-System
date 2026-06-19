@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Edit Availability</h1>
        <form action="{{ route('teacher_availability.update', $availability->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="start_time">Start Time</label>
                <input type="time" name="start_time" class="form-control" value="{{ $availability->start_time }}" required>
            </div>
            <div class="form-group">
                <label for="end_time">End Time</label>
                <input type="time" name="end_time" class="form-control" value="{{ $availability->end_time }}" required>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
@endsection
