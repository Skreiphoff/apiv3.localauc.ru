<?php

namespace App\Http\Controllers;

use App\Models\CompleteLab;
use App\Models\Discipline;
use App\Models\DisciplineResource;
use App\Models\Group;
use App\Models\Lab;
use App\Models\LabConfig;
use App\Models\Notification;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use function Safe\date;

class CommonController extends Controller
{
    /**
     * * ГОТОВО
     * ? Загружает фотографию
     *
     */
    //TODO: Проверка MIME файла
    public function upload_avatar(Request $request)
    {
        $path = $request->file('file')->store('avatars','public');

        $user = User::find($request->jwt_user_id);

        $user->photo = $path;
        $user->save();

        return self::success("AVATAR_UPLOADED",['path' => $path]);
    }

    /**
     * * ГОТОВО
     * ? Скачивает файл задания
     */
    public function get_lab_file(Request $request)
    {
        return FilesController::download_lab($request);
    }

    /**
     * * Временное решение
     * ? Используется в Teachers/sidebar
     */
    //TODO: полностью переделать
    public function get_user_data(Request $request)
    {
        $user = User::find($request->jwt_user_id);
        if (!$user){
            return self::error("NO_USER");
        }
        $result = [
            'f_name' => $user->f_name,
            's_name' => $user->s_name,
            'fth_name' => $user->fth_name,
            'photo' => asset(Storage::url($user->photo)),
        ];
        return self::success("OK",$result);
    }

    /**
     * * Временное решение
     * ? Используется в Teachers/sidebar
     */
    //TODO: полностью переделать
    public function get_user_profile_data(Request $request)
    {
        $user = User::find($request->jwt_user_id);
        if (!$user){
            return self::error("NO_USER");
        }
        $user->photo = asset(Storage::url($user->photo));
        return self::success("OK",$user);
    }

    /**
     * * Временное решение
     * ? Используется в Teachers/sidebar
     */
    //TODO: полностью переделать
    public function get_full_user_data(Request $request)
    {
        $user = User::find($request->jwt_user_id);
        if (!$user){
            return self::error("NO_USER");
        }
        $user->photo = asset(Storage::url($user->photo));
        return self::success("OK",$user);
    }

    /**
     * * Готово. Скачивает ресурс
     */
    public function get_resource(Request $request)
    {
        return FilesController::download_resource($request);
    }

    /**
     * Проверка пароля пользователя
     * для авторизации каких-либо действий
     */
    public function check_password(Request $request)
    {
        $user = User::find($request->jwt_user_id);
        if(!$user || ! Hash::check($request->password, $user->password)){
            return self::error('LOGIN_ERROR','Неправильный пароль');
        }
        return self::success("OK");
    }

    public function get_user_notifications(Request $request)
    {
        $result = Notification::orderBy('id','DESC')
            ->where([
                'id_user'=>$request->jwt_user_id,
                'hidden'=>0,
            ])
            ->get();
        // Notification::where(['id_user'=>$request->jwt_user_id,'received'=>NULL])
        //     ->update(['received' => date('Y-m-d H:i:s')]);
        return self::success("OK",$result);
    }

    public function get_new_user_notifications(Request $request)
    {
        $result = Notification::where('id_user',$request->jwt_user_id)->whereNull('received')
            ->orderBy('id','DESC')
            ->get();

        Notification::where(['id_user'=>$request->jwt_user_id,'received'=>NULL])
            ->update(['received' => date('Y-m-d H:i:s')]);
        return self::success("OK",$result);
    }

    //! //////////////////////////////////////////////////////////////
    //! //////////////////////////////////////////////////////////////
    //! //////////////////////////////////////////////////////////////
    //! //////////////////////////////////////////////////////////////
    //! //////////////////////////////////////////////////////////////

}
