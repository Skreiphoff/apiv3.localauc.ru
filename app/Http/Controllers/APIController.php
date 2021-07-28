<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class APIController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'method' => 'required|string'
        ]);

        if (method_exists(CommonController::class,$request->method))
        {
            $class = new CommonController();
            return $class->{$request->method}($request);
        }

        switch ($request->jwt_user_role) {
            case 'student':
                $class = StudentController::class;
                break;

            case 'admin':
                if (method_exists(AdminController::class,$request->method))
                {
                    $class = new AdminController();
                    return $class->{$request->method}($request);
                }
                // no break;

            case 'teacher':
                $class = TeacherController::class;
                break;

            default:
                return self::error("UNDEFINED_ROLE (APIC)");
                break;
        }

        if (method_exists($class,$request->method))
        {
            $class = new $class;
            return $class->{$request->method}($request);
        }

        return self::error("UNDEFINED_METHOD (APIC)");
    }
}
