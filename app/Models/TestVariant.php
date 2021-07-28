<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestVariant extends Model
{
    use HasFactory;

    protected $table = 'test_variants';
    protected $primaryKey = 'id_attempt';
    protected $fillable = [
        'id_question',
        'answer',
        'is_correct',
        'details',
        'order',
        'time_spent',
    ];
    public $timestamps = false;

    public function attempt()
    {
        return $this->belongsTo(TestAttempt::class, 'id_attempt','id_attempt');
    }

    public function question()
    {
        return $this->belongsTo(TestQuestion::class, 'id_question','id_question');
    }

}
