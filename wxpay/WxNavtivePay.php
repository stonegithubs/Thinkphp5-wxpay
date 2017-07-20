<?php

namespace wxpay;

/**
* 微信扫码支付
*
* 使用:
* 将本文件放到extend目录下即可
*
* 用法:
* 调用 \wxpay\WxNavtivePay::pay($params) 即可生成支付二维码
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
class WxNavtivePay extends WxBase
{
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
        $response = self::http(self::REQUEST_URL, $postXml);

        // 6.xml格式转为数组格式
        $response = self::xmlToArray($response);

        // 7.进行结果处理
        $img = self::processResponse($response);

        return $img;
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
}