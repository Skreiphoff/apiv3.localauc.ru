<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;
    protected $table = 'groups';
    protected $primaryKey = 'id_group';
    protected $fillable = [
        'admission_year',
        'students_count',
        'invites'
        ];
    protected $casts = [
        'id_group' => 'string',
    ];

    public $timestamps = false;
    public function students()
    {
        return $this->hasMany(User::class, 'id_group','id_group');

    }
    public function disciplines()
    {
        return $this->hasMany(Student::class, 'id_group','id_group');
    }

    // public function disciplines()
    // {
    //     return $this->belongsToMany(Discipline::class,'students','id_group','id_discipline');
    // }

    public function lab_configs()
    {
        return $this->hasMany(LabConfig::class, 'id_group');
    }
}
