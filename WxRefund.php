<?php

/**
* 退款申请接口
*
* 使用:
* 将本文件放到extend目录下即可
*
* 用法:
* 调用 \WxRefund::refund($params) 即可完成退款申请
*
* 注意:
* 1.错误采用抛出异常的方式, 可根据自己的业务在统一接口进行修改
* 2.默认通过商户订单号(out_trade_no)退款, 可以通过修改常量ORDER_TYPE更改为 微信订单号(transaction_id)
*
* ----------------- 求职 ------------------
* 姓名: zhangchaojie      邮箱: zhangchaojie_php@qq.com  应届生
* 期望职位: PHP初级工程师 薪资: 3500  地点: 深圳(其他城市亦可)
* 能力:
*     1.熟悉小程序开发, 前后端皆可, 前端一日可做5-10个页面, 后端可写接口
*     2.后端, PHP基础知识扎实, 熟悉ThinkPHP5框架, 用TP5做过CMS, 商城, API接口
*     3.MySQL, Linux都在进行进一步学习
*
* 如有大神收留, 请发送邮件告知, 必将感激涕零!
*/
class WxRefund
{
    // APPID：绑定支付的APPID
    const APPID = '';
    // MCHID：商户号
    const MCHID = '';
    // KEY：商户支付密钥
    const KEY   = '';
    // 请求地址
    const REQUEST_URL = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    // 查询方式
    const ORDER_TYPE  = 'out_trade_no';

    /**
     * 主入口
     * @param  array $params 退款参数, 具体如下:
     * @param  string   $params['order_no']  商户订单号/微信订单号
     * @param  string   $params['refund_no']  退款单号
     * @param  integer  $params['total_fee']    订单总金额(分)
     * @param  integer  $params['refund_fee']    退款金额(分)
     */
    public static function refund($params)
    {
        // 1.检测参数
        self::checkData($params);

        // 2.拼接参数
        $postArr  = self::generateParam($params);

        // 3.生成签名 并 添加到数组中
        $sign     = self::makeSign($postArr);
        $postArr['sign'] = $sign;

        // 4.转化为xml格式
        $postXml  = self::arrayToXml($postArr);

        // 5.发送请求
        $response = self::http(self::REQUEST_URL ,$postXml);

        // 6.xml格式转为数组格式
        $response = self::xmlToArray($response);

        // 7.进行结果处理
        $result   = self::processResponse($response);

        return $result;
    }

    /**
     * 统一处理错误
     * @param  string $errorMsg 错误信息
     */
    public static function processError($errorMsg)
    {
        throw new think\Exception($errorMsg);
    }

    /**
     * 校检参数
     */
    private static function checkData($params)
    {
        if (empty($params['order_no'])) {
            self::processError("订单号不得为空");
        }

        if (empty($params['refund_no'])) {
            self::processError("退款单号");
        }

        if (intval($params['total_fee']) <= 0) {
            self::processError("订单总金额为正整数, 单位为分");
        }

        if (intval($params['refund_fee']) <= 0) {
            self::processError("退款金额为正整数, 单位为分");
        }
    }

    /**
     * 生成请求数组
     */
    private static function generateParam($params)
    {
        $arr = [
            'appid'         => self::APPID,
            'mch_id'        => self::MCHID,
            'nonce_str'     => self::getNoncestr(),
            'total_fee'     => $params['total_fee'],
            'refund_fee'    => $params['refund_fee'],
            'out_refund_no' => $params['refund_no']
        ];

        // 商户订单号 or 微信订单号
        if(self::ORDER_TYPE == 'out_trade_no') {
            $arr['out_trade_no']   = $params['order_no'];
        } else {
            $arr['transaction_id'] = $params['order_no'];
        }

        return $arr;
    }


    /**
     * 生成签名
     */
    private static function makeSign($postArr)
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
     * 获取随机字符
     */
    public static function getNoncestr()
    {
        $str = time() . substr(microtime(), -5 , 5) . mt_rand(1000, 9999) . ip2long($_SERVER['REMOTE_ADDR']);
        return $str;
    }


    /**
     * xml转数组
     * @param  string $xml xml字符串
     * @return array
     */
    public static function xmlToArray($xml)
    {
        $xmlObj = simplexml_load_string($xml,'SimpleXMLElement', LIBXML_NOCDATA);
        $arr    = json_decode(json_encode($xmlObj), true);
        return $arr;
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
     * POST方式, 进行http请求, 并且需要携带证书
     * @param  array $data 请求参数
     */
    public static function http($url, $data)
    {
        // 初始化
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);

        // 超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT, 30);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);

        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);

        // 不严格校检
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

        //以下两种方式需选择一种

        // 第一种方法，cert 与 key 分别属于两个.pem文件
        // 默认格式为PEM，可以注释
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLCERT, EXTEND_PATH.'/pem/apiclient_cert.pem');
        // 默认格式为PEM，可以注释
        curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLKEY, EXTEND_PATH.'/pem/apiclient_key.pem');

        //第二种方式，两个文件合成一个.pem文件
        // curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');

        // post方式
        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);

        // 执行
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            // 存在错误则抛出异常
            self::processError(curl_error($ch));
        }

        curl_close($ch);
        return $response;
    }

    /**
     * 处理请求结果
     * @param  xml $response 请求结果
     */
    private static function processResponse($response)
    {
        // 1.判断信息是否返回成功
        if ($response['return_code'] == 'SUCCESS') {
            // 2. 判断订单是否存在
            if ($response['result_code'] == 'SUCCESS') {
                return $response;
            } else {
                self::processError($response['err_code_des']);
            }
        } else {
            self::processError($response['return_msg']);
        }
    }
}