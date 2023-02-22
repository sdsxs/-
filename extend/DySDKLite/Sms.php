<?php
namespace DySDKLite;

use DySDKLite\SignatureHelper;

class Sms
{
    // AccessKeyID
    protected $access_id;
    // AccessKeySecret
    protected $access_key;
    // Endpoint
    protected $end_point;
    // Endpoint
    protected $topic;
    // 短信模版
    protected $template_code;
    // 短信签名
    protected $sign_name;
    // 发送手机号码
    protected $phone;
    // 发送短信码
    protected $code;

    public function __construct()
    {
        $this->access_id     = 'LTAI5tMZeJ1LYLydXx37qRrc';
        $this->access_key    = '28wWYdSxrHlXDBZQgP7XKy8TUgtYST';
        $this->end_point     = 'dysmsapi.aliyuncs.com';
        $this->topic         = 'sms.topic-cn-hangzhou';
        $this->template_code = 'SMS_229641522';
        $this->sign_name     = '顶呱呱人才系统';
        $this->var1          = 'code';
    }

    /**
     * 发送通知消息
     *
     * @params $phone int 手机号码
     * @params $code int 验证码
     * @return boolean
     */
    public function sendMessage($template_code, $phone, $name)
    {
        $params = array ();

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = $this->access_id;
        $accessKeySecret = $this->access_key;

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $phone;

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = $this->sign_name;

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = $template_code;

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = Array (
            "name" => $name,
        );

        // fixme 可选: 设置发送短信流水号
        // $params['OutId'] = "12345";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        // $params['SmsUpExtendCode'] = "1234567";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        );

        return $content;
    }

    /**
     * 发送短信验证码
     *
     * @params $phone int 手机号码
     * @params $code int 验证码
     * @return boolean
     */
    public function send($phone, $code)
    {
        $params = array ();

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = $this->access_id;
        $accessKeySecret = $this->access_key;

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $phone;

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = $this->sign_name;

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = $this->template_code;

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = Array (
            "code" => $code,
        );

        // fixme 可选: 设置发送短信流水号
        // $params['OutId'] = "12345";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        // $params['SmsUpExtendCode'] = "1234567";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        );

        return $content;
    }
}
?>