<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lab extends Model
{

    use HasFactory;

    protected $table = 'labs';
    protected $primaryKey = 'id_lab';
    protected $fillable = [
        'id_discipline',
        'id_form',
        'description',
        'discipline_description',
        'file',
        'comment',
        //next fields are custom, do not use while CRUD to DB
        'deadline',
        'answer'
        ];

    public function lab_config()
    {
        return $this->hasMany(LabConfig::class, 'id_lab');
    }
    public function complete_labs()
    {
        return $this->hasMany(CompleteLab::class, 'id_lab');
    }
    public function exam_variants()
    {
        return $this->hasMany(ExamVariant::class, 'id_lab');
    }
    public function lab_form()
    {
        return $this->belongsTo(LabForm::class, 'id_form');
    }
    public function discipline()
    {
        return $this->belongsTo(Discipline::class,'id_discipline');
    }
}
