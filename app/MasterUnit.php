<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterUnit extends Model
{
    protected $table = 'master_unit';
    protected $fillable = ['unit', 'is_imported'];

    public function need()
    {
        return $this->belongsToOne('App\Needs', 'unit', 'id');
    }

    public function logisticRealizationItems()
    {
        return $this->belongsToOne('App\LogisticRealizationItems', 'unit_id', 'id');
    }
}
