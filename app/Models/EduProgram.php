<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EduProgram extends Model
{
    use HasFactory, SoftDeletes;

    // Имя таблицы
    protected $table = 'edu_programs';

    // Первичный ключ
    protected $primaryKey = 'id_element';

    // Атрибуты доступные для заполнения
    protected $fillable = [
        'id_discipline',
        'order',
        'required',
        'instance',
        'instance_element_id',
        'time',
        'deleted_by',
    ];
    public $timestamps = false;

    protected $dates = ['deleted_at'];

    public function discipline()
    {
        return $this->belongsTo(Discipline::class, 'id_discipline','id_discipline');
    }

    public function deletes()
    {
        return $this->belongsTo(User::class, 'deleted_by','id_user');
    }

    public function user_progresses()
    {
        return $this->hasMany(UserEduProgress::class,'id_element','id_element');
    }

    // TODO: Instance get

    // TODO: Instance element get
}
