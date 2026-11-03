<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KegiatanDonorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware role di route
    }

    public function rules(): array
    {
        $tanggal = ['required', 'date'];
        if ($this->isMethod('post')) {
            $tanggal[] = 'after_or_equal:today'; // hanya saat membuat baru
        }

        return [
            'nama'      => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'tanggal'   => $tanggal,
            'lokasi'    => ['required', 'string', 'max:255'],
            'kuota'     => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama'    => 'nama kegiatan',
            'tanggal' => 'tanggal kegiatan',
            'lokasi'  => 'lokasi',
            'kuota'   => 'kuota',
        ];
    }
}