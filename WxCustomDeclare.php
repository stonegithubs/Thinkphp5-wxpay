<?php

/**
* 报关接口
*
* 使用:
* 将本文件放到extend目录下即可
*
* 用法:
* 调用 \WxCustomDeclare::customDeclare($params) 即可完成申报
*
* 注意:
* 错误采用抛出异常的方式, 可根据自己的业务在统一接口进行修改
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
class WxCustomDeclare
{
    // APPID：绑定支付的APPID
    const APPID = '';
    // MCHID：商户号
    const MCHID = '';
    // KEY：商户支付密钥
    const KEY   = '';
    // REQUEST_URL: 请求地址
    const REQUEST_URL = 'https://api.mch.weixin.qq.com/cgi-bin/mch/customs/customdeclareorder';

    /**
     * 主入口
     * @param  array    $params 请求数组, 如果不拆分报关, 则只需要传一下三个参数即可
     * @param  string   $params['out_trade_no']  商户订单号
     * @param  string   $params['transaction_id'] 微信支付订单号
     * @param  integer  $params['customs']    订单金额(分)
     */
    public static function customDeclare($params)
    {
        // 1.检测数据
        self::checkData($params);

        // 2.拼接参数
        $postArr  = self::generateParam($params);

        // 3.生成签名 并 添加到数组中
        $sign     = self::makeSign($postArr);
        $postArr['sign'] = $sign;

        // 4.转化为xml格式
        $postXml  = self::arrayToXml($postArr);

        // 5.发送请求
        $response = self::http(self::REQUEST_URL, $postXml);

        // 6.xml格式转为数组格式
        $response = self::xmlToArray($response);

        // 7.进行结果处理
        $result   = self::processResponse($response);

        return $result;
    }

    /**
     * 参数校检
     */
    private static function checkData($params)
    {
        if (empty($params['out_trade_no'])) {
            self::processError("商户订单号(out_trade_no)不得为空");
        }

        if (empty($params['transaction_id'])) {
            self::processError("微信支付订单号(transaction_id)不得为空");
        }

        if (empty($params['customs'])) {
            self::processError("海关(customs)不得为空");
        }

        // 商户子订单号，如果存在, 则以下字段必传
        if (!empty($params['sub_order_no'])) {
            if (intval($params['transport_fee']) <= 0) {
                self::processError("物流费(transport_fee)为正整数, 单位为分");
            }

            if (intval($params['product_fee']) <= 0) {
                self::processError("商品价格(product_fee)为正整数, 单位为分");
            }
        }
    }

    /**
     * 生成请求数组
     */
    private static function generateParam($params)
    {
        $arr = [
            'appid'          => self::APPID,
            'mch_id'         => self::MCHID,
            'customs'        => $params['customs'],
            'out_trade_no'   => $params['out_trade_no'],
            'transaction_id' => $params['transaction_id']
        ];

        if (!empty($params['sub_order_no'])) {
            $arr['sub_order_no']  = $params['sub_order_no'];
            $arr['fee_type']      = 'CNY';
            $arr['transport_fee'] = $params['transport_fee'];
            $arr['product_fee']   = $params['product_fee'];
            $arr['order_fee']     = $params['transport_fee'] + $params['product_fee'];
        }

        return $arr;
    }

    /**
     * 处理请求结果
     * @param  xml $response 请求结果
     */
    private static function processResponse($response)
    {
        // 1.判断信息是否返回成功
        if ($response['return_code'] == 'SUCCESS') {
            // 2.判断订单是否成功
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
     * 统一处理错误, 可以根据业务需要进行日志记录和错误处理
     * @param  string $errorMsg 错误信息
     */
    public static function processError($errorMsg)
    {
        throw new think\Exception($errorMsg);
    }

    /**
     * 生成签名
     */
    public static function makeSign($postArr)
    {
        // 1. 排序
        ksort($postArr);

        // 2. 拼合成key=value形式
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
        $arr = json_decode(json_encode($xmlObj),true);

        return $arr;
    }

    /**
     * POST方式, 进行http请求
     * @param  array $data 请求参数
     * @param  $url 请求URL地址
     */
    public static function http($url, $data)
    {
        $ch = curl_init();
        // 设置URL
        curl_setopt($ch,CURLOPT_URL, $url);

        // 超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT, 30);

        // 返回字符串
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);

        //不严格校检
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

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