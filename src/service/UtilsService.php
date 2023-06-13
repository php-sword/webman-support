<?php

namespace sword\service;

use sword\Cache\Facade\Cache;
use Webman\Http\Request;

/**
 * 工具类
 * @version v1.0.1
 */
class UtilsService
{

    /**
     * @return \Redis
     */
    public static function getRedisDriver(): \Redis
    {
        /**
         * @var \Redis
         */
        return Cache::store('redis')->handler();
    }

    /**
     * 时间锁
     * @param string $key
     * @param int $expire 自动解锁时间 秒
     * @return bool
     */
    public static function setLock(string $key, int $expire = 1): bool
    {
        $key = config('app.app_key') . '_lock:' . $key;

        $redis = static::getRedisDriver();

        if ($expire == null) {
            $redis->del($key);
            return true;
        }

        $res = $redis->setNx($key, time());
        if ($res) {
            $redis->expire($key, $expire);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取锁状态
     * @param string $key
     * @return ?string
     */
    public static function getLock(string $key): ?string
    {
        $key = config('app.app_key') . '_lock:' . $key;
        $redis = static::getRedisDriver();
        return $redis->get($key);
    }

    /**
     * 获取请求路由地址
     * @param Request $request
     * @return string
     */
    public static function getRequestPath(Request $request): string
    {
        $appName = $request->app;
        $path = $request->path();
        //判断该请求是否为插件
        if (str_starts_with($path, "/app/")) {
            $appName = "app/" . explode("/", $path)[2];
        }
        $controller = explode('\\', $request->controller);
        $controller = $controller[count($controller) - 1];
        $action = $request->action;

        //当前请求的Path
        return "$appName/$controller/$action";
    }

    /**
     * 获取调用方法的来源
     * @param int $index
     * @return string
     */
    public static function getCallFunc(int $index = 1): string
    {
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $index + 1);
        $trace = $traces[$index] ?? null;
        return $trace ? "{$trace['class']}::{$trace['function']}" : "";
    }

    /**
     * 获取请求来源信息
     * @param Request $request
     * @return string
     */
    public static function getRequestSource(Request $request): string
    {
        return $request->method() . ':' . self::getRequestPath($request);
    }

    /**
     * AES加密
     * @param string $data 待加密数据
     * @param string|null $key 加密密钥
     * @return string
     */
    public static function aesEncrypt(string $data, ?string $key = null): string
    {
        $key = $key ?? config('app.data_encrypt_key');

        return openssl_encrypt($data, 'AES-128-ECB', $key);
    }

    /**
     * AES解密
     * @param string $data 待解密数据
     * @param string|null $key 解密密钥
     * @return string|false
     */
    public static function aesDecrypt(string $data, ?string $key = null): string|false
    {
        $key = $key ?? config('app.data_encrypt_key');

        return openssl_decrypt($data, 'AES-128-ECB', $key);
    }

}