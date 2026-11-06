<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_log';

    // Append-Only Audit Log: Hanya memiliki kolom created_at (tanpa updated_at)
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'aktivitas',
        'entitas',
        'entitas_id',
        'data_lama',
        'data_baru',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'data_lama' => 'array',
            'data_baru' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Helper static untuk mencatat audit log dengan mudah
     */
    public static function catat(
        string $aktivitas,
        string $entitas,
        ?int $entitasId = null,
        ?array $dataLama = null,
        ?array $dataBaru = null,
        ?int $userId = null
    ): self {
        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'aktivitas' => $aktivitas,
            'entitas' => $entitas,
            'entitas_id' => $entitasId,
            'data_lama' => $dataLama,
            'data_baru' => $dataBaru,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}

