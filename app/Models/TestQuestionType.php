<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestQuestionType extends Model
{
    use HasFactory;

    protected $table = 'test_question_types';
    protected $primaryKey = 'id_type';
    protected $fillable = [
        'description',
    ];
    public $timestamps = false;
}
