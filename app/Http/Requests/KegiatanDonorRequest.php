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
            'nama'        => ['required', 'string', 'max:150'],
            'deskripsi'   => ['nullable', 'string'],
            'tanggal'     => $tanggal,
            'jam_mulai'   => ['nullable', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'date_format:H:i', 'after:jam_mulai'],
            'lokasi'      => ['required', 'string', 'max:255'],
            'kuota'       => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama'        => 'nama kegiatan',
            'tanggal'     => 'tanggal kegiatan',
            'jam_mulai'   => 'jam mulai',
            'jam_selesai' => 'jam selesai',
            'lokasi'      => 'lokasi',
            'kuota'       => 'kuota',
        ];
    }
}