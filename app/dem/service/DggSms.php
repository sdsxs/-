<?php
namespace app\dem\service;


class DggSms{
    private  $header;
    private  $url;
    public function __construct()
    {
    }

    public function curl($url, $data = [], $header, $method = 'POST', $time_out = 3)
    {
        $curl = curl_init();
        // 设置curl允许执行的最长秒数

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $time_out);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        // 执行操作
        $result = curl_exec($curl);

        curl_close($curl);
        return $result;
    }


}
?>