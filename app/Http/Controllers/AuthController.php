<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\JWT;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Попытка входа
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function signin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string|min:3|max:50',
            'password' => 'required|string|min:8|max:100|alpha_dash',
            'remember' => 'required|boolean',
            'device' => 'required|array',
        ]);

        if ($validator->fails()) {
            return self::error('LOGIN_ERROR',json_encode($validator->errors()->all()));
        }

        // Создать и залогинить пользователя? только для разработки
        if (false){
            $user = User::create([
                'login'=>$request->login,
                'password'=>Hash::make($request->password),
                ]);
            return self::success('LOGIN_SUCCESS',JWT::generate(
                $user->id_user,
                1, //default role
                $request->remember,
                $request->device
            ));
        }

        if (Validator::make($request->only('login'), [
            'login' => 'email',
        ])->fails()){
            // login is provided
            // but need to check for aplha_dash
            if (Validator::make($request->only('login'), [
                'login' => 'regex:/^[a-zA-Z0-9.\-_]+$/i',
            ])->fails()){
                // contains wrong symbols
                return self::error('LOGIN_ERROR','Login must contain alpha numeric symbols and dashes');
            }
            if (!$user = User::where('login', $request->login)->first())
            return self::error('LOGIN_ERROR','Неправильный логин или пароль');//,'USER_NOT_FOUND_BY_LOGIN');
        }else{
            // Email is provided
            if (!$user = User::where('email', $request->login)->first())
            return self::error('LOGIN_ERROR','Неправильный логин или пароль');//,'USER_NOT_FOUND_BY_EMAIL');
        }


        if(!$user ){
            return self::error('LOGIN_ERROR','Неправильный логин или пароль');//,'USER_NOT_FOUND');
        }

        //,'Код приглашения недопустимо использовать для аутентификации');
        if ($user->role>10) self::error('LOGIN_ERROR','Неправильный логин или пароль');

        // Забаненным пользователям доступ не предоставляется
        if ($user->banned!==0) return self::error("USER_BANNED","Пользователь заблокирован");

        if(!$user || ! Hash::check($request->password, $user->password)){
            return self::error('LOGIN_ERROR','Неправильный логин или пароль');//,'WRONG_PASSWORD');
        }

        $user->last_login = date('Y-m-d H:i:s');
        $user->save();

        $tokens = JWT::generate(
            $user->id_user,
            $user->role,
            $request->remember,
            $request->device
        );

        // return self::success('LOGIN_SUCCESS',$tokens);
        $status = 'ROLE_'.Str::upper(config('auth.roles')[$user->role]);
        return $this->sendTokens($status,$tokens);

    }

    /**
     * Функция обновления токенов
     * * @param accessToken string from header, expired tokens allowed, required
     * * @param refresh string from Request
     * * @param device string
     *
     * * @return array contains accessToken, accessTokenExpired, refreshToken
     * * @return REFRESH_SUCCESS
     *
     * @throws TOKEN_INVALID if accessToken invalid need to login, we don't know who is it
     * @throws REFRESH_INVALID if refershToken invalid
     * @throws REFRESH_EXPIRED if refreshToken expired
     */
    public function refresh(Request $request)
    {
        try {
            $user = User::find($request->jwt_user_id);
            if ($user->banned!==0) return self::error("USER_BANNED");
            $refreshToken = $request->cookie('refreshToken');
            // return response($refreshToken);
            $tokens = JWT::regenerate($refreshToken,$request->device);
            // $refresh_token =
            $user->last_login = date('Y-m-d H:i:s');
            $user->save();
            // return self::success('REFRESH_SUCCESS',$tokens,'Токены обновлены');
            $status = 'ROLE_'.Str::upper(config('auth.roles')[$user->role]);
            return $this->sendTokens($status,$tokens,'Токены обновлены');
        } catch (Exception $e) {
            return self::error($e->getMessage());
        }
    }

    /**
     * Функция выхода
     * Позволяет аннулировать токен сессию с использованием refresh токена
     * refresh должен принадлежать пользователю accessToken
     * выход будет успешно выполнен если пользователь выходит с того же устройства
     * Выход с другого устройства не доступен по refresh токену с помощью этой функции
     *
     * * @param accessToken string from header, required
     * * @param refresh string from Request
     *
     * * @return array contains accessToken, accessTokenExpired, refreshToken
     * * @return LOGOUT_SUCCESS code 200
     *
     * @throws TOKEN_INVALID if accessToken invalid, code 403
     * @throws TOKEN_EXPIRED if accessToken expired, code 403
     * @throws REFRESH_INVALID if refershToken invalid, code 403
     */
    public function signout(Request $request)
    {
        $refreshToken = $request->cookie('refreshToken');
        $token = JWT::find($refreshToken);
        // Если по найденный токен принадлежит другому пользователю или выход осуществляется с другого устройства
        if (!$token ||
            $token->id_user !== $request->jwt_user_id ||
            json_encode($request->device)!==$token->device
            ){
            return self::error('REFRESH_INVALID');
        }
        // Удалить
        $token->delete();

        return self::success('LOGOUT_SUCCESS')
            ->withoutCookie(
                'refreshToken',
                env('PATH_FOR_REFRESH_TOKEN_COOKIE'),
                env('DOMAIN_FOR_REFRESH_TOKEN_COOKIE'),
            );
    }

    /**
     * Попытка входа
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        // ? Определить роль по длинне инвайта
        $role = 0;
        if (($lenght = Str::length(trim($request->invitation)))>0){
            if ($lenght===10) $role = 91;
            if ($lenght===20) $role = 92;
            if ($lenght===40) $role = 93;
            // ! Длинна не соблюдена (Отлажено)
            if (!$role) return self::error("INVITE_INVALID","Такой код не подойдет");
        }else{
            // ! Длинна нулевая (Отлажено)
            return self::error("INVITE_IS_NOT_PROVIDED","Такой код не подойдет");
        }

        // * взять запись из БД, только если длинна и роль соответствуют
        $invite = User::whereLogin($request->invitation)->whereRole($role)->first();

        // ! Нет кода такой длинны с такой ролью, возможно код был создан искусственно и длинна не соблюдена (Отлажено)
        if (!$invite) return self::error("INVITE_INVALID","Такой код не подойдет");

        // * нужно узнать когда создан код
        // создан в прошлом - значит отнять текущее время от даты создания
        $created_at = strtotime($invite->created_at);
        $now = time();
        $past_time = $now - $created_at;
        // ! Пригласительный создан в будущем (Отлажено)
        if ($past_time < 0) return self::error("INVITE_INVALID","Такой код не подойдет");

        // Для студентов срок действия - 7 дней
        if (($role===91 && $past_time>60*60*24*7)
        // Для преподов срок действия - 4 дня
         || ($role===92 && $past_time>60*60*24*4)
        // Для админов срок действия - 1 день
         || ($role===93 && $past_time>60*60*24*1)){
            // ! Пригласительный просрочен
            return self::error("INVITE_EXPIRED","Такой код не подойдет");
        }

        // ? Это первый шаг регистрации? Тогда подойдет и то что мы уже нашли пригласительный код
        if ($request->method === 'checkInvite'){
            // Продолжаем регистрацию
            return self::success("OK");
        }

        // ? Это второй шаг регистрации, тогда будем регистрировать
        if ($request->method === 'register'){

            $validator = Validator::make($request->all(), [
                'login' => 'required|string|min:3|max:50|regex:/^[a-zA-Z]+[a-zA-Z0-9.\-_]+$/i|unique:users',
                'email' => 'required|email|max:50|unique:users',
                'password' => 'required|string|min:8|max:100|alpha_dash',
                'confirmation' => 'required|same:password',
                'device' => 'required|array',
            ]);

            if ($validator->fails()) {
                // * Соберем все ошибки
                $errors = [];
                $error = false;
                if ($validator->errors()->first('login')){
                    $errors['loginError'] = $validator->errors()->first('login');
                }
                if ($validator->errors()->first('email')){
                    $errors['emailError'] = $validator->errors()->first('email');
                }
                if ($validator->errors()->first('password')){
                    $errors['passwordError'] = $validator->errors()->first('password');
                }
                if ($validator->errors()->first('confirmation')){
                    $errors['confirmationError'] = $validator->errors()->first('confirmation');
                }
                return self::error('VALIDATION_ERROR',$error,$errors);
            }

            $role-=90;

            $invite->login = $request->login;
            $invite->email = $request->email;
            $invite->password = Hash::make($request->password);
            $invite->role = $role;
            $invite->s_name = $request->sname;
            $invite->f_name = $request->fname;
            if ($request->noFthname===false){
                $invite->fth_name = $request->fthname;
            }
            $tokens = JWT::generate(
                $invite->id_user,
                $role,
                false,
                $request->device
            );
            $invite->save();

            // return self::success('REGISTER_SUCCESS',$tokens);
            return $this->sendTokens('REGISTER_SUCCESS',$tokens);

        }

        // ! Выход за пределы допустимого кода
        return self::error("REQUEST_INVALID",$request);

    }

    private function sendTokens($status, $tokens, $message=null)
    {
        // if (isset($tokens['refreshToken']))
        $refreshToken = $tokens['refreshToken'];
        unset($tokens['refreshToken']);
        if ($tokens['remember']){
            $expires = env('JWT_REMEMBER_SESSION');
        }else{
            $expires = env('JWT_DEFAULT_SESSION');
        }
        unset($tokens['remember']);

        return self::success($status,$tokens,$message)
            ->withCookie(
                cookie(
                    'refreshToken',
                    $refreshToken,
                    $expires/60,
                    env('PATH_FOR_REFRESH_TOKEN_COOKIE'),
                    env('DOMAIN_FOR_REFRESH_TOKEN_COOKIE'),
                    env('IS_REFRESH_TOKEN_COOKIE_SECURED'),
                    true,
                    false,
                    'strict'
                )
            );
    }
}
