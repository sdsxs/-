<?php
declare(strict_types=1);
namespace app\dem\service;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SpSignService
{

    /**
     * 应用ID
     *
     * @var string
     */
    protected $appId;

    /**
     * 密钥
     *
     * @var string
     */
    protected $secretKey;

    /**
     * @var string|null
     */
    protected $nonce;

    /**
     * 基础路径
     * @var string|null
     */
    protected $base_uri;

    /**
     * 请求路径
     * @var string|null
     */
    protected $request_url;

    /**
     * 请求方式 get,post,option...
     * @var string|null
     */
    protected $method;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SpSignService constructor.
     */
    public function __construct()
    {

        $this->appId = 'coo-recruit';

        $this->secretKey = '5a57ba8ae6b011bf3a94701003af366a';
    }

    /**
     * 创建请求头
     *
     * @return array
     */
    public function getRequestHeader($phone)
    {
        $headers['syscode'] = $this->appId;

        list($msec, $sec) = explode(' ', microtime());

        $headers['time'] = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        $headers['nonce'] = $this->nonce ?? md5($phone . (string)$headers['time']);

        return $headers;
    }

    /**
     * @param array $requestData
     * @return array
     */
    public function getRequestHeaderWhitSign(array $requestData,$phone)
    {
        $headers = $this->getRequestHeader($phone);

        $headers['Content-Type'] = 'application/json';

        $need_md5_str = $this->asc_sort(['nonce' => $headers['nonce'], 'time' => $headers['time'], 'data' => json_encode($requestData)]);

        $need_md5_str .= '&sysCode=' . $this->appId . '&secret=' . $this->secretKey;

        $headers['sign'] = strtoupper(md5($need_md5_str));

        return $headers;
    }
    /**
     * ascii码从小到大排序
     *
     * @param array $params
     * @return bool|string
     */
    function asc_sort($params = array())
    {
        if (!empty($params)) {

            $p = ksort($params);

            if ($p) {

                $str = '';

                foreach ($params as $k => $val) {
                    $str .= $k . '=' . $val . '&';
                }

                return rtrim($str, '&');
            }
        }

        return false;
    }

    public function send($phone,$code){
        $data  =  [
            [
                "syscode"=>"coo-recruit",
                "msgTemplateId"=> "427808223338210054",
                "contentParamMap"=> [
                    //"text"=> '您的验证码为：${code},验证码1分钟内有效，请勿泄露他人。'
                    "code"=> "$code",
                ],
                "recverMap"=>[
                    "SMS"=> [
                        //"code"=> "123456",
                        "contact"=>"$phone"
                    ]
                ],
                "recverName"=> "$phone",
                "recverType"=> 0,
                "senderName"=>"薯片人才库",
                "title"=>"薯片人才库人才招聘"
            ]
        ];
        $url       =   'https://spmicrouag.shupian.cn/msgcenter/api/v1/msgcenter/external/tpp_batch_send_message.do';
        $header    =   $this->getRequestHeaderWhitSign($data,$phone);
        $options = json_encode($data, JSON_UNESCAPED_UNICODE);
        $jsonData = [
            'body' => $options,
            'headers' => $header
        ];
        try {
            $client = new Client();
            //发送http post请求
            $result     = $client->post($url, $jsonData);
        } catch (GuzzleException $e){
            print($e->getMessage());
        }
    }
}