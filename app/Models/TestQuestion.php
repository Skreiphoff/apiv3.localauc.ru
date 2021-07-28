<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestQuestion extends Model
{
    use HasFactory;

    protected $table = 'test_questions';
    protected $primaryKey = 'id_question';
    protected $fillable = [
        'id_test',
        'id_type',
        'text',
        'answers',
        'required',
        'time',
        'level',
        'weight',
    ];
    public $timestamps = false;

    public function test()
    {
        return $this->belongsTo(Test::class, 'id_test','id_test');
    }

    public function type()
    {
        return $this->belongsTo(TestQuestionType::class, 'id_type','id_type');
    }

    public function answers()
    {
        return $this->hasMany(TestVariant::class, 'id_question','id_question');
    }
}
