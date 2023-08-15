<?php

namespace sword;

class Install
{
    const WEBMAN_PLUGIN = true;

    /**
     * @var array
     */
    protected static array $pathRelation = [
    ];

    /**
     * Install
     * @return void
     */
    public static function install()
    {
        static::installByRelation();
    }

    /**
     * Uninstall
     * @return void
     */
    public static function uninstall()
    {
        self::uninstallByRelation();
    }

    /**
     * installByRelation
     * @return void
     */
    public static function installByRelation()
    {
        var_dump("installByRelation");
        if (!config('app.debug')) return;

        var_dump("installByRelation1111");

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
    }

    /**
     * uninstallByRelation
     * @return void
     */
    public static function uninstallByRelation()
    {
    }

}
