<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory , Notifiable;

    // Имя таблицы
    protected $table = 'users';

    // Первичный ключ
    protected $primaryKey = 'id_user';

    // Атрибуты доступные для заполнения
    protected $fillable = [
        'login',
        'password',
        's_name',
        'f_name',
        'fth_name',
        'role',
        'photo',
        'id_group',
        'email',
        'banned',
        'self',
    ];

    // Атрибуты которые нужно скрыть из возвращаемого массива.
    protected $hidden = [
        'password',
    ];

    // ОДИН-КО-МНОГИМ Связь с таблицей refresh_tokens
    public function refresh_tokens()
    {
        return $this->hasMany(JWT::class, 'id_user','id_user');
    }

    // ОДИН-КО-МНОГИМ Связь с таблицей дисциплин: созданные пользователем дисциплины
    public function created_disciplines()
    {
        return $this->hasMany(Discipline::class, 'id_creator','id_user');
    }

    // ОДИН-КО-МНОГИМ-Через-отношение Связь с таблицей дисциплин через один-ко-многим к таблице teachers: преподаваемые пользователем дисциплины
    public function teachers_disciplines()
    {
        return $this->belongsToMany(Discipline::class,'teachers','id_user','id_discipline');
    }

    // ОДИН К ОДНОМУ Получает запись таблицы группы
    public function group()
    {
        return $this->belongsTo(Group::class, 'id_group','id_group');
    }

    // ОДИН-КО-МНОГИМ Получает записи таблицы exam_variants, где 'id_student'=='id_user'
    public function exam_variants()
    {
        return $this->hasMany(ExamVariant::class, 'id_student','id_user');
    }

    // ОДИН-КО-МНОГИМ Получает записи таблицы course_themes, где 'id_student'=='id_user'
    public function course_themes()
    {
        return $this->hasMany(CourseTheme::class, 'id_student','id_user');
    }

    // ОДИН-КО-МНОГИМ Получает записи таблицы complete_labs, где 'id_student'=='id_user'
    public function complete_labs()
    {
        return $this->hasMany(CompleteLab::class, 'id_student','id_user');
    }

    // ОДИН-КО-МНОГИМ Получает записи таблицы teachers, где 'id_user'=='id_user'
    public function teachers()
    {
        return $this->hasMany(Teacher::class, 'id_user','id_user');
    }

    // ОДИН-КО-МНОГИМ-через-отношение Получает записи таблицы students, через таблицу groups,
    // где users.id_group == groups.id_group and groups.id_group == students.id_group
    public function student_disciplines()
    {
        return $this->hasManyThrough(
            Student::class,
            Group::class,
            'id_group',
            'id_group','id_group','id_group');

    }

    // ОДИН-КО-МНОГИМ-через-отношение Получает записи таблицы students, через таблицу groups,
    // где users.id_group == groups.id_group and groups.id_group == students.id_group
    public function student_labs()
    {
        return $this->hasManyThrough(
            LabConfig::class,
            Group::class,
            'id_group',
            'id_group','id_group','id_group');

    }

    /**
     * Дополнительная функция, возвращает экзепляр модели содержащий только
     * id_user, f_name, s_name, fth_name
     */
    public function names()
    {
        if ($this->photo===null){
            $photo = null;
        }else{
            $photo = asset(Storage::url($this->photo));
        }
        $data = new User();
        $data->id_user = $this->id_user;
        $data->f_name = $this->f_name;
        $data->s_name = $this->s_name;
        $data->fth_name = $this->fth_name;
        $data->photo = $photo;
        return $data;
    }

    public function courses()
    {
        return $this->hasMany(UserCourse::class,'id_user','id_user');
    }
}
