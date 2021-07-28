<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabConfig extends Model
{
    use HasFactory;
    protected $table = 'lab_configs';
    protected $primary_key = 'id_lab';
    protected $fillable = [
        'id_lab',
        'id_group',
        'deadline',
        'allowed after'
        ];
    public $timestamps = false;
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    public function lab()
    {
        return $this->belongsTo(Lab::class,'id_lab','id_lab');
    }
}
