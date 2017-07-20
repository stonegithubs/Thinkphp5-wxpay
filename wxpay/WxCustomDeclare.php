<?php

namespace wxpay;

/**
* 报关接口
*
* 使用:
* 将本文件放到extend目录下即可
*
* 用法:
* 调用 \wxpay\WxCustomDeclare::customDeclare($params) 即可完成申报
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
class WxCustomDeclare extends WxBase
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
}