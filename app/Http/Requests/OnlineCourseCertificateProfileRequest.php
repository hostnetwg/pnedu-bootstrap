<?php

namespace App\Http\Requests;

use App\Models\OnlineCourseEnrollment;
use Illuminate\Foundation\Http\FormRequest;

class OnlineCourseCertificateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enrollment = $this->route('enrollment');

        return $enrollment instanceof OnlineCourseEnrollment
            && $this->user() !== null
            && $enrollment->emailMatchesUser($this->user()->email ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var OnlineCourseEnrollment $enrollment */
        $enrollment = $this->route('enrollment');
        $course = $enrollment->onlineCourse;

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ];

        if ($course && $course->certificate_collect_birth_data) {
            if ($course->certificate_birth_data_required) {
                $rules['birth_date'] = ['required', 'date', 'before:today'];
                $rules['birth_place'] = ['required', 'string', 'max:255'];
            } else {
                $rules['birth_date'] = ['nullable', 'date', 'before:today'];
                $rules['birth_place'] = ['nullable', 'string', 'max:255'];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Podaj imię — trafi ono na zaświadczenie.',
            'last_name.required' => 'Podaj nazwisko — trafi ono na zaświadczenie.',
        ];
    }
}
