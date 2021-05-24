<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcceptanceReportEvidence extends Model
{
    protected $fillable = [
        'acceptance_report_id', 'path', 'type'
    ];

    public function getPathAttribute($value)
    {
        return config('aws.url') . $value;
    }
}
