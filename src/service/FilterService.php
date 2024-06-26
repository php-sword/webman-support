<?php

namespace sword\service;

/**
 * 前端过滤器参数解析
 * @version 1.0.1
 */
class FilterService
{

    /**
     * 将filter的json字符串格式化
     * @param string $filterJson 前端提交的过滤json数据
     * @param array $fieldReplace 字段名替换规则
     * @return array
     */
    public static function format(string $filterJson, array $fieldReplace = []): array
    {
        $filterArr = json_decode($filterJson, true);
        if(!$filterArr) return [];
        $filterData = [];
        //将数据逐个解析为where所需数组
        foreach ($filterArr as $key => $val) {
            $item = explode('|', $val);

            //替换field
            if(isset($fieldReplace[$key])) $key = $fieldReplace[$key];

            if($item[0] !== ''){
                if($v = self::formatWhere($key, $item)){
                    $filterData[] = $v;
                }
            }
        }
        return $filterData;
    }

    /**
     * 格式化where条件
     * @param string $key
     * @param mixed $item
     * @return array|null
     */
    protected static function formatWhere(string $key, mixed $item): ?array
    {
        switch ($item[1]) {
            case 'like':
                $item[0] = "%$item[0]%";
                break;
            case 'between_time':
                if($item[0] == 'null') return null;
                $times = explode(',', $item[0]);
                $item[0] = [strtotime($times[0]), strtotime($times[1])];
                $item[1] = 'between';
                break;
            case 'between':
                if($item[0] == 'null') return null;
                $times = explode(',', $item[0]);
                $item[0] = [$times[0], $times[1]];
                break;
        }

        return [$key, $item[1], $item[0]];
    }
}