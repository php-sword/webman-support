<?php

namespace sword\http\middleware;

/**
 * 控制器中间件注解
 * Class MiddlewareAttr
 * @package sword\http\middleware
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class MiddlewareAttr
{

    /**
     * 控制器中间件
     * @var ControllerMiddlewareInterface[] $middleware 中间件列表
     */
    public array $middleware = [];

    /**
     * 接收注解参数
     * @param string ...$middleware
     */
    public function __construct(string ...$middleware)
    {
        //校验传入的参数是否合法 实现ControllerMiddlewareInterface接口
        foreach ($middleware as $item) {
            $obj = new $item();
            if (!$obj instanceof ControllerMiddlewareInterface) {
                throw new \InvalidArgumentException("{$item} must instanceof ControllerMiddlewareInterface");
            }
            $this->middleware[] = $obj;
        }
    }
}