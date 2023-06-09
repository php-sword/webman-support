<?php

namespace sword\http\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * 控制器专用中间件接口
 * Interface ControllerMiddlewareInterface
 * @package app\common\middleware
 */
class ControllerMiddleware implements MiddlewareInterface
{

    /**
     * 实现控制器中间件处理
     * @param Request $request
     * @param callable $handler
     * @return Response
     */
    public function process(Request $request, callable $handler) : Response
    {
        /**
         * @var ControllerMiddlewareInterface[] $middleList
         */
        $middleList = [];
        try {
            $controllerClass = $request->controller;

            $ref = new \ReflectionClass($controllerClass);
            $middles = $ref->getAttributes(MiddlewareAttr::class);
            if($middles){
                /**
                 * @var MiddlewareAttr $middle
                 */
                $middle = $middles[0]->newInstance();
                $middleList = $middle->middleware;
            }else{
                //获取控制器父类注解
                $parentClass = $ref->getParentClass();
                if($parentClass){
                    $middles = $parentClass->getAttributes(MiddlewareAttr::class);
                    if($middles){
                        /**
                         * @var MiddlewareAttr $middle
                         */
                        $middle = $middles[0]->newInstance();
                        $middleList = $middle->middleware;
                    }
                }
            }
        } catch (\ReflectionException) {}

        if(!$middleList) {
            return $handler($request);
        }

        //如果存在中间件，则调用控制器中间件
        foreach ($middleList as $middleware) {
            //调用中间件，若有响应则中断请求
            if($res = $middleware->before($request)){
                return $res;
            }
        }

        //继续向洋葱穿越，得到响应
        $response = $handler($request);

        //倒序调用中间件，若有响应则以响应为准
        foreach (array_reverse($middleList) as $middleware) {
            if($res = $middleware->after($request, $response)){
                return $res;
            }
        }

        return $response;
    }
}