<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Controller;

require_once "geetest_config.php";

class CheckGeetestStatus extends Controller
{
    const HTTP_TIMEOUT_DEFAULT = 5; // 单位：秒
    const BYPASS_URL = "https://bypass.geetest.com/v1/bypass_status.php";
    const GEETEST_STATUS_KEY = "REDIS_CHECK_GEETEST_STATUS_KEY";

    /**
     * 发送GET请求，获取服务器返回结果
     */
    private function httpGet($url, $params)
    {
        $url .= "?" . http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::HTTP_TIMEOUT_DEFAULT); // 设置连接主机超时（单位：秒）
        curl_setopt($ch, CURLOPT_TIMEOUT, self::HTTP_TIMEOUT_DEFAULT); // 允许 cURL 函数执行的最长秒数（单位：秒）
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /**
     * 请求极验 bypass 接口, 将极验云服务状态记录到 redis 中
     */
    public function checkStatus()
    {
        $url = self::BYPASS_URL;
        $params = array("gt" => GEETEST_ID);
        try {
            $resBody = $this->httpGet($url, $params);
            \Log::info("gtlog: checkStatus(): 发送bypass请求, 返回body=" . $resBody);
            $res_array = json_decode($resBody, true);
            $status = $res_array["status"];
        } catch (\Exception $e) {
            $status = "fail";
        }
        if($status != null && $status != ""){
            Redis::set(self::GEETEST_STATUS_KEY, $status);
        }else{
            Redis::set(self::GEETEST_STATUS_KEY, "fail");
        }
    }

    /**
     * 获取 redis 中的极验云服务状态
     */
    public static function getGeetestStatus()
    {
        $status = Redis::get(self::GEETEST_STATUS_KEY);
        \Log::info("gtlog: getsGeetestStatus(): 获取redis中缓存的极验云状态, status=" . $status);
        if(strcmp($status,"success")==0){
            return true;
        }else{
            return false;
        }
    }
}