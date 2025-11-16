<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadHistoric extends Model
{
    use HasFactory;

    public const STATUS_WAITING = 'WAITING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_PROCESSED = 'PROCESSED';
    public const STATUS_ERROR = 'ERROR';

    public const STATUSES = [
        self::STATUS_WAITING,
        self::STATUS_PROCESSING,
        self::STATUS_PROCESSED,
        self::STATUS_ERROR,
    ];

    protected $fillable = [
        'name',
        'hash',
        'reference_date',
        'status',
    ];

    protected $casts = [
        'reference_date' => 'date',
    ];

    public function storage()
    {
        return $this->hasOne(IssoDeviaSerUmS3::class, 'upload_historic_id');
    }
}

