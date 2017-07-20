<?php

namespace wxpay;

/**
* 微信退款申请查询
*
* 使用:
* 将本文件放到extend目录下即可
*
* 用法:
* 调用 \wxpay\WxRefundQuery::query($order_no) 即可完成微信退款申请查询
*
* 注意:
* 1.错误采用抛出异常的方式, 可根据自己的业务在统一接口进行修改
* 2.默认通过商户订单号(out_trade_no)查询, 可以通过修改常量ORDER_TYPE更改为: 商户订单号(out_trade_no) / 微信订单号(transaction_id) / 商户退款单号(out_refund_no) / 微信退款单号(refund_id) 其中之一
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

class WxRefundQuery extends WxBase
{
    // 请求地址
    const REQUEST_URL = 'https://api.mch.weixin.qq.com/pay/refundquery';
    // 查询方式, 可以为一下值:
    // 商户订单号(out_trade_no) / 微信订单号(transaction_id) / 商户退款单号(    out_refund_no) / 微信退款单号(refund_id)
    const ORDER_TYPE  = 'out_trade_no';

    /**
     * 主入口
     * @param  string $order_no  商户订单号/微信订单号/商户退款单号/微信退款单号 其中之一
     */
    static public function query($order_no)
    {
        // 1.校检数据
        if (empty($order_no)) {
            self::processError('单号不能为空');
        }

        // 2.获取请求数组
        $postArr  = self::generateParam($order_no);

        // 3.生成签名 并 添加到数组中
        $sign     = self::makeSign($postArr);
        $postArr['sign'] = $sign;

        // 4.数组转化为xml格式
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
     * 生成请求数组
     */
    static private function generateParam($order_no)
    {
        $arr = [
            'appid'     => self::APPID,
            'mch_id'    => self::MCHID,
            'nonce_str' => self::getNoncestr(),
        ];

        // 商户订单号(out_trade_no) / 微信订单号(transaction_id) / 商户退款单号(    out_refund_no) / 微信退款单号(refund_id)
        switch (self::ORDER_TYPE) {
            case 'transaction_id':
                $arr['out_trade_no']   = $order_no;
                break;
            case 'out_refund_no':
                $arr['out_refund_no']  = $order_no;
                break;
            case 'refund_id':
                $arr['refund_id']      = $order_no;
                break;
            default:
                $arr['transaction_id'] = $order_no;
                break;
        }

        return $arr;
    }

    /**
     * 处理请求结果
     * @param  xml $response 请求结果
     */
    static private function processResponse($response)
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