<?php

namespace App\Http\Requests\Admin;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $teacher = $this->route('teacher');
        $ignoreId = $teacher instanceof User ? $teacher->id : null;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($ignoreId)],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique(User::class)->ignore($ignoreId)],
            'phone' => ['nullable', 'string', 'max:30'],
        ];

        if ($this->isMethod('POST')) {
            $rules['password'] = $this->passwordRules();
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'nip' => 'NIP',
            'phone' => 'nomor telepon',
            'password' => 'kata sandi',
        ];
    }
}
