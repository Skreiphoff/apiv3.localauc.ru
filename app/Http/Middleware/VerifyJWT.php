<?php

namespace App\Http\Middleware;

use App\Models\JWT;
use Closure;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Send;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

class VerifyJWT
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $param = null)
    {
        $request->exception = [];
        // get token from Authorization header
        $token = $request->bearerToken();
        // check if Auth header exist
        if (!$token){
            // if there's no token
            // throw new AuthorizationException('TOKEN_INVALID'); // code 403
            return Send::error('UNAUTHENTICATED');
        }

        // verify token
        if ($error = JWT::verify($token, ($param === 'refreshing'))){
            if ($error->getMessage() === 'The token expired.'){
                // throw new AuthorizationException('TOKEN_EXPIRED'); // code 403
                return Send::error('TOKEN_EXPIRED');
            }else{
                // throw new AuthorizationException('TOKEN_INVALID'); // code 403
                return Send::error($error->getMessage());
                return Send::error('TOKEN_INVALID');
            }
        }

        // get data from token
        $tokenData = JWT::data($token);

        if ($param && $param !== 'refreshing' && $param !== config('auth.roles')[$tokenData->role]){
            // throw new AuthorizationException('ACCESS_DENIED'); // code 403
            return Send::error('ACCESS_DENIED');
        }

        // add user_id and user_role to $request
        $request->jwt_user_id = $tokenData->id_user;
        $request->jwt_user_role = config('auth.roles')[$tokenData->role];

        return $next($request);
    }
}
