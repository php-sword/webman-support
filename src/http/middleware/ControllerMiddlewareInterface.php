<?php

namespace sword\http\middleware;

use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 控制器专用中间件接口
 * Interface ControllerMiddlewareInterface
 * @package app\common\middleware
 */
interface ControllerMiddlewareInterface
{

    /**
     * 控制器中间件处理 - 前置
     * 若返回响应则中断请求
     * @param Request $request
     * @return Response|null
     */
    public function before(Request $request): ?Response;

    /**
     * 控制器中间件处理 - 后置
     * 若有响应则以响应为准
     * @param Request $request
     * @param Response $response 控制器执行后的响应
     * @return Response|null
     */
    public function after(Request $request, Response $response): ?Response;

}