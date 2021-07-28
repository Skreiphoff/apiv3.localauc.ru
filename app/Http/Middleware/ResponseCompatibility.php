<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ResponseCompatibility
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $content = json_decode($response->getContent(),true);

        $decoded = true;

        if (is_null($content)){
            $content = $response->getContent();
            $decoded = false;
        }

        if (!$decoded||!isset($content['success'])){
            if ($response->isOk()){
                $response->setContent($this->success(
                    'OK',
                    $content
                ));
            }else{
                $response->setContent($this->error(
                    $response->getStatusCode(),
                    $content
                ));
            }
        }

        return $response;
    }

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    private function success($status, $data= [])
    {
    	$response = [
            'success' => true,
            'status' => $status,
        ];

        if(!empty($data)){
            $response['data'] = $data;
        }

        return json_encode($response);
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    private function error($errorStatus, $errorData = [])
    {
        $errorData['success'] = false;
        $errorData['status'] = $errorStatus;

        return json_encode($errorData);
    }
}
