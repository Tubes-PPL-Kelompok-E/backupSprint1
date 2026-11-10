<?php

namespace App\Http\Requests;

use App\Models\KantongDarah;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Dipakai untuk kantong baru (store) maupun mengubah kantong berstatus Karantina (update). */
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
        $ini = $this->route('kantong'); // terisi saat update

        return [
            'kode_batch'    => ['nullable', 'regex:/^[A-Z0-9\-]{5,40}$/', Rule::unique('kantong_darah', 'kode_batch')->ignore($ini?->id)],
            'tanggal_donor' => ['bail', 'required', 'date', 'before_or_equal:today', function ($attribute, $value, $fail) {
                $masa = KantongDarah::MASA_SIMPAN[$this->input('komponen')] ?? null;
                if ($masa && Carbon::parse($value)->addDays($masa)->lt(today())) {
                    $fail("Kantong sudah melewati masa simpan {$masa} hari sejak tanggal donor dan tidak dapat diterima.");
                }
            }],
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
