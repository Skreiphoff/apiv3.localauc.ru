<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;
    protected $table = 'students';
    protected $fillable = [
        'id_discipline',
        'id_group',
        'edu_year',
        'semester'
         ];

    public $timestamps = false;
    public function group()
    {
        return $this->belongsTo(Group::class,'id_group','id_group');
    }
    public function discipline()
    {
        return $this->belongsTo(Discipline::class,'id_discipline','id_discipline');
    }

    public function get_by_semester($semester,$admission_year)
    {
        $edu_year = ($semester-1)%2+$admission_year;
        if ($semester = $semester%2 == 0) $semester = 2;

        return Student::where('semester',$semester)->where('edu_year',$edu_year)->get();
    }
}
