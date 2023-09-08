<?php

namespace sword\log;

use BadMethodCallException;
use sword\service\UtilsService;
use think\db\exception\DbException;
use Throwable;
use Tinywan\Jwt\Exception\JwtTokenException;
use Tinywan\Jwt\JwtToken;

/**
 * 数据库日志工具
 * @method static void debug(string|array $data, mixed $value = [], string $valType = 'text')
 * @method static void info(string|array $data, mixed $value = [], string $valType = 'text')
 * @method static void notice(string|array $data, mixed $value = [], string $valType = 'text')
 * @method static void warning(string|array $data, mixed $value = [], string $valType = 'text')
 * @method static void error(string|array $data, mixed $value = [], string $valType = 'text')
 * @method static void critical(string|array $data, mixed $value = [], string $valType = 'text')
 * @method static void alert(string|array $data, mixed $value = [], string $valType = 'text')
 * @method static void emergency(string|array $data, mixed $value = [], string $valType = 'text')
 */
class Log
{

    /**
     *  日志级别缓存
     * @var array
     */
    private static array $levelMap = [];

    /**
     * 系统日志级别
     */
    const LEVEL_LIST = [
        'debug' => 1,
        'info' => 2,
        'notice' => 3,
        'warning' => 4,
        'error' => 5,
        'critical' => 6,
        'alert' => 7,
        'emergency' => 8,
    ];

    /**
     * @param $method
     * @param $args
     * @return SupportLogModel
     * @throws DbException
     */
    public static function __callStatic($method, $args): SupportLogModel
    {

        //查询应用日志级别
        if(isset(self::$levelMap[$method])) {
            $level = self::$levelMap[$method];
        }else{
            $level = SupportLogLevelModel::where('label', $method)->find();
            if (!$level) {
                throw new BadMethodCallException("Log::$method() is not a valid method");
            }
            self::$levelMap[$method] = $level;
        }

        $logData = $args[0];
        $logValue = $args[1] ?? '';
        $valueType = $args[2] ?? 'text';

        $data = [
            'level_id' => $level['id'],
            'level_name' => $level['name'],
            'title' => $logData,
            'value' => $logValue,
            'value_type' => $valueType
        ];
        if(is_array($logData)){
            $data = array_merge($data, $logData);
        }

        return self::writeLog($data);
    }

    /**
     * 记录其他日志
     * @param string $level
     * @param string|array $data
     * @param mixed $value
     * @param string $valType
     * @return SupportLogModel
     */
    public static function log(string $level, string|array $data, mixed $value = [], string $valType = 'text'): SupportLogModel
    {
        return self::$level($data, $value, $valType);
    }

    /**
     * 写入日志
     * @param array $data
     * @return SupportLogModel
     * @throws DbException
     */
    private static function writeLog(array $data): SupportLogModel
    {
        $request = request();
        //自动填充请求来源信息
        if(empty($data['request_source'])){
            if($request){
                $data['request_source'] = UtilsService::getRequestSource($request);
            }else{
                $traces = UtilsService::getCallFunc(3);
                $data['request_source'] = $traces;
            }
        }

        try{
            //填充IP地址
            if($request and $ip = $request->getRealIp()){
                $data['request_ip'] = $ip;
            }
            //填充用户数据
            if($request and self::checkLogin()){
                if($userId = JwtToken::getExtendVal('id')){
                    $data['request_user_id'] = $userId;
                }
                if($userName = JwtToken::getExtendVal('name')){
                    $data['request_user'] = $userName;
                }
            }
        }catch (Throwable){}

        $model = new SupportLogModel();
        if(!$model->save($data)) {
            //日志保存到数据库失败
            throw new DbException('日志保存到数据库失败');
        }
        return $model;
    }

    /**
     * 检查是否登录
     * @return bool
     */
    private static function checkLogin(): bool
    {
        if(!request()) return false;
        try{
            JwtToken::getCurrentId();
        }catch (JwtTokenException){
            return false;
        }
        return true;
    }
}