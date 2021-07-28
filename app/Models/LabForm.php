<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabForm extends Model
{
    use HasFactory;
    protected $table = 'lab_forms';
    protected $primaryKey = 'id_form';
    protected $fillable = [
        'description',
         ];

    public $timestamps = false;
    public function lab_forms()
    {
        return $this->hasMany(LabForm::class, 'id_form');
    }
}
