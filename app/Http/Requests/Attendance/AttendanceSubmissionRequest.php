<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceSubmissionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,webp', 'max:2048'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0'],
            'face_detected' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'photo' => 'swafoto',
            'latitude' => 'lintang',
            'longitude' => 'bujur',
            'accuracy' => 'akurasi lokasi',
            'face_detected' => 'deteksi wajah',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'file' => ':attribute harus berupa berkas.',
            'image' => ':attribute harus berupa gambar.',
            'mimes' => ':attribute harus berformat JPG, JPEG, atau WEBP.',
            'photo.max' => ':attribute tidak boleh lebih dari 2 MB.',
            'numeric' => ':attribute harus berupa angka.',
            'between' => ':attribute berada di luar rentang yang valid.',
            'min' => ':attribute tidak valid.',
            'boolean' => ':attribute tidak valid.',
        ];
    }
}
