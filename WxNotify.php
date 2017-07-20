<?php
/**
* 支付回调处理类
*
* 使用:
* 将本文件放到extend目录下即可
*
* 使用建议:
* 1.首先调用 \WxNotiy::getNotitfyData() 获取微信发送数据
* 2.其次调用 \WxNotiy::checkNotifyData($data) 检测数据是否合法
* 3.处理自己业务逻辑
* 4.根据以上结果调用 \WxNotiy::responseSuccess() 或者 \WxNotiy::responseFail() 响应微信服务器;
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
class WxNotiy
{
    // KEY：商户支付密钥
    const KEY = '';

    /**
     * 获取回调通知xml数据, 并转化为数组
     * @return array
     */
    static public function getNotitfyData()
    {
        // 获取xml数据
        $xmlData = file_get_contents("php://input");

        // 转化为数组形式
        return json_decode(json_encode(simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * 校检数据
     */
    static public function checkNotifyData($data)
    {
        // 1.判断数据是否获取成功
        if ($data['return_code']=='SUCCESS') {
            // 2.判断业务是否成功
            if($data['result_code']=='SUCCESS'){
                // 3.判断签名是否正确
                if (!checkSign($data)) {
                    $errMsg = '签名校检错误';
                };
            } else {
                $errMsg = $data['err_code_des'];
            }
        } else {
            $errMsg = $data['return_msg'];
        }

        self::processError($errMsg);
    }

    /**
     * 校检sign值
     */
    static public function checkSign($data)
    {
        if (empty($data['sign'])) {
            self::processError('签名错误');
        } else {
            $sign = $data['sign'];
            unset($data['sign']); // 不包含sign值

            if ($sign == self::makeSign($data)) {
                return true;
            }
        }

        return false;
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
     * 成功的方式相应微信
     */
    public static function responseSuccess()
    {
        return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

    /**
     * 失败的方式相应微信
     * @param  string $msg 错误信息
     */
    public static function responseFail($msg='error')
    {
        $failXml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[%s]]></return_msg></xml>';
        return sprintf($failXml, $msg);
    }

    /**
     * 统一处理错误
     * @param  string $errorMsg 错误信息
     */
    public static function processError($errorMsg)
    {
        throw new think\Exception($errorMsg);
    }
}