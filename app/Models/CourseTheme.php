<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseTheme extends Model
{
    use HasFactory;
    protected $table = 'course_themes';
    protected $primaryKey = 'id_theme';
    protected $fillable = [
        'id_student',
        'id_discipline',
        'description',
        'confirmed'
         ];
    public $timestamps = false;
    public function student()
    {
        return $this->belongsTo(User::class,'id_student');

    }
    public function discipline()
    {
        return $this->belongsTo(Discipline::class);

    }
}
