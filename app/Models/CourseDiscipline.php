<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseDiscipline extends Model
{
    use HasFactory;

    protected $table = 'course_disciplines';
    protected $primaryKey = 'id_course';
    protected $fillable = [
        'id_discipline',
        'order',
        'required',
    ];
    public $timestamps = false;

    public function course()
    {
        return $this->belongsTo(Course::class, 'id_course','id_course');
    }

    public function discipline()
    {
        return $this->belongsTo(Discipline::class, 'id_discipline','id_discipline');
    }
}
