# ThinkPHP5微信支付扩展库(多文件版)

## 使用
将<code>wxpay</code>文件夹放到<code>extend</code>目录下即可

## 单文件版和多文件版
- 单文件版指不依赖其他任何库, 仅仅把单个文件放到<code>extend</code>目录下即可
- 多文件版指所有文件放到了<code>wxpay</code>目录中, 需要把wxpay文件夹放到extend目录中, 子文件继承自<code>WxBase</code>父类, 但子类仅与<code>WxBase</code>有关系, 如果不需要其他支付, 删除即可

## 注意
- 错误采用抛出异常的方式, 可根据自己的业务在统一接口进行修改

## 用法

#### 支付结果通知处理 WxNotify.php
1. 首先调用 <code>\wxpay\WxNotiy::getNotitfyData()</code>获取微信发送数据
2. 其次调用 <code>\wxpay\WxNotiy::checkNotifyData($data)</code>检测数据是否合法
3. 处理自己业务逻辑
4. 根据以上结果调用 <code>\wxpay\WxNotiy::responseSuccess()</code>或者 <code>\wxpay\WxNotiy::responseFail()</code>响应微信服务器;

### H5支付 WxWapPay.php
调用 <code>\wxpay\WxWapPay::pay($params)</code> 即可生成支付需要数据

#### 微信扫码支付 WxNavtivePay.php
调用 <code>\wxpay\WxNavtivePay::pay($params)</code> 即可生成支付二维码

#### 报关接口 WxCustomDeclare.php
调用 <code>\wxpay\WxCustomDeclare::customDeclare($params)</code> 即可完成申报

#### 查询订单 WxOrderQuery.php
调用 <code>\wxpay\WxOrderQuery::query($order_no)</code> 即可完成订单查询

### 退款申请接口 WxRefund.php
调用 <code>\wxpay\WxRefund::refund($params)</code> 即可完成退款申请

### 退款申请查询 WxRefundQuery.php
调用 <code>\wxpay\WxRefundQuery::query($order_no)</code> 即可完成退款申请查询

### 报关查询 WxCustomDeclareQuery.php
调用 <code>\wxpay\WxCustomDeclareQuery::query($order_no, $customs)</code> 即可完成查询

#### 小程序支付 WxAppPay.php
###### 第一阶段 获取并存储openid
1. 小程序: 调用<code>wx.login()</code>, 获取code登录标识
2. 小程序: 调用<code>wx.request()</code>, 向服务器发送请求
3. 服务端: 调用<code>\WxAppPay::getOpenid($cide)</code> 获取openid
4. 服务端: 存储openid, 并设置过期时间(为了安全起见, 不可永久有效)

###### 第二阶段 进行支付获取预订单信息
5. 小程序: 调用<code>wx.request()</code>, 向服务器发送请求
6. 服务端: 通过用户身份, 找到对应用户的, 从缓存/数据中获取openid
7. 服务端: 调用<code>\wxpay\WxAppPay::prePay($params)</code> 获取预订单信息 prepay_id
8. 服务端: 调用<code>\wxpay\WxAppPay::getPayData($prepay_id)</code> 获取小程序支付参数,

###### 第三阶段 小程序拉起支付
小程序: 调用<code>wx.requestPayment()</code>拉起支付
