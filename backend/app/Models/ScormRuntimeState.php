<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScormRuntimeState extends Model
{
    protected $table = 'scorm_runtime_state';

    protected $fillable = [
        'launch_session_id',
        'cmi_data',
        'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'cmi_data' => 'array',
            'last_updated_at' => 'datetime',
        ];
    }

    public function launchSession(): BelongsTo
    {
        return $this->belongsTo(PackageLaunchSession::class, 'launch_session_id');
    }
}
