<?php

include_once __DIR__ . '/Autoloader.php';

/**
 * 支付类
 * 
 * @author yangdong
 *        
 */
class Pay
{

    function __construct()
    {
        ;
    }

    /**
     *
     * @return \yandong\pay\Pay
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * 1.0 支付宝(sdk)
     * 
     * @return array
     */
    function aliSdkPay($order_no, $total_fee = 0, $subject = '', $body = '', $notity_url = '')
    {
        header('Content-Type:text/html; charset=utf-8');
            
        $partner        = AliPayConfig::config()['partner'];
        $seller_id      = AliPayConfig::config()['seller_id'];
        $out_trade_no   = $order_no;
        $subject        = $subject ? $subject : '购物'; // String(128) 商品的标题/交易标题/订单标题/订单关键字等。
        $body           = $body ? $body : '购物'; // String(512) 对一笔交易的具体描述信息。如果是多种商品,请将商品描述字符串累加传给 body。
        $notify_url     = $notity_url; // 回调通知地址
        
        $service = AliPayConfig::config()['service'];
        $payment_type = '1';
        $_input_charset = AliPayConfig::config()['input_charset'];
        $it_b_pay = '30m';
        $show_url = 'm.alipay.com';
        
        $para_sort = array(
            'partner' => '"' . $partner . '"',
            'seller_id' => '"' . $seller_id . '"',
            'out_trade_no' => '"' . $out_trade_no . '"',
            'subject' => '"' . $subject . '"',
            'body' => '"' . $body . '"',
            'total_fee' => '"' . $total_fee . '"',
            'notify_url' => '"' . $notify_url . '"',
            'service' => '"' . $service . '"',
            'payment_type' => '"' . $payment_type . '"',
            '_input_charset' => '"' . $_input_charset . '"',
            'it_b_pay' => '"' . $it_b_pay . '"',
            'show_url' => '"' . $show_url . '"'
        );
        $prestr = Core::instance()->createLinkstring($para_sort);
        $Sgin = false;
        $sign_type = AliPayConfig::config()['sign_type'];
        if ($sign_type == 'RSA') {
            $sign = Rsa::instance()->rsaSign($prestr, AliPayConfig::config()['private_key']);
            return $prestr . '&sign="' . urlencode($sign) . '"' . '&sign_type="RSA"';
        }else {
            return $Sgin;
        }
    }

    /**
     * 1.1 支付宝（APP）
     * 
     * @param string $order_no            
     * @param string $payprice            
     */
    function aliAppPay($order_no, $payprice, $subject = '', $body = '', $notity_url = '')
    {
        //支付宝支付的服务器端
        $partner = AliPayConfig::config()['partner'];
        $seller  = AliPayConfig::config()['seller_id'];
        $subject    = $subject ? $subject : '华浩联创'; // String(128) 商品的标题/交易标题/订单标题/订单关键字等。
        $body       = $body ? $body : '购物'; // String(512) 对一笔交易的具体描述信息。如果是多种商品,请将商品描述字符串累加传给 body。
        $notifyurl  = $notity_url; // 回调通知地址
        
        // 坑，注意，需要转换密钥！！！ openssl pkcs8 -topk8 -inform PEM -in your.key -outform PEM -nocrypt > your_nocrypt.key
        // $privateKey=file_get_contents("/ramdisk/your_nocrypt.key");//这里为了方便直接写入到php文件里了。
        $dataString = sprintf('partner="%s"&seller_id="%s"&out_trade_no="%s"&subject="%s"&body="%s"&total_fee="%.2f"&notify_url="%s"&service="mobile.securitypay.pay"&payment_type="1"&_input_charset="utf-8"&it_b_pay="30m"&show_url="m.alipay.com"', $partner, $seller, $order_no, $subject, $body, $payprice, $notifyurl);
        // 获取签名        
        $sign = Rsa::instance()->rsaSign($dataString, AliPayConfig::config()['private_key']);
        
        $dataString .= '&sign_type="RSA"&bizcontext="{"appkey":"2014052600006128"}"&sign="' . $sign . '"';
        // 生成可以直接打开的链接，让iOS客户端打开：[[UIApplication sharedApplication] openURL:[NSURL URLWithString:$iOSLink]];
        $iOSLink = "alipay://alipayclient/?" . urlencode(json_encode(array(
            'requestType' => 'SafePay',
            "fromAppUrlScheme" => /*iOS App的url schema，支付宝回调用*/"LoveLife",
            "dataString" => $dataString
        )));
        return $iOSLink;
    }

    /**
     * 1.2 支付宝 回调通知
     * TRADE_FINISHED 交易成功 true(触发通知)
     * TRADE_SUCCESS 支付成功 true(触发通知)
     * WAIT_BUYER_PAY 交易创建 true(触发通知)
     * TRADE_CLOSED 交易关闭 false(不触发通知)
     */
    function notifyurl()
    {
        //计算得出通知验证结果
        $alipay_config = AliPayConfig::config();
        $alipayNotify  = new AlipayNotify($alipay_config);
        if($alipayNotify->getResponse($_POST['notify_id']))//判断成功之后使用getResponse方法判断是否是支付宝发来的异步通知。
        {
            if($alipayNotify->getSignVeryfy($_POST, $_POST['sign'])) {//使用支付宝公钥验签
                //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
                //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
                //商户订单号
                $out_trade_no = $_POST['out_trade_no'];
                //支付宝交易号
                $trade_no = $_POST['trade_no'];
                //交易状态
                $trade_status = $_POST['trade_status'];
                if($_POST['trade_status'] == 'TRADE_FINISHED') {
                    //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //如果有做过处理，不执行商户的业务程序
                    //注意：
                    //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
                    //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                }
                else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                    //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //如果有做过处理，不执行商户的业务程序
                    //注意：
                    //付款完成后，支付宝系统发送该交易状态通知
                    //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                }
                //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
                echo "success";		//请不要修改或删除
            }
            else //验证签名失败
            {
                echo "sign fail";
            }
        }
        else //验证是否来自支付宝的通知失败
        {
            echo "response fail";
        }
    }

    /**
     * 2.1 微信支付(sdk)
     * @param unknown $order_no 
     * @param unknown $payprice
     * @param string $subject
     * @param string $body
     * @param string $notity_url
     * @return number[]|string[]|unknown[]|mixed[]
     */
    function wxSdkPay($order_no, $payprice, $subject = '', $body = '', $notity_url = '')
    {
        // 设置必填参数
        $amount         = $payprice * 100; // 金额
        $out_trade_no   = $order_no; // 订单号
        $subject        = $subject ? $subject : '购物'; // String(128) 商品的标题/交易标题/订单标题/订单关键字等。
        $body           = $body ? $body : '购物'; // String(512) 对一笔交易的具体描述信息。如果是多种商品,请将商品描述字符串累加传给 body。
        $notifyurl      = $notity_url; // 回调通知地址
        //1.统一下单
        $input = new \WxPayUnifiedOrder();
        // appid已填,商户无需重复填写
        $input->SetAppid(WxPayConfig::APPID);
        // mch_id已填,商户无需重复填写
        $input->SetMch_id(WxPayConfig::MCHID);
        // noncestr已填,商户无需重复填写
        // spbill_create_ip已填,商户无需重复填写
        // sign已填,商户无需重复填写
        $input->SetBody($body);
        $input->SetOut_trade_no($order_no);
        $input->SetTotal_fee($amount);
        $input->SetGoods_tag($subject);
        $input->SetNotify_url($notity_url);
        $input->SetTrade_type("APP");
        // 非必填参数，商户可根据实际情况选填
        // $unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
        // $unifiedOrder->setParameter("device_info","XXXX");//设备号
        // $unifiedOrder->setParameter("attach","XXXX");//附加数据
        // $unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
        // $unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
        // $unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
        // $unifiedOrder->setParameter("openid","XXXX");//用户标识
        // $unifiedOrder->setParameter("product_id","1101");//商品ID
        
        $order = \WxPayApi::unifiedOrder($input);
        // 商户根据实际情况设置相应的处理流程
        $data = array();
        if ($order["return_code"] == "FAIL") {
            // 商户自行增加处理流程
            // echo "通信出错：".$unifiedOrderResult['return_msg']."<br>";
            $data['success'] = 0;
            $data['out_trade_no'] = $out_trade_no;
            $data['err_code_des'] = $order['return_msg'];
            
        } elseif ($order["result_code"] == "FAIL") {
            // 商户自行增加处理流程
            // echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
            // echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
            $data['success'] = 0;
            $data['out_trade_no'] = $out_trade_no;
            $data['err_code']     = $order['err_code'];
            $data['err_code_des'] = $order['err_code_des'];
            
        } elseif ($order["result_code"] == "SUCCESS") {
            // 商户自行增加处理流程
            /**
             * @var Ambiguous $data
             * {
                    "return_code": "SUCCESS",
                    "return_msg": "OK",
                    "appid": "wxc359e9d3ead7cf5f",
                    "mch_id": "1385854502",
                    "nonce_str": "JH9awYnmRn2H0E9D",
                    "sign": "3AE87EE5D9F95E7D7C0CA35F77829026",
                    "result_code": "SUCCESS",
                    "prepay_id": "wx20160920141755e2c56ec1080378465993",
                    "trade_type": "APP"
                }
             */
            $data = $order;
            $data['timestamp'] = time();            
            $data['total_fee'] = $payprice;
            $data['out_trade_no'] = $out_trade_no;
            $data['success'] = 1;
        }
        return $data;
    }

    /**
     * 2.1 微信支付(调APP)
     * @return multitype:
     */
    function wxAppPay($order_no, $payprice, $subject = '', $body = '', $notity_url = '')
    {
        $data = $this->wxSdkPay($order_no, $payprice, $subject, $body, $notity_url);
        if ($data['success']) {
            //weixin://app/wxc359e9d3ead7cf5f/pay/?nonceStr=YzJdqUVvigqEtJbQ&package=Sign%3DWXPay&partnerId=1385854502&prepayId=wx201609201433415383ab0cb10774129733&timeStamp=1474353221&sign=1F2CA4B2EE2FB63286D64666037393AD&signType=SHA1
            $iOSLink = sprintf("weixin://app/%s/pay/?nonceStr=%s&package=Sign%%3DWXPay&partnerId=%s&prepayId=%s&timeStamp=%s&sign=%s&signType=SHA1", $data['appid'], $data['nonce_str'], $data['mch_id'], $data['prepay_id'], time(), $data['sign']);
            return $iOSLink;
        }else {
            return $data;
        }
    }

    /**
     * 2.2 JSAPI H5
     * 
     * @param unknown $order_no            
     * @param unknown $payprice            
     * @param string $subject            
     * @param string $body            
     * @param string $notity_url            
     */
    public function jsApiPay($order_no, $payprice, $subject = '', $body = '', $notity_url = '', $openid)
    {
        // ①、获取用户openid
        $tools = new JsApiPay();
        //$openId = $tools->GetOpenid();
        // ②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($body);
        $input->SetAttach($subject);
        $input->SetOut_trade_no($order_no);
        $input->SetTotal_fee($payprice * 100);
        //$input->SetTime_start(date("YmdHis"));
        //$input->SetTime_expire(date("YmdHis", time() + 1800));
        $input->SetGoods_tag($subject);
        $input->SetNotify_url($notity_url);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
        
        $order = \WxPayApi::unifiedOrder($input);

        $data = array();
        if ($order["return_code"] == "FAIL") {
            // 商户自行增加处理流程
            $data['success'] = 0;
            $data['out_trade_no'] = $order_no;
            $data['err_code_des'] = $order['return_msg'];
            
        } elseif ($order["result_code"] == "FAIL") {
            // 商户自行增加处理流程
            $data['success'] = 0;
            $data['out_trade_no']   = $order_no;
            $data['err_code']       = $order['err_code'];
            $data['err_code_des']   = $order['err_code_des'];
            
        } elseif ($order["result_code"] == "SUCCESS") {
            // 成功返回
            /**
             * @var Ambiguous $jsApiParameters
             * [
             *       'appId' => 'wxc199b9409ce8723b',
             *       'nonceStr' => '5oe88v87yt62uwcu2fhdk0u4z4xuj8i9',
             *       'package' => 'prepay_id=wx2016092013530685cda707b20266675309',
             *       'signType' => 'MD5',
             *       'timeStamp' => '1474350786',
             *       'paySign' => '08CEC0766210C33450904D5F06C5EF50',
             *   ]
             */
            $jsApiParameters = $tools->GetJsApiParameters($order);
            $data = json_decode($jsApiParameters, true);
        }
        return $data;
    }

    /**
     * 企业付款
     * @param unknown $order_no
     * @param unknown $payprice
     * @param string $subject
     * @param string $body
     * @param string $notity_url
     * @param unknown $openid
     */
    public function transfers($order_no, $payprice, $body = '', $notity_url = '', $openid){
         /**
	     * API 参数
	     * @var array
	     * 'mch_appid'         # 公众号APPID
	     * 'mchid'             # 商户号
	     * 'device_info'       # 设备号
	     * 'partner_trade_no'  # 商户订单号
	     * 'openid'            # 收款用户openid
	     * 'check_name'        # 校验用户姓名选项 针对实名认证的用户
	     * 're_user_name'      # 收款用户姓名
	     * 'amount'            # 付款金额
	     * 'desc'              # 企业付款描述信息
	     */
        $input = new WxPayTransfers();
        $input->SetMch_appid(JsPayConfig::APPID);
        $input->SetMchid(JsPayConfig::MCHID);
        $input->SetDevice_info('');
        $input->SetPartner_trade_no($order_no);
        $input->SetOpenid($openid);
        $input->SetCheck_name('NO_CHECK');
        //$input->SetRe_user_name('');
        $input->SetAmount($payprice*100);
        $input->SetDesc($body);
        
        $order = WxPayApi::transfers($input);
        
        $data = array();
        if ($order["return_code"] == "FAIL") {
            // 商户自行增加处理流程
            // echo "通信出错：".$unifiedOrderResult['return_msg']."<br>";
            $data['success'] = 0;
            $data['out_trade_no'] = $order_no;
            $data['err_code_des'] = $order['return_msg'];
            
        } elseif ($order["result_code"] == "FAIL") {
            // 商户自行增加处理流程
            // echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
            // echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
            $data['success'] = 0;
            $data['out_trade_no'] = $order_no;
            $data['err_code']     = $order['err_code'];
            $data['err_code_des'] = $order['err_code_des'];
            
        } elseif ($order["result_code"] == "SUCCESS") {
            // 商户自行增加处理流程
            $data = $order;
            $data['timestamp'] = time();            
            $data['total_fee'] = $payprice;
            $data['out_trade_no'] = $order_no;
            $data['success'] = 1;
        }
        return $data;
    }
    /**
     * 微信支付回调通知
     */
    function wxnotifyurl()
    {
        $notify = new PayNotifyCallBack();
        $notify->Handle();
        //在PayNotifyCallBack->NotifyProcess() 中处理回调后相关的订单状态
    }

    /**
     * 3.0 银联支付
     * @param unknown $order_no
     * @param unknown $payprice
     * @param string $subject
     * @param string $body
     * @param string $notity_url
     * @return boolean|unknown[]
     */
    function uniopay($order_no, $payprice, $subject = '', $body = '', $notity_url = '')
    {
        header('Content-type:text/html;charset=utf-8');
        require_once __DIR__.'/UionPay/common.php';
        require_once __DIR__.'/UionPay/httpClient.php';
        require_once __DIR__.'/UionPay/secureUtil.php';
        
        $payprice = 0.01 * 100;
        // 初始化日志
        $params = array(
            'version' => '5.0.0', // 版本号
            'encoding' => 'utf-8', // 编码方式
            'certId' => getSignCertId(), // 证书ID
            'txnType' => '01', // 交易类型
            'txnSubType' => '01', // 交易子类
            'bizType' => '000201', // 业务类型
            'frontUrl' => $notity_url, // 前台通知地址，控件接入的时候不会起作用
            'backUrl' => $notity_url, // 后台通知地址
            'signMethod' => '01', // 签名方法
            'channelType' => '08', // 渠道类型，07-PC，08-手机
            'accessType' => '0', // 接入类型
            'merId' => '898510154111001', // 商户代码，请改自己的测试商户号
            'orderId' => $order_no, // 商户订单号，8-40位数字字母
            'txnTime' => date('YmdHis'), // 订单发送时间
            'txnAmt' => $payprice, // 交易金额，单位分
            'currencyCode' => '156', // 交易币种
            'orderDesc' => $subject, // 订单描述，可不上送，上送时控件中会显示该信息
            'reqReserved' => '透传信息'
        ) // 请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
;
        // 签名
        $signature = sign($params);
        if (! $signature)
            return false;
        $params['signature'] = $signature;
        // 发送信息到后台
        $result = sendHttpRequest($params, UnPayConfig::SDK_App_Request_Url);
        if ($result) {
            // 返回结果展示
            $result_arr = coverStringToArray($result);
            // echo verify ( $result_arr ) ? '验签成功' : '验签失败';
            if (verify($result_arr)) {
                return ['tn'=>$result_arr['tn']];
            } 
        }
        return false;
    }

    /**
     * 3.1 银联支付回调通知
     */
    function upaynotify()
    {
        require_once COMMON_PATH . 'Pay/AppUpay/secureUtil.php';
        $data = $_REQUEST;
        if (isset($data['signature'])) {
            if (verify($data)) {
                $out_trade_no = $data['orderId']; // 其他字段也可用类似方式获取
                //TODO 回调成功自定义操作
            } else {
                echo '验签失败';
            }
            echo 'success';
        } else {
            echo '签名为空';
        }
    }
}