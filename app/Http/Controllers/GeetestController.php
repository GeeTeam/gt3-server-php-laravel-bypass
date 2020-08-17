<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Sdk\GeetestLib;
use App\Http\Controllers\CheckGeetestStatus;

require_once "geetest_config.php";

class GeetestController extends Controller
{
    // 验证初始化接口，GET请求
    public function first_register(Request $request)
    {
        /*
            必传参数
                digestmod 此版本sdk可支持md5、sha256、hmac-sha256，md5之外的算法需特殊配置的账号，联系极验客服
            自定义参数,可选择添加
                user_id 客户端用户的唯一标识，确定用户的唯一性；作用于提供进阶数据分析服务，可在register和validate接口传入，不传入也不影响验证服务的使用；若担心用户信息风险，可作预处理(如哈希处理)再提供到极验
                client_type 客户端类型，web：电脑上的浏览器；h5：手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生sdk植入app应用的方式；unknown：未知
                ip_address 客户端请求sdk服务器的ip地址
         */
        $gtLib = new GeetestLib(GEETEST_ID, GEETEST_KEY);
        $userId = "test";
        $digestmod = "md5";
        $params = [
            "digestmod" => $digestmod,
            "user_id" => $userId,
            "client_type" => "web",
            "ip_address" => "127.0.0.1"
        ];
        if(CheckGeetestStatus::getGeetestStatus()){
            $result = $gtLib->register($digestmod, $params);
        }else{
            $result = $gtLib->localInit();
        }
        // 注意，不要更改返回的结构和值类型
        return response($result->getData())->header('Content-Type', "application/json;charset=UTF-8");
    }

    // 二次验证接口，POST请求
    public function second_validate(Request $request)
    {
        $gtLib = new GeetestLib(GEETEST_ID, GEETEST_KEY);
        $challenge = $request->input(GeetestLib::GEETEST_CHALLENGE);
        $validate = $request->input(GeetestLib::GEETEST_VALIDATE);
        $seccode = $request->input(GeetestLib::GEETEST_SECCODE);
        $result = null;
        if (CheckGeetestStatus::getGeetestStatus()) {
            $result = $gtLib->successValidate($challenge, $validate, $seccode, null);
        } else {
            $result = $gtLib->failValidate($challenge, $validate, $seccode);
        }
        // 注意，不要更改返回的结构和值类型
        if ($result->getStatus() === 1) {
            return response()->json(["result" => "success", "version" => GeetestLib::VERSION]);
        } else {
            return response()->json(["result" => "fail", "version" => GeetestLib::VERSION, "msg" => $result->getMsg()]);
        }
    }

}