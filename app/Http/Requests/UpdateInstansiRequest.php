<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstansiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Kode tidak ikut divalidasi: kode adalah identitas tetap dan tidak bisa diubah
    public function rules(): array
    {
        return [
            'nama'    => ['required', 'string', 'max:150'],
            'tipe'    => ['required', 'in:pmi,rumah_sakit'],
            'alamat'  => ['required', 'string', 'max:500'],
            'kota'    => ['required', 'string', 'max:100'],
            'telepon' => ['nullable', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'email'   => ['nullable', 'email', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipe.in'       => 'Tipe instansi harus PMI atau Rumah Sakit.',
            'telepon.regex' => 'Format nomor telepon tidak valid.',
        ];
    }
}
