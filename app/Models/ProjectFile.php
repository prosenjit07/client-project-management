<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFile extends Model
{
    protected $fillable = [
        'project_id',
        'file_name',
        'file_path',
        'file_type',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}