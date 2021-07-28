<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompleteLab extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $primaryKey = 'id_student';
    protected $table = 'complete_labs';
    protected $fillable = [
        'id_student',
        'id_lab',
        'id_teacher',
        'complete_date',
        'mark',
        'file',
        'status',
        'coments',
         ];

    public function student()
    {
        return $this->belongsTo(User::class,'id_student');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class,'id_teacher');
    }

    public function teacher_names()
    {
        $id_teacher = $this->id_teacher;

    }

    public function labs()
    {
        return $this->belongsTo(Lab::class,'id_lab');
    }



}
