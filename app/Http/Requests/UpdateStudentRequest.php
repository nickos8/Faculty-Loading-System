<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // We rely on route middleware + controller-level checks.
        return true;
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user'); // from {user} in route

        return [
        'first_name'        => ['required','string','max:255'],
        'last_name'         => ['required','string','max:255'],
        'phone_number'      => ['required','string','max:50'],
        'address'           => ['required','string'],
        'gender'            => ['required','in:Male,Female,Other'],
        'email'             => ['required','email','max:255'],
        'school_id'         => ['required','string','max:255'],
        'status'            => ['required','in:active,inactive'], // or your enum
        'program_id'        => ['required','integer','exists:programs,id'], // 👈 NEW
        'section_id'        => ['nullable','integer','exists:sections,id'],
        'academic_status'   => ['required','in:regular,irregular'],
        'enrollment_status' => ['required','in:enrolled,dropped,graduated'], // adjust to your values
    ];
    }
}
