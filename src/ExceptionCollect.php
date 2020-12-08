<?php

namespace Woaap\Deploy;

use Illuminate\Support\Facades\Redis;

class ExceptionCollect
{

    private $date_time = '';
    private $minutes = 0;

    public function __construct()
    {
        $this->setDateTime();
        $this->setMinutes();
    }

    /**
     * 异常收集
     * @param $html
     */
    public function collect($html)
    {
        config([
            'database.redis.deploy-connection' => [
                'host' => '10.22.21.69',
                'password' => null,
                'port' => '6379',
                'database' => 1
            ]
        ]);
        $redis = Redis::connection('deploy-connection');
        $redis_key = config('app.name') . '_' . config('app.env') . ':' . $this->getRedisKey() ;
        $redis->hset($redis_key, $this->date_time . uniqid(), $html);
    }

    /**
     * 获取当前时间
     */
    private function setDateTime()
    {
        $this->date_time = time();
    }

    /**
     * 获取当前分钟
     */
    private function setMinutes()
    {
        $this->minutes = (int)date('i', $this->date_time);
    }

    /**
     * 计算redis key
     * @return false|string
     */
    private function getRedisKey()
    {
        $mod = (int)$this->minutes % 10;
        if($mod === 0){
            return date('YmdHi', $this->date_time);
        }
        return date('YmdH', $this->date_time) . ($this->minutes - $mod);
    }

}
