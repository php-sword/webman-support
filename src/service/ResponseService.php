<?php

namespace sword\service;

use app\enum\ResponseCode;
use support\Response;

class ResponseService
{

    /**
     * 返回json数据的统一格式数据封装
     * @param int|ResponseCode $code 错误代码，0为无错误
     * @param string $message 响应提示文本
     * @param mixed $data 响应数据主体
     * @param bool $isResponse 是否返回Response格式并添加header
     * @return array|Response
     */
    public static function jsonPack(ResponseCode|int $code = 0, string $message = '', mixed $data = [], bool $isResponse = true): Response|array
    {
        //如果是Code枚举，则自动获取对应的错误信息
        if($code instanceof ResponseCode) {
            $message or $message = $code::getName($code);
            $code = $code->value;
        }

        //数据是否加密
        if(config('app.data_encrypt')) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $data = UtilsService::aesEncrypt($data);
        }

        $ret = [
            'code'   => $code,
            'data'   => $data,
            'message'=> $message
        ];

        if($isResponse){
            return new Response(200, [
                'Content-Type' => 'application/json'
            ], json_encode($ret, JSON_UNESCAPED_UNICODE));
        }else{
            return $ret;
        }
    }

}