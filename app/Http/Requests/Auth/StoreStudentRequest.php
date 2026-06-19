<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // We already restrict via route middleware, but this keeps it explicit.
        return $this->user() && $this->user()->hasRole('program_admin');
    }

    public function rules(): array
    {
        return [
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'phone_number'  => ['required', 'string', 'max:20'],
            'address'       => ['required', 'string'],
            'gender'        => ['required', 'in:Male,Female,Other'],

            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'school_id'     => ['required', 'string', 'max:255'], // consider unique if policy requires.

            'password'      => ['required', 'string', 'min:8', 'confirmed'],

            // student_academics fields
            'section_id'        => ['nullable', 'integer', 'exists:sections,id'],
            'academic_status'   => ['nullable', 'in:regular,irregular'],
            // enrollment_status will default to 'enrolled' in controller
        ];
    }

    public function messages(): array
    {
        return [
            'gender.in' => 'Gender must be one of: Male, Female, Other.',
        ];
    }
}
