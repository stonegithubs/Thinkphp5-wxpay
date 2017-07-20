<?php

namespace wxpay;

/**
* 微信支付基础类
*
* 说明:
* 子类仅与本父类有关系, 如果仅需一种支付, 只拷贝本文件和继承的文件即可
*
* ----------------- 求职 ------------------
* 姓名: zhangchaojie  邮箱: zhangchaojie_php@qq.com  应届生
* 期望职位: PHP初级工程师 薪资: 3500  地点: 深圳(其他城市亦可)
* 能力:
*     1.熟悉小程序开发, 前后端皆可, 前端一日可做5-10个页面, 后端可写接口
*     2.后端, PHP基础知识扎实, 熟悉ThinkPHP5框架, 用TP5做过CMS, 商城, API接口
*     3.MySQL, Linux都在进行进一步学习
*
* 如有大神收留, 请发送邮件告知, 必将感激涕零!
*/
class WxBase
{
    // APPID：绑定支付的APPID
    const APPID = '';
    // MCHID：商户号
    const MCHID = '';
    // KEY：商户支付密钥
    const KEY   = '';

    /**
     * 统一处理错误, 可以根据业务需要进行日志记录和错误处理
     * @param  string $errorMsg 错误信息
     */
    protected static function processError($errorMsg)
    {
        throw new \think\Exception($errorMsg);
    }

    /**
     * 生成签名
     */
    protected static function makeSign($postArr)
    {
        // 1. 排序
        ksort($postArr);

        // 2. 拼合成key = value形式
        $sign = urldecode(http_build_query($postArr));

        // 3. 拼接API密钥
        $sign = $sign . '&key=' . self::KEY;

        // 4. MD5加密后大写
        $sign = strtoupper(md5($sign));
        return $sign;
    }

    /**
     * 数组转为xml格式
     */
    public static function arrayToXml($arr)
    {
        $xml = '<xml>';
        foreach ($arr as $key => $value) {
            $xml .= "<{$key}>{$value}</{$key}>";
        }
        $xml .= '</xml>';

        return $xml;
    }

    /**
     * xml转数组
     * @param  string $xml xml字符串
     * @return array
     */
    public static function xmlToArray($xml)
    {
        $xmlObj = simplexml_load_string($xml,'SimpleXMLElement', LIBXML_NOCDATA);
        $arr    = json_decode(json_encode($xmlObj),true);

        return $arr;
    }

    /**
     * POST方式, 进行http请求
     * @param  array $data 请求参数
     */
    public static function http( $url, $data, $cert=false)
    {
        $ch = curl_init();
        // 设置URL
        curl_setopt($ch,CURLOPT_URL, $url);

        // 超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT, 30);

        // 返回字符串
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);

        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);

        //不严格校检
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

        //以下两种方式需选择一种
        if ($cert) {
            // 第一种方法，cert 与 key 分别属于两个.pem文件
            // 默认格式为PEM，可以注释
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, EXTEND_PATH.'/wxpay/pem/apiclient_cert.pem');
            // 默认格式为PEM，可以注释
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, EXTEND_PATH.'/wxpay/pem/apiclient_key.pem');

            //第二种方式，两个文件合成一个.pem文件
            // curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');
        }

        // 请求方式为POST
        curl_setopt($ch,CURLOPT_POST, 1);

        // 请求参数
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);

        // 执行请求
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            // 存在错误则抛出异常
            self::processError(curl_error($ch));
        }
        curl_close($ch);

        return $response;
    }

    /**
     * 获取随机字符
     */
    public static function getNoncestr()
    {
        $str = time() . substr(microtime(), -5 , 5) . mt_rand(1000, 9999) . ip2long($_SERVER['REMOTE_ADDR']);

        return $str;
    }
}