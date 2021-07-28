<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;
    protected $table = 'resources';
    protected $primaryKey = 'id_resource';
    protected $fillable = [
        'type',
        'file',
        'description',

         ];
         public function disciplines()
         {
             return $this->belongsToMany(Discipline::class, 'DisciplineResource', 'id_discipline', 'id_resource');
         }
   }
