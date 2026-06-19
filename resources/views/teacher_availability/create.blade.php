@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Create Availability</h1>
        <form action="{{ route('teacher_availability.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="day">Day</label>
                <select name="day" class="form-control" required>
                    @php
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    @endphp

                    @foreach($days as $day)
                        <option value="{{ $day }}"
                                @if(in_array($day, $daysWithAvailability)) disabled @endif>
                            {{ $day }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="start_time">Start Time</label>
                <input type="time" name="start_time" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="end_time">End Time</label>
                <input type="time" name="end_time" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
@endsection 
