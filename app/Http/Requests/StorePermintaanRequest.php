<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermintaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dibatasi middleware role:admin_pmi
    }

    public function rules(): array
    {
        return [
            // Peminta harus instansi bertipe rumah sakit yang masih aktif
            'instansi_id' => ['required', Rule::exists('instansi', 'id')->where('tipe', 'rumah_sakit')->whereNull('deleted_at')],
            'nama_pasien' => ['required', 'string', 'max:100'],
            'ruang'       => ['nullable', 'string', 'max:100'],
            'no_rm'       => ['required', 'string', 'max:30'],
            'golongan'    => ['required', 'in:A,B,AB,O'],
            'rhesus'      => ['required', 'in:pos,neg'],
            'komponen'    => ['required', 'in:prc,wb,tc,ffp'],
            'jumlah'      => ['required', 'integer', 'min:1', 'max:20'],
            'prioritas'   => ['required', 'in:cito,rutin'],
        ];
    }

    public function messages(): array
    {
        return [
            'instansi_id.required' => 'Pilih rumah sakit peminta.',
            'instansi_id.exists'   => 'Rumah sakit tidak valid. Pilih instansi bertipe Rumah Sakit.',
            'no_rm.required'       => 'Nomor rekam medis wajib diisi.',
            'jumlah.max'           => 'Jumlah maksimal 20 kantong per permintaan.',
        ];
    }
}
