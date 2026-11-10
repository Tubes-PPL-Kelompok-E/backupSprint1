<?php

namespace App\Services;

use DomainException;

/** Aturan bisnis menolak sebuah aksi; pesannya aman ditampilkan langsung ke pengguna. */
class PendaftaranDitolak extends DomainException
{
}
