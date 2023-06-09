<?php

namespace sword\service;

use app\enum\ResponseCode;
use app\exception\MsgException;
use support\exception\BusinessException;
use support\Response;
use sword\log\Log;
use Throwable;
use Tinywan\Jwt\Exception\JwtTokenException;
use Tinywan\Jwt\Exception\JwtTokenExpiredException;
use Webman\Http\Request;

/**
 * Http异常处理
 */
class HttpExceptionService
{

    /**
     * 封装的异常处理
     * @param Request $request
     * @param Throwable $exception
     * @return Response
     */
    public static function render(Request $request, Throwable $exception): Response
    {
        //如果是业务异常，直接返回
        if(
            ($exception instanceof BusinessException) &&
            ($response = $exception->render($request))
        ) {
            return $response;
        }

        //处理通用消息异常
        if($exception instanceof MsgException) {
            //错误提示消息
            $code = $exception->getCode();
            $message = $exception->getMessage();
            $data = $exception->getData();
        }elseif($exception instanceof JwtTokenExpiredException) {
            // Jwt验证Token过期
            $resCode = ResponseCode::TokenExpired;
            $code = $resCode->value;
            $message = $resCode::getName($resCode);
        }elseif($exception instanceof JwtTokenException) {
            // Jwt验证异常
            $resCode = ResponseCode::TokenError;
            $code = $resCode->value;
            $message = $resCode::getName($resCode);
        }else{
            //错误提示消息
            if(env('APP_DEBUG')){
                $code = $exception->getCode()?:1; //不能为0 因为jsonPack会响应success
                $message = $exception->getMessage();
                if($request->isAjax()){
                    $data = [
                        'message' => $message,
                        'trace' => $exception->getTraceAsString()
                    ];
                }else{
                    $message .= "\n". $exception->getTraceAsString();
                }
            }else{
                $code = 500;
                $message = '系统发生错误';
            }

            //向数据库插入错误日志
            Log::error(
                mb_substr('Http异常: '. $exception->getMessage(), 0, 255),
                $exception->getMessage(). "\n". $exception->getTraceAsString()
            );
        }

        if($request->isAjax()){
            return ResponseService::jsonPack($code, $message, $data??[]);
        }else{
            return response($message);
        }
    }

}