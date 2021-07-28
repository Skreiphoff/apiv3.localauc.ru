<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    // Имя таблицы
    protected $table = 'courses';

    // Первичный ключ
    protected $primaryKey = 'id_course';

    // Атрибуты доступные для заполнения
    protected $fillable = [
        'name',
    ];
    public $timestamps = true;

    public function users()
    {
        return $this->hasMany(UserCourse::class,'id_course','id_course');
    }

    public function disciplines()
    {
        return $this->hasMany(CourseDiscipline::class,'id_course','id_course');
    }

    // Использовать метки времени
    //    protected $timestamps = true;

    //    public function disciplines()
    //    {
    //        return $this->hasManyThrough(
    //            Discipline::class,
    //            CourseDiscipline::class,
    //        );
    //    }
}
