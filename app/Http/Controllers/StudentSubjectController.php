<?php

namespace App\Http\Controllers;

use App\Models\StudentSubject;
use Illuminate\Http\Request;

class StudentSubjectController extends Controller
{
    public function store(Request $request)
    {
        StudentSubject::create($request->all());
        return redirect()->route('student-subjects.index');
    }
}
