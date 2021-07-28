<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public static function success($status, $data= [], $message=null)
    {
    	$response = [
            'success' => true,
            'status' => $status,
        ];
        if(!empty($data)){
            $response['data'] = $data;
        }
        if($message){
            $response['message'] = $message;
        }

        return response()->json($response, 200);
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public static function error($errorStatus, $message=null, $errorData = [], $code = 200)
    {
        $response = [
            'success' => false,
            'status' => $errorStatus,
        ];

        if($message){
            $response['message'] = $message;
        }

        if(!empty($errorData)){
            $response['data'] = $errorData;
        }

        return response()->json($response, $code);
    }

    /**
     * success response alias.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($status, $data= [], $message=null)
    {
    	return self::success($status, $data= [], $message=null);
    }

    /**
     * error response alias
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($errorStatus, $message=null, $errorData = [], $code = 200)
    {
    	return self::error($errorStatus, $message=null, $errorData = [], $code = 200);
    }
}
