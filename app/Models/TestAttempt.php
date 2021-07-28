<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestAttempt extends Model
{
    use HasFactory;

    protected $table = 'test_attempts';
    protected $primaryKey = 'id_attempt';
    protected $fillable = [
        'id_test',
        'id_user',
        'attempt_number',
        'status',
        'progress',
        'mark',
        'fail_reason',
        'completed_at',
    ];
    public $timestamps = true;

    public function test()
    {
        return $this->belongsTo(Test::class, 'id_test','id_test');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user','id_user');
    }

    public function questions()
    {
        return $this->hasMany(TestVariant::class, 'id_attempt','id_attempt');
    }

    public function failures()
    {
        return $this->hasMany(TestFailure::class, 'id_attempt','id_attempt');
    }
}
