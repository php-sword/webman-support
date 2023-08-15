<?php

if (!function_exists('env')) {
    /**
     * 获取环境变量以及.env配置
     * @param string|null $name
     * @param mixed|null $default
     * @return mixed
     */
    function env(string $name = null, mixed $default = null): mixed
    {
        $val = getenv($name);
        return $val === false ? $default : $val;
    }
}

//处理开发是IDE提示优化
(function () {
    if (!config('app.debug')) return;

    $tp_ide_helper = base_path() . '/vendor/webman/think-orm/src/_ide_helper.php';
    if (file_exists($tp_ide_helper)) {
        //直接清空内容
        file_put_contents($tp_ide_helper, '<?php');
    }

    $tp_orm_query = base_path() . 'vendor/topthink/think-orm/src/db/BaseQuery.php';
    if (file_exists($tp_orm_query)) {
        //替换find返回
        $contents = file_get_contents($tp_orm_query);
        $lines = explode("\n", $contents);
        $targetLine = '    public function find($data = null)';

        for ($i = 0; $i < count($lines); $i++) {
            if (str_contains($lines[$i], $targetLine)) {
                $lines[$i - 2] = '     * @return static|null';
                break;
            }
        }

        $newContents = implode("\n", $lines);
        file_put_contents($tp_orm_query, $newContents);
    }
})();