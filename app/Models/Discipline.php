<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BooleanSwitchers;

class Discipline extends Model
{
    use HasFactory;
    protected $table = 'disciplines';
    protected $primaryKey = 'id_discipline';
    protected $fillable = [
        'id_creator',
        'description',
        'exam_forms',
        'rating_scale',
        'creator_data',
        'teachers_data'
        ];
    public $timestamps = false;

    public function creator()
    {
        return $this->belongsTo(User::class,'id_creator');
    }
    public function teachers()
    {
        return $this->belongsToMany(User::class,'teachers','id_discipline','id_user');
    }
    public function students()
    {
        return $this->hasMany(Student::class, 'id_discipline');
    }
    public function course_themes()
    {
        return $this->hasMany(CourseTheme::class, 'id_discipline');
    }
    public function labs()
    {
        return $this->hasMany(Lab::class, 'id_discipline');
    }
    public function resources()
    {
        return $this->belongsToMany(Resource::class, 'discipline_resources', 'id_discipline', 'id_resource');
    }

    public function exam_forms_convert()
    {
        $exam_forms_int = $this->exam_forms;
        $this->exam_forms = BooleanSwitchers::convert_switchers($exam_forms_int,config('switchers.exam_forms'));
        return $this;
    }

}
