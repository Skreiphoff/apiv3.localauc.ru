<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';
    // Первичный ключ
    protected $primaryKey = 'id_notification';

    protected $fillable = [
        'id_user',
        'text',
        'sended',
        'received',
        'viewed',
        'hidden',
         ];

}
