<?php
namespace app\dem\service;

use think\Db;

class WeChat
{
    private $appId = 'wxb9ed7d2280b5bcb3';
    private $appSecret = '5edf8cca75794772bd3eb8384fc7ea0c';

    /**
     * 微信登录
     * @code  获取 token, unionid
     */
    public function login($code)
    {
        $appId      = $this->appId;
        $appSecret  = $this->appSecret;
        $grant_type = "authorization_code";
        $url = "https://api.weixin.qq.com/sns/jscode2session?"."appid=".$appId."&secret=".$appSecret."&js_code=".$code."&grant_type=".$grant_type;
        $data = $this->curlHttp($url);
        if(empty($data['openid'])){
            return  json_encode(['code'=>$code,'msg'=>"登录异常",'data'=>'']);
        }
        $list['openid']  = $data['openid'];
        $list['session_key']  = $data['session_key'];
        return $list;
    }

    public function token($openid,$session_key){
        return md5($openid.$session_key);
    }

    public function getAccessToken(){
        //获取access_token
        $access_token = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
        //缓存access_token
        session_start();
        $_SESSION['access_token'] = "";
        $_SESSION['expires_in'] = 0;
        $ACCESS_TOKEN = "";
        if (!isset($_SESSION['access_token']) || (isset($_SESSION['expires_in']) && time() > $_SESSION['expires_in'])) {
            $json =  curlHttp($access_token);


            $_SESSION['access_token'] = $json['access_token'];
            $_SESSION['expires_in'] = time() + 7200;
            $ACCESS_TOKEN = $json["access_token"];
        } else {

            $ACCESS_TOKEN = $_SESSION["access_token"];
        }
        return $ACCESS_TOKEN;
    }

    /**
     * 生成二维码
     * @param $path
     * @return string
     */
    public function createQrcode($path,$width=150){

        $ACCESS_TOKEN=$this->getAccessToken();

        //构建请求二维码参数
        //path是扫描二维码跳转的小程序路径，可以带参数?id=xxx
        //width是二维码宽度
        $qcode = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=$ACCESS_TOKEN";

        $param = json_encode(array("path" => $path, "width" =>$width ));
        //POST参数
        $result = $this->httpRequest( $qcode, $param,'POST');
        //生成二维码
        file_put_contents("qrcode.png", $result);
        $base64_image = "data:image/jpeg;base64," . base64_encode($result);
        return $base64_image;
    }
    public function getBuiNssWxACode($path){
        $member_id =get_header_id("token");
        $store = Db::name("store_apply")
            ->field("company_name")
            ->where(['member_id'=>$member_id])
            ->find();
        $data['shop_name'] = $store['company_name'];

        $path1 = ROOT_PATH.'public'.DS."Qrcode".DS;
//        if( !file_exists( $path1 ) )
//        {
//            mkdir($path1,0777,true);
//        }
        $savePath = $path1.$member_id.".png";
//        if(file_exists($savePath))
//        {
//            $data['invt_qrcode'] = "/Qrcode/".$member_id.".png";
//            return $data;
//        }
        $ACCESS_TOKEN=$this->getAccessToken();
        //构建请求二维码参数
        //path是扫描二维码跳转的小程序路径，可以带参数?id=xxx
        //width是二维码宽度
        $qcode = "https://api.weixin.qq.com/wxa/getwxacode?access_token=$ACCESS_TOKEN";

        $param = json_encode(array("path" => $path));
        //POST参数
        $result = $this->httpRequest( $qcode, $param,'POST');
        //生成二维码
        file_put_contents($savePath, $result);
        $base64_image = "data:image/jpeg;base64," . base64_encode($result);
        $data['invt_qrcode'] = $base64_image;
        return $data;
    }

    public function getWxACode($path){
        $ACCESS_TOKEN=$this->getAccessToken();

        //构建请求二维码参数
        //path是扫描二维码跳转的小程序路径，可以带参数?id=xxx
        //width是二维码宽度
        $qcode = "https://api.weixin.qq.com/wxa/getwxacode?access_token=$ACCESS_TOKEN";

        $param = json_encode(array("path" => $path));
        //POST参数
        $result = $this->httpRequest( $qcode, $param,'POST');
        //生成二维码
        file_put_contents("qrcode.png", $result);
        $base64_image = "data:image/jpeg;base64," . base64_encode($result);
        return $base64_image;
    }

    public function  httpRequest($url, $data='', $method='GET'){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        if($method=='POST')
        {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data != '')
            {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }


    function curlHttp( $url,$type='GET', $data='')
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); //// https请求 不验证证书和hosts
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        if( $type == 'GET')
        {
            curl_setopt($curl, CURLOPT_HEADER, FALSE);  //不返回报文头部
        }else
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json;charset=utf-8'));
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output,TRUE);
    }




}