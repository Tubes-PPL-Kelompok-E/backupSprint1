<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstansiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dibatasi middleware role:admin_pmi
    }

    protected function prepareForValidation(): void
    {
        $kode = strtoupper(trim((string) $this->input('kode')));
        $this->merge(['kode' => $kode === '' ? null : $kode]);
    }

    public function rules(): array
    {
        return [
            'kode'    => ['nullable', 'regex:/^[A-Z0-9\-]{3,20}$/', 'unique:instansi,kode'],
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
            'kode.regex'    => 'Kode hanya boleh huruf besar, angka, dan tanda minus (3-20 karakter).',
            'kode.unique'   => 'Kode instansi sudah dipakai. Gunakan kode lain.',
            'tipe.in'       => 'Tipe instansi harus PMI atau Rumah Sakit.',
            'telepon.regex' => 'Format nomor telepon tidak valid.',
        ];
    }
}
