<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecture extends Model
{
    use HasFactory;

    protected $table = 'lectures';
    protected $primaryKey = 'id_lecture';
    protected $fillable = [
        'id_discipline',
        'name',
        'content',
        'time',
    ];
    public $timestamps = true;

    public function discipline()
    {
        return $this->belongsTo(Discipline::class, 'id_discipline','id_discipline');
    }
}
