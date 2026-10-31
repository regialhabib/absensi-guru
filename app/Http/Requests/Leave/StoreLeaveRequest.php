<?php

namespace App\Http\Requests\Leave;

use App\Enums\LeaveType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(LeaveType::class)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Jenis izin wajib dipilih.',
            'type.enum' => 'Jenis izin tidak valid.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.date' => 'Tanggal mulai bukan tanggal yang valid.',
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.date' => 'Tanggal selesai bukan tanggal yang valid.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'reason.required' => 'Alasan wajib diisi.',
            'reason.max' => 'Alasan maksimal :max karakter.',
            'attachment.file' => 'Lampiran harus berupa file.',
            'attachment.uploaded' => 'Lampiran gagal diunggah. Pastikan ukurannya maksimal 5 MB.',
            'attachment.mimes' => 'Lampiran harus berupa file PDF, JPG, atau PNG.',
            'attachment.max' => 'Ukuran lampiran maksimal 5 MB.',
        ];
    }
}
