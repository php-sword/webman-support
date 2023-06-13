<?php

if(!function_exists('env')) {
    /**
     * 获取环境变量以及.env配置
     * @param string|null $name
     * @param mixed|null $default
     * @return mixed
     */
    function env(string $name = null, mixed $default = null): mixed
    {
        $val =  getenv($name);
        return $val === false? $default : $val;
    }
}
