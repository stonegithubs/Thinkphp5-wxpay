<?php
/**
* 小程序支付
*
* 使用:
* 将本文件放到extend目录下即可
*
* 使用步骤(详细参考开发文档):
* -----第一阶段 获取并存储openid----------
* 1. 小程序: 调用wx.login(), 获取code登录标识
* 2. 小程序: 调用wx.request(), 向服务器发送请求
* 3. 服务端: 调用 \WxAppPay::getOpenid($cide) 获取openid
* 4. 服务端: 存储openid, 并设置过期时间(为了安全起见, 不可永久有效)
* -----第二阶段 进行支付获取预订单信息-----
* 5. 小程序: 调用wx.request(), 向服务器发送请求
* 6. 服务端: 通过用户身份, 找到对应用户的, 从缓存/数据中获取openid
* 7. 服务端: 调用\WxAppPay::prePay($params) 获取预订单信息 prepay_id
* 8. 服务端: 调用\WxAppPay::getPayData($prepay_id) 获取小程序支付参数, 返回给小程序
* -----第三阶段 小程序拉起支付 -------
* 9. 小程序: 调用wx.requestPayment()拉起支付
* -----第四阶段 回调通知处理----------
* 10. WxNotify.php文件专门处理通知哦~
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
class WxAppPay
{
    // APPID：微信分配的小程序ID
    const APPID       = '';
    // 小程序密钥
    const APPSECRET   =  '';
    // MCHID：商户号
    const MCHID       = '';
    // KEY：商户支付密钥
    const KEY         = '';
    // REQUEST_URL: 下单请求地址
    const REQUEST_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    // NOTIFY_URL: 支付回调地址
    const NOTIFY_URL  = '';
    // LOGIN_URL: 获取openId地址
    const LOGIN_URL   = 'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code';

    /**
     * 第一步: 获取openId
     * @param  string  $code 登录凭证, 通过小程序wx.login()获取
     * @return string       openID
     */
    public static function getOpenid($code)
    {
        // 拼接URL
        $url    = sprintf(self::LOGIN_URL, self::APPID, self::APPSECRET, $code);
        // 发送请求
        $result = self::http($url, '', false);
        // json转为数组
        $result = json_decode($result, true);

        // 结果判断
        if (empty($result['openid'])) {
            self::processError('获取openid失败, 失败原因: '.$result['errmsg']);
        };

        return $result['openid'];
    }

    /**
     * 第二步: 生成预付单
     * @param  array    $params 预支付参数
     * @param  string   $params['out_trade_no']  商户订单号
     * @param  string   $params['body']          商品描述
     * @param  string   $params['openid']        用户标识
     * @param  integer  $params['total_fee']     订单金额(分)
     */
    public static function prePay($params)
    {
        // 1.校检预付单数据
        self::checkPrePayData($params);

        // 2.拼接参数
        $postArr  = self::generatePrePayParam($params);

        // 3.生成签名 并 添加到数组中
        $sign     = self::makeSign($postArr);
        $postArr['sign'] = $sign;

        // 4.转化为xml格式
        $postXml  = self::arrayToXml($postArr);

        // 5.发送请求
        $response = self::http(self::REQUEST_URL, $postXml);

        // 6.xml格式转为数组格式
        $response = self::xmlToArray($response);

        // 7.进行结果处理, 失败抛异常
        $result   = self::processResponse($response);

        return $result;
    }

    /**
     * 第三步: 生成小程序需要的支付参数
     * @param  string $prepay_id 预支付参数, 通过pre_pay()获得
     * @return array
     */
    public static function getPayData($prepay_id)
    {
        $arr = [
            'appId'     => self::APPID,
            'timeStamp' => time(),
            'nonceStr'  => self::getNoncestr(),
            'package'   => 'prepay_id='.$prepay_id
        ];

        $paySign = self::makeSign($arr);
        $arr['paySign'] = $paySign;

        return $arr;
    }

    /**
     * 参数预付单校检
     */
    private static function checkPrePayData($params)
    {
        if (empty($params['out_trade_no'])) {
            self::processError("商户订单号(out_trade_no)不得为空");
        }

        if (empty($params['openid'])) {
            self::processError("用户标识(openid)不得为空");
        }

        if (empty($params['body'])) {
            self::processError("商品描述(body)不得为空");
        }

        if (intval($params['total_fee']) <= 0) {
            self::processError("支付金额(total_fee)为正整数, 单位为分");
        }
    }

    /**
     * 生成预付单请求数组
     */
    private static function generatePrePayParam($params)
    {
        $arr = [
            'appid'               => self::APPID,
            'mch_id'              => self::MCHID,
            'nonce_str'           => self::getNoncestr(),
            'body'                => $params['body'],
            'out_trade_no'        => $params['out_trade_no'],
            'total_fee'           => $params['total_fee'],
            'spbill_create_ip'    => $_SERVER['REMOTE_ADDR'],
            // 'spbill_create_ip'    => '1.1.1.1', //测试使用
            'notify_url'          => self::NOTIFY_URL,
            'trade_type'          => 'JSAPI',
            'openid'              => $params['openid']
        ];

        return $arr;
    }

    /**
     * 处理预付单请求结果
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

    /**
     * 统一处理错误
     * @param  string $errorMsg 错误信息
     */
    private static function processError($errorMsg)
    {
        throw new think\Exception($errorMsg);
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
     * http请求
     * @param  string $url 请求地址
     * @param  array $data 请求参数
     */
    public static function http($url, $data='', $post=true)
    {
        // 初始化
        $ch = curl_init();
        // 设置请求地址
        curl_setopt($ch, CURLOPT_URL, $url);
        // 设置超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT, 30);
        // 返回参数
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($post) {
             // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

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