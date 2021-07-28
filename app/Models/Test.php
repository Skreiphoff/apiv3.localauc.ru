<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $table = 'tests';
    protected $primaryKey = 'id_test';
    protected $fillable = [
        'id_discipline',
        'name',
        'max_attempts',
        'time',
        'pass_weight',
        'parameters',
    ];
    public $timestamps = true;

    public function discipline()
    {
        return $this->belongsTo(Discipline::class, 'id_discipline','id_discipline');
    }

    public function attempts()
    {
        return $this->hasMany(TestAttempt::class, 'id_test','id_test');
    }

    public function questions()
    {
        return $this->hasMany(TestQuestion::class, 'id_test','id_test');
    }
}
