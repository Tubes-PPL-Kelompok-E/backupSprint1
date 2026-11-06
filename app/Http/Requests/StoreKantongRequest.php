<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKantongRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dibatasi middleware role:admin_pmi
    }

    protected function prepareForValidation(): void
    {
        $kode = strtoupper(trim((string) $this->input('kode_batch')));
        $this->merge(['kode_batch' => $kode === '' ? null : $kode]);
    }

    public function rules(): array
    {
        return [
            'kode_batch'    => ['nullable', 'regex:/^[A-Z0-9\-]{5,40}$/', 'unique:kantong_darah,kode_batch'],
            'tanggal_donor' => ['required', 'date', 'before_or_equal:today'],
            'volume_ml'     => ['required', 'integer', 'min:50', 'max:1000'],
            'komponen'      => ['required', 'in:prc,wb,tc,ffp'],
            'golongan'      => ['required', 'in:A,B,AB,O'],
            'rhesus'        => ['required', 'in:pos,neg'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_batch.regex'             => 'Kode batch hanya boleh huruf besar, angka, dan tanda minus.',
            'kode_batch.unique'            => 'Kode batch sudah dipakai.',
            'tanggal_donor.before_or_equal' => 'Tanggal donor tidak boleh di masa depan.',
            'volume_ml.min'                => 'Volume minimal 50 ml.',
            'volume_ml.max'                => 'Volume maksimal 1000 ml.',
        ];
    }
}
