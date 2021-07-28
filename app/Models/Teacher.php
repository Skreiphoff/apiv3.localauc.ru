<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;
    protected $table = 'teachers';
    protected $fillable = [
        'id_discipline',
        'id_user'
         ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);

    }
    public function discipline()
    {
        return $this->belongsTo(Teacher::class);
    }
}

