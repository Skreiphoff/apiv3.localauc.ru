<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEduProgress extends Model
{
    use HasFactory;

    // Имя таблицы
    protected $table = 'user_edu_progress';

    // Первичный ключ
    protected $primaryKey = 'id_user';

    // Атрибуты доступные для заполнения
    protected $fillable = [
        'id_element',
        'completed',
        'time_spent',
    ];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user','id_user');
    }

    public function edu_element()
    {
        return $this->belongsTo(EduProgram::class, 'id_element','id_element');
    }
}
