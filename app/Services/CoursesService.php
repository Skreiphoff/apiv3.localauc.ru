<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserCourse;
use Illuminate\Http\Request;

class CoursesService
{
    public function userCoursesList($id_user)
    {
        $list = UserCourse::whereIdUser($id_user)->get();

        return $list;
    }
}
