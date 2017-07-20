<?php

/**
* 报关查询
*
* 使用:
* 将本文件放到extend目录下即可
*
* 用法:
* 调用 \WxCustomDeclareQuery::query($order_no, $customs) 即可完成查询
*
* 注意:
* 1.错误采用抛出异常的方式, 可根据自己的业务在统一接口进行修改
* 2.默认通过商户订单号(out_trade_no)查询, 可以通过修改常量 ORDER_TYPE 更改类型
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

class WxCustomDeclareQuery
{
    // APPID：绑定支付的APPID
    const APPID = '';
    // MCHID：商户号
    const MCHID = '';
    // KEY：商户支付密钥
    const KEY   = '';
    // 请求地址
    const REQUEST_URL = 'https://api.mch.weixin.qq.com/cgi-bin/mch/customs/customdeclarequery';
    // 查询方式: 商户订单号(out_trade_no) / 微信支付订单号(transaction_id) / 商户子订单号(sub_order_no) / 微信子订单号(sub_order_id)
    const ORDER_TYPE  = 'out_trade_no';

    /**
     * 主入口
     * @param  string $order_no 订单号
     * @param  string $customs  海关
     */
    public static function query($order_no, $customs)
    {
        // 1.校检数据
        if (empty($order_no)) {
            self::processError('订单号(order_no)不能为空');
        }

        if (empty($customs)) {
            self::processError('海关(customs)不得为空');
        }

        // 2.获取请求数组
        $postArr = self::generateParam($order_no, $customs);

        // 3.生成签名 并 添加到数组中
        $sign = self::makeSign($postArr);
        $postArr['sign'] = $sign;

        // 4.数组转化为xml格式
        $postXml = self::arrayToXml($postArr);

        // 5.发送请求
        $response = self::http(self::REQUEST_URL, $postXml);

        // 6.xml格式转为数组格式
        $response = self::xmlToArray($response);

        // 7.进行结果处理
        $result = self::processResponse($response);

        return $result;
    }

    /**
     * 处理请求结果
     * @param  xml $response 请求结果
     */
    private static function processResponse($response)
    {
        // 1.判断信息是否返回成功
        if ($response['return_code'] == 'SUCCESS') {
            // 2. 判断订单是否成功
            if ($response['result_code'] == 'SUCCESS') {
                return $response;
            } else {
                self::processError($response['err_code_des']);
            }
        } else {
            self::processError($response['return_msg']);
        }
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
     * POST方式, 进行http请求
     * @param  array $data 请求参数
     */
    public static function http($url, $data)
    {
        // 初始化
        $ch = curl_init();
        // 设置请求地址
        curl_setopt($ch, CURLOPT_URL, $url);
        // 设置超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT, 30);
        // 返回参数
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // 不严格校检
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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
     * 生成请求数组
     */
    private static function generateParam($order_no, $customs)
    {
        $arr = [
            'appid'     => self::APPID,
            'mch_id'    => self::MCHID,
            'customs'   => $customs,
            'sign_type' => 'MD5'
        ];

        switch (self::ORDER_TYPE) {
            case 'out_trade_no':
                $arr['out_trade_no'] = $order_no;
                break;
            case 'transaction_id':
                $arr['out_trade_no'] = $order_no;
                break;
            case 'sub_order_no':
                $arr['out_trade_no'] = $order_no;
                break;
            case 'out_trade_no':
                $arr['sub_order_id'] = $order_no;
                break;
            default:
                self::processError('订单类型配置错误, 请检查');
                break;
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
     * 统一处理错误
     * @param  string $errorMsg 错误信息
     */
    static function processError($errorMsg)
    {
        throw new think\Exception($errorMsg);
    }

}