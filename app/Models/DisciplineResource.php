<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplineResource extends Model
{
    use HasFactory;
    protected $table = 'discipline_resources';
    // protected $primaryKey = 'id_resource';
    protected $fillable = [
        'id_discipline',
        'id_resource'
         ];

    public $timestamps = false;
}
