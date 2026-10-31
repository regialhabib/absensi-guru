<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'between:2020,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ];
    }

    public function year(): int
    {
        return $this->integer('year', now()->year);
    }

    public function month(): int
    {
        return $this->integer('month', now()->month);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['year' => 'tahun', 'month' => 'bulan'];
    }
}
