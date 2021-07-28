<?php

namespace App\Facades;

use App\Services\CoursesService;
use Illuminate\Support\Facades\Facade;

class CourseFacade extends Facade
{
    protected static function getFacadeAccessor() {
        return CoursesService::class;
    }
}
