<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestFailure extends Model
{
    use HasFactory;

    protected $table = 'test_failures';
    protected $primaryKey = 'id_failure';
    protected $fillable = [
        'id_attempt',
        'reason',
        'is_fatal',
    ];
    public $timestamps = true;

    public function attempt()
    {
        return $this->belongsTo(TestAttempt::class, 'id_attempt','id_attempt');
    }

    public function user()
    {
        return $this->hasOneThrough(
            User::class,
            TestAttempt::class,
            'id_attempt',
            'id_user',
            'id_attempt',
            'id_user');
    }
}
