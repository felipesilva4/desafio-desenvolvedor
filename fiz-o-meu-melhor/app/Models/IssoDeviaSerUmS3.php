<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssoDeviaSerUmS3 extends Model
{
    use HasFactory;

    protected $table = 'issodeviaserums3';

    protected $fillable = [
        'upload_historic_id',
        'file_path',
    ];

    public function uploadHistoric()
    {
        return $this->belongsTo(UploadHistoric::class);
    }
}


