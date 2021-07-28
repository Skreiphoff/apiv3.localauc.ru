<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamVariant extends Model
{
    use HasFactory;
    protected $table = 'exam_variants';
    protected $primary_key = 'id_lab';
    protected $fillable = [
        'id_lab',
        'id_student',
        'variant'
         ];
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);

    }
    public function lab()
    {
        return $this->belongsTo(Lab::class);
    }
}
