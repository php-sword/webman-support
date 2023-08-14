<?php

namespace sword\log;

use app\model\LogLevelModel;
use app\model\LogModel;
use app\service\AuthService;
use BadMethodCallException;
use sword\service\UtilsService;
use think\db\exception\DbException;
use Throwable;
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
     * @return LogModel
     * @throws DbException
     */
    public static function __callStatic($method, $args): LogModel
    {
        if (isset(self::LEVEL_LIST[$method])) {
            $levelId = self::LEVEL_LIST[$method];
            $levelName = $method;
        }else{
            //查询应用日志级别
            $level = LogLevelModel::where('label', $method)->find();
            if (!$level) {
                throw new BadMethodCallException("Log::$method() is not a valid method");
            }
            $levelId = $level->id;
            $levelName = $level->name;
        }

        $logData = $args[0];
        $logValue = $args[1] ?? '';
        $valueType = $args[2] ?? 'text';

        $data = [
            'level_id' => $levelId,
            'level_name' => $levelName,
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
     * @return LogModel
     */
    public static function log(string $level, string|array $data, mixed $value = [], string $valType = 'text'): LogModel
    {
        return self::$level($data, $value, $valType);
    }

    /**
     * 写入日志
     * @param array $data
     * @return LogModel
     * @throws DbException
     */
    private static function writeLog(array $data): LogModel
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
            if($request and (new AuthService)->checkLogin()){
                if($userId = JwtToken::getExtendVal('id')){
                    $data['request_user_id'] = $userId;
                }
                if($userName = JwtToken::getExtendVal('name')){
                    $data['request_user'] = $userName;
                }
            }
        }catch (Throwable){}

        $model = new LogModel();
        if(!$model->save($data)) {
            //日志保存到数据库失败
            throw new DbException('日志保存到数据库失败');
        }
        return $model;
    }

}