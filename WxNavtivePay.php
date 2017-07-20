<?php
/**
* 微信扫码支付
*
* 使用:
* 将本文件放到extend目录下即可
*
* 用法:
* 调用 \WxNavtivePay::pay($params) 即可生成支付二维码
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
class WxNavtivePay
{
    // APPID：绑定支付的APPID
    const APPID = '';
    // MCHID：商户号
    const MCHID = '';
    // KEY：商户支付密钥
    const KEY   = '';
    // REQUEST_URL: 请求地址
    const REQUEST_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    // NOTIFY_URL: 回调地址
    const NOTIFY_URL  = '';

    /**
     * 主入口, 创建扫码支付二维码
     * @param  array    $params 创建二维码所需要的参数, 只传递一下参数即可
     * @param  string   $params['out_trade_no']  商户订单号
     * @param  string   $params['body']         商品描述
     * @param  integer  $params['total_fee']    订单金额(分)
     * @return string 图片标签
     */
    public static function pay($params)
    {
        // 1.检测数据
        self::checkData($params);

        // 2.拼接参数
        $postArr = self::generateParam($params);

        // 3.生成签名 并 添加到数组中
        $sign = self::makeSign($postArr);
        $postArr['sign'] = $sign;

        // 4.转化为xml格式
        $postXml = self::arrayToXml($postArr);

        // 5.发送请求
        $response = self::http(self::REQUEST_URL ,$postXml);

        // 6.xml格式转为数组格式
        $response = self::xmlToArray($response);

        // 7.进行结果处理
        $img = self::processResponse($response);

        return $img;
    }

    /**
     * 统一处理错误
     * @param  string $errorMsg 错误信息
     */
    private static function processError($errorMsg)
    {
        throw new think\Exception($errorMsg);
    }

    /**
     * 参数校检
     */
    private static function checkData($params)
    {
        if (empty($params['out_trade_no'])) {
            self::processError("商户订单号不得为空");
        }

        if (empty($params['body'])) {
            self::processError("商品描述不得为空");
        }

        if (intval($params['total_fee']) <= 0) {
            self::processError("支付金额为正整数, 单位为分");
        }
    }

    /**
     * 生成请求数组
     */
    private static function generateParam($params)
    {
        $arr = [
            'body'                => $params['body'],
            'out_trade_no'        => $params['out_trade_no'],
            'product_id'          => $params['out_trade_no'],
            'total_fee'           => $params['total_fee'],
            'appid'               => self::APPID,
            'mch_id'              => self::MCHID,
            'notify_url'          => self::NOTIFY_URL,
            'nonce_str'           => self::getNoncestr(),
            'spbill_create_ip'    => $_SERVER['REMOTE_ADDR'],
            // 'spbill_create_ip'    => '1.1.1.1', //测试使用
            'trade_type'          => 'NATIVE',
        ];

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
     * 处理请求结果
     * @param  xml $response 请求结果
     */
    private static function processResponse($response)
    {
        // 1.判断信息是否返回成功
        if ($response['return_code'] == 'SUCCESS') {
            // 2. 判断订单是否成功
            if ($response['result_code'] == 'SUCCESS') {
                // 1.获取code_url值
                $code_url = $response['code_url'];

                // 2.使用微信在线生成二维码, 可以自己用类库生成
                $qrCode_url = $qrcodeStr = 'http://paysdk.weixin.qq.com/example/qrcode.php?data=' . urlencode($code_url);

                // 3.生成图片, 这里大小可以自定义, 或从配置文件读取
                $image = "<img alt='模式一扫码支付' src='{$qrcodeStr}' style='width:150px;height:150px;' />";
                return $image;
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
        $arr = json_decode(json_encode($xmlObj),true);

        return $arr;
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