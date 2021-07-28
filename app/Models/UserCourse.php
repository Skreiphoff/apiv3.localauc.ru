<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCourse extends Model
{
    use HasFactory;

    // Имя таблицы
    protected $table = 'user_courses';

    // Первичный ключ
    protected $primaryKey = 'id_user';

    // Атрибуты доступные для заполнения
    protected $fillable = [
        'id_course',
        'progress'
    ];
    public $timestamps = false;

    protected $appends = [
        'name'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'id_course','id_course');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user','id_user');
    }

    public function getNameAttribute()
    {
        return $this->course->name;
    }
}
