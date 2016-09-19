<?php

namespace Common\Pay;

/**
 * 支付类
 * @author yangdong
 *
 */

class Pay {
    function __construct() {
        ;
    }
    /**
     * 
     * @return \Common\Pay\Pay
     */
    public static function instance()
    {
        return new static;
    }
    
    /**
     * 1.0 支付宝(sdk)
     * @return array
     */
    function alipay($order_no, $total_fee=0, $subject='', $body='', $notity_url=''){
        header('Content-Type:text/html; charset=utf-8');
        require_once COMMON_PATH.'Pay/AppAli/alipay_rsa.function.php';
        require_once COMMON_PATH.'Pay/AppAli/alipay_core.function.php';
        $alipay_config = C('mobilepay_config');
        
        $partner        = $alipay_config['partner'];
        $seller_id      = $alipay_config['seller_id'];
        $out_trade_no   = $order_no;
        $subject        = $subject ? $subject : '购物';//String(128) 商品的标题/交易标题/订单标题/订单关键字等。
        $body           = $body ? $body : '购物';//String(512) 对一笔交易的具体描述信息。如果是多种商品,请将商品描述字符串累加传给 body。
        $total_fee      = $total_fee ? $total_fee : M('order')->where(array('id'=>$order_no))->getField('totalprice');//订单金额
        $notify_url     = $notity_url ? $notity_url : $alipay_config['notify_url'];//回调通知地址
        $service        = 'mobile.securitypay.pay';
        $payment_type   = '1';
        $_input_charset = $alipay_config['input_charset'];
        $it_b_pay       = '30m';
        $show_url       = 'm.alipay.com';
        
        $para_sort = array(
            'partner'       => '"'.$partner.'"',
            'seller_id'     => '"'.$seller_id.'"',
            'out_trade_no'  => '"'.$out_trade_no.'"',
            'subject'       => '"'.$subject.'"',
            'body'          => '"'.$body.'"',
            'total_fee'     => '"'.$total_fee.'"',
            'notify_url'    => '"'.$notify_url.'"',
            'service'       => '"'.$service.'"',
            'payment_type'  => '"'.$payment_type.'"',
            '_input_charset'=> '"'.$_input_charset.'"',
            'it_b_pay'      => '"'.$it_b_pay.'"',
            'show_url'      => '"'.$show_url.'"',
        );
        
        $prestr = createLinkstring($para_sort);

        $isSgin = false;
        switch (strtoupper(trim($alipay_config['sign_type']))) {
            case "RSA" :
                $isSgin = rsaSign($prestr, trim($alipay_config['private_key_path']));
                break;
            default :
                $isSgin = false;
        }
        if ($isSgin){
            $string = $prestr.'&sign="'.urlencode($isSgin).'"'.'&sign_type="RSA"';
            return showData(array('data'=>$string, 'status'=>1), '加密成功');
        }else {
            return showData(new \stdClass(), '加密失败', 1);
        }
    }
    /**
     * 1.1 支付宝（APP）
     * @param string $order_no
     * @param string $payprice
     */
    function appAlipay($order_no, $payprice, $subject='', $body='', $notity_url=''){
        $alipay_config = C('mobilepay_config');
        //支付宝支付的服务器端
        $partner = $alipay_config['partner'];
        $seller = $alipay_config['seller_id'];
        //坑，注意，需要转换密钥！！！ openssl pkcs8 -topk8 -inform PEM -in your.key -outform PEM -nocrypt > your_nocrypt.key
        // $privateKey=file_get_contents("/ramdisk/your_nocrypt.key");//这里为了方便直接写入到php文件里了。
        $privateKey=<<<EOF
-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBANpqe7rC/qHB4lKg
Kwitb53YkwJgIf3YhwOxD3vuvEkAu6ekcLJmqg9Il4dxXFMfAKRh38ExfsX7J3c4
dB1jNLXyoI2kKtd0wFH99aQm5911TeDmIbbHMMF+GiEVWkOZFN7KiTUZcvAVE4nY
hHsmmC0AQviWQMc4Lai67Jb3z2XxAgMBAAECgYAeDez8o/xZ0c4MxJFnXkYvmC+S
chv7TCI39dNFoHI0MW+g/9WqFspr0/dV4dlsbqWt+PHLKb5iC89Abno72PzVk3JO
ptyd6DZyq5sa/58ZwGJWbFSl4zaGisV28bactT6STTHiJczCzSSN7JP/amTERUA6
jIXrzwIRJCxNyWsqgQJBAPSXv/qZBb/nAYrLp7dni3Lgw4WqSzs8wPdlWtQybdea
PI+3EftrA+ByN352TLLCUGcX+1uhnncEOf1sPP+U9tkCQQDkmjPEnYzB9DNhHaYP
Q6UzMZdeOMpsFycnRED4J7WgAIXZRouXycsbuV04clVVHWE5mdIAaeT+xEEzGU4r
EmjZAkEAgODEw2KF6QvrgBq3EKh6jdlorLGCWoA0nSbGqTC5N/WJG6C21Ocab9U+
8F+dIkPI4cl9JFcQjF2pwKKbsX/oYQJAMV8EUzBbl//voMfQd3d6lEXflR/ax+Fw
OVDKX03kMfwq7DQKLewNC53K/kfjGhDQUKph6mj0Zflow2pxsWe2cQJBALMOVDQM
hJ/5MRFqVevkvfQ5CgiFSv7G+5GWhpkZ91jyu6X2aKsOSGE7X+KeRglPsb4eHmF5
yn9INyM6IS4gec4=
-----END PRIVATE KEY-----
EOF;
        //组装订单信息。可以让客户端传进来orderId等信息，这里连接数据库，查询价格，商品名等信息。ps：价格一定不要让客户端传进来,免得被骗:)
         
        $subject        = $subject ? $subject : '华浩联创';//String(128) 商品的标题/交易标题/订单标题/订单关键字等。
        $body           = $body ? $body :'购物';//String(512) 对一笔交易的具体描述信息。如果是多种商品,请将商品描述字符串累加传给 body。
        $notifyurl     = $notity_url ? $notity_url :$alipay_config['notify_url'];//回调通知地址
    
        $dataString=sprintf('partner="%s"&seller_id="%s"&out_trade_no="%s"&subject="%s"&body="%s"&total_fee="%.2f"&notify_url="%s"&service="mobile.securitypay.pay"&payment_type="1"&_input_charset="utf-8"&it_b_pay="30m"&show_url="m.alipay.com"',$partner,$seller,$order_no,$subject,$body,$payprice,$notifyurl);
        //获取签名
        $res = openssl_get_privatekey($privateKey);
        openssl_sign($dataString, $sign, $res);
        openssl_free_key($res);
        $sign = urlencode(base64_encode($sign));
        $dataString.='&sign_type="RSA"&bizcontext="{"appkey":"2014052600006128"}"&sign="'.$sign.'"';
        //生成可以直接打开的链接，让iOS客户端打开：[[UIApplication sharedApplication] openURL:[NSURL URLWithString:$iOSLink]];
        $iOSLink= "alipay://alipayclient/?".urlencode(json_encode(array('requestType' => 'SafePay', "fromAppUrlScheme" => /*iOS App的url schema，支付宝回调用*/"LoveLife","dataString"=>$dataString)));
        return showData(array('data'=>$iOSLink));
    }
    /**
     * 1.2 支付宝 回调通知
     * TRADE_FINISHED 交易成功 true(触发通知)
     * TRADE_SUCCESS 支付成功 true(触发通知)
     * WAIT_BUYER_PAY 交易创建 true(触发通知)
     * TRADE_CLOSED 交易关闭 false(不触发通知)
     * 0-用户 1-师傅
     */
    function notifyurl($type=0){
        $alipay_config = C('mobilepay_config');
        $alipayNotify  = new \Common\Pay\AppAli\AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if($verify_result) {//验证成功
            $out_trade_no  = $_POST['out_trade_no'];   //商户订单号
            $trade_no      = $_POST['trade_no'];       //支付宝交易号
            $trade_status  = $_POST['trade_status'];   //交易状态
            if($_POST['trade_status'] == 'TRADE_FINISHED') {
    
            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //更新状态
                $this->update($out_trade_no, $type);
            }
            echo "success";		//请不要修改或删除
        }else {
            echo "fail";//验证失败
        }
    }
    /**
     * 2.1 微信支付(sdk)
     * @param string $order_no 订单号
     * @return array
     */
    function wxpay($order_no, $payprice, $subject='', $body='', $notity_url=''){
        require_once COMMON_PATH.'/Pay/AppWx/WxPayPubHelper.php';
        //使用统一支付接口
        $unifiedOrder = new \UnifiedOrder_pub();
        $Common_util_pub = new \Common_util_pub();
        //设置统一支付接口参数
        //设置必填参数
        $amount         = $payprice*100;   //金额
        $out_trade_no   = $order_no;    //订单号
        $subject        = $subject ? $subject : '购物';//String(128) 商品的标题/交易标题/订单标题/订单关键字等。
        $body           = $body ? $body :'购物';//String(512) 对一笔交易的具体描述信息。如果是多种商品,请将商品描述字符串累加传给 body。
        $notifyurl      = $notity_url ? $notity_url : C('wxpay.U_NOTIFY_URL');//回调通知地址
        //appid已填,商户无需重复填写
        //mch_id已填,商户无需重复填写
        //noncestr已填,商户无需重复填写
        //spbill_create_ip已填,商户无需重复填写
        //sign已填,商户无需重复填写
        $unifiedOrder->setParameter("body","$body");//商品描述
        //自定义订单号，此处仅作举例
        $timeStamp = time();
        $unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号
        $unifiedOrder->setParameter("total_fee","$amount");//总金额
        $unifiedOrder->setParameter("notify_url","$notifyurl");//通知地址
        $unifiedOrder->setParameter("trade_type","APP");//交易类型
        //非必填参数，商户可根据实际情况选填
        //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
        //$unifiedOrder->setParameter("device_info","XXXX");//设备号
        //$unifiedOrder->setParameter("attach","XXXX");//附加数据
        //$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
        //$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
        //$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
        //$unifiedOrder->setParameter("openid","XXXX");//用户标识
        //$unifiedOrder->setParameter("product_id","1101");//商品ID
        
        //获取统一支付接口结果
        $unifiedOrderResult = $unifiedOrder->getResult();

        //echo json_encode($unifiedOrderResult);
        //商户根据实际情况设置相应的处理流程
        if ($unifiedOrderResult["return_code"] == "FAIL")
        {
            //商户自行增加处理流程
            //echo "通信出错：".$unifiedOrderResult['return_msg']."<br>";
            $data = array();
            $data['success'] = 0;
            $data['out_trade_no'] = $out_trade_no;
            $data['err_code_des']   = $unifiedOrderResult['return_msg'];
            return showData($data);
        }
        elseif($unifiedOrderResult["result_code"] == "FAIL")
        {
            //商户自行增加处理流程
            //echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
            //echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
            $data = array();
            $data['success']        = 0;
            $data['out_trade_no']   = $out_trade_no;
            $data['err_code']       = $unifiedOrderResult['err_code'];
            $data['err_code_des']   = $unifiedOrderResult['err_code_des'];
            return showData($data);
        }
        elseif($unifiedOrderResult["result_code"] == "SUCCESS")
        {
            //从统一支付接口获取到code_url
            //$code_url = $unifiedOrderResult["code_url"];
            //商户自行增加处理流程
            //......
            //$this->assign('code_url',$code_url);
            //echo json_encode($unifiedOrderResult);
            $data = array();
            $data['appid']          = $unifiedOrderResult["appid"];
            $data['noncestr']       = $unifiedOrderResult["nonce_str"];
            $data['package']        = "Sign=WXPay";
            $data['partnerid']      = C('wxpay.MCHID');
            $data['prepayid']       = $unifiedOrderResult["prepay_id"];
            $data['timestamp']      = $timeStamp;
            $data['sign']           = $Common_util_pub->getSign($data);
    
            $data['total_fee']      = $payprice;
            $data['out_trade_no']   = $out_trade_no;
            $data['success']        = 1;
            return showData($data);
        }
    }
    /**
     * 2.1 微信支付(调APP)
     * @return multitype:
     */
    function weixin($order_no, $payprice, $subject='', $body='', $notity_url=''){
        require_once COMMON_PATH.'/Pay/AppWx/WxPayPubHelper.php';
        //STEP 0. 签名
        //更改商户把相关参数后可测试
        $APP_ID     = \WxPayConf_pub::APPID;
        $APP_SECRET = \WxPayConf_pub::APPSECRET;
        //商户号，填写商户对应参数
        $MCH_ID     = \WxPayConf_pub::MCHID;
        //商户API密钥，填写相应参数
        $PARTNER_ID = \WxPayConf_pub::KEY;
        //支付结果回调页面
        $NOTIFYURL = $notity_url ? $notity_url : \WxPayConf_pub::NOTIFY_URL;//回调通知地址
    
        $amount         = $payprice*100;   //金额
        $out_trade_no   = $order_no;    //订单号
        $subject        = $subject ? $subject : '华浩联创';//String(128) 商品的标题/交易标题/订单标题/订单关键字等。
        $body           = $body ? $body :'购物';//String(512) 对一笔交易的具体描述信息。如果是多种商品,请将商品描述字符串累加传给 body。
    
        //STEP 1. 构造一个订单。
        $order=array(
            "body"          => $body,
            "appid"         => $APP_ID,
            "device_info"   => "APP-001",
            "mch_id"        => $MCH_ID,
            "nonce_str"     => mt_rand(),
            "notify_url"    => $NOTIFYURL,
            "out_trade_no"  => $out_trade_no,
            "spbill_create_ip" => "196.168.1.1",
            "total_fee"     => $amount,//坑！！！这里的最小单位时分，跟支付宝不一样。1就是1分钱。只能是整形。
            "trade_type"    => "APP"
        );
        ksort($order);
        //STEP 2. 签名
        $sign="";
        foreach ($order as $key => $value) {
            if($value&&$key!="sign"&&$key!="key"){
                $sign.=$key."=".$value."&";
            }
        }
        $sign.="key=".$PARTNER_ID;
        $sign=strtoupper(md5($sign));
    
        //STEP 3. 请求服务器
        $xml="<xml>\n";
        foreach ($order as $key => $value) {
            $xml.="<".$key.">".$value."</".$key.">\n";
        }
        $xml.="<sign>".$sign."</sign>\n";
        $xml.="</xml>";
        $opts = array(
            'http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: text/xml',
                'content' => $xml
            ),
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            )
        );
        $context  = stream_context_create($opts);
        $result = file_get_contents('https://api.mch.weixin.qq.com/pay/unifiedorder', false, $context);
        $result = simplexml_load_string($result,null, LIBXML_NOCDATA);
    
        //使用$result->nonce_str和$result->prepay_id。再次签名返回app可以直接打开的链接。
        $input=array(
            "noncestr"  => "".$result->nonce_str,
            "prepayid"  => "".$result->prepay_id,//上一步请求微信服务器得到nonce_str和prepay_id参数。
            "appid"     => $APP_ID,
            "package"   => "Sign=WXPay",
            "partnerid" => $MCH_ID,
            "timestamp" => time(),
        );
        ksort($input);
        $sign="";
        foreach ($input as $key => $value) {
            if($value&&$key!="sign"&&$key!="key"){
                $sign.=$key."=".$value."&";
            }
        }
        $sign.="key=".$PARTNER_ID;
        $sign=strtoupper(md5($sign));
        $iOSLink=sprintf("weixin://app/%s/pay/?nonceStr=%s&package=Sign%%3DWXPay&partnerId=%s&prepayId=%s&timeStamp=%s&sign=%s&signType=SHA1",$APP_ID,$input["noncestr"],$MCH_ID,$input["prepayid"],$input["timestamp"],$sign);
    
        return showData(array('data'=>$iOSLink));
    }
    /**
     * 2.2 JSAPI H5
     * @param unknown $order_no
     * @param unknown $payprice
     * @param string $subject
     * @param string $body
     * @param string $notity_url
     */
    public function jsapiPay($order_no, $payprice, $subject='', $body='', $notity_url='', $openid){
    
        require_once COMMON_PATH."/Pay/JsApi/lib/WxPay.Api.php";
        require_once COMMON_PATH."/Pay/JsApi/WxPay.JsApiPay.php";
    
        //①、获取用户openid
        $tools = new \JsApiPay();
        //$openId = $tools->GetOpenid();
    
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($body);
        $input->SetAttach($subject);
        $input->SetOut_trade_no($order_no);
        $input->SetTotal_fee($payprice*100);
        /*
         $input->SetTime_start(date("YmdHis"));
         $input->SetTime_expire(date("YmdHis", time() + 1800));
         */
        $input->SetGoods_tag($subject);
        $input->SetNotify_url($notity_url);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
    
        $order = \WxPayApi::unifiedOrder($input);
    
        if ($order["return_code"] == "FAIL") {
            $data = array();
            $data['out_trade_no'] = $order_no;
            $data['err_code_des']   = $order['return_msg'];
        } elseif($order["result_code"] == "FAIL") {
            //商户自行增加处理流程
            $data = array();
            $data['out_trade_no']   = $order_no;
            $data['err_code']       = $order['err_code'];
            $data['err_code_des']   = $order['err_code_des'];
        }
        elseif($order["result_code"] == "SUCCESS") {
            $jsApiParameters = $tools->GetJsApiParameters($order);
            $data = json_decode($jsApiParameters, true);
        }
        return showData($data);
    }
    /**
     * 微信支付回调通知
     */
    function wxnotifyurl(){
        require_once COMMON_PATH.'/Pay/AppWx/WxPayPubHelper.php';
        //使用通用通知接口
        $notify = new \Notify_pub();
        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);
        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if($notify->checkSign() == FALSE){
            $notify->setReturnParameter("return_code","FAIL");//返回状态码
            $notify->setReturnParameter("return_msg","签名失败");//返回信息
        }else{
            $notify->setReturnParameter("return_code","SUCCESS");//设置返回码
        }
        $returnXml = $notify->returnXml();
        echo $returnXml;
    
        //==商户根据实际情况设置相应的处理流程，此处仅作举例=======
        //以log文件形式记录回调信息
        //logResult("【接收到的notify通知】:\n".$xml."\n");
        if($notify->checkSign() == TRUE)
        {
            if ($notify->data["return_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                //logResult("【通信出错】:\n".$xml."\n");
            }
            elseif($notify->data["result_code"] == "FAIL"){
                //此处应该更新一下订单状态，商户自行增删操作
                //logResult("【业务出错】:\n".$xml."\n");
            }
            else{
                //此处应该更新一下订单状态
                $out_trade_no = $notify->data["out_trade_no"];
                
                if ($type <= 1) {
                    $this->update($out_trade_no, $type);
                }else {
                    $this->spending($out_trade_no, $type);
                }
            }
        }
    }

    /**
     * 3.0 银联支付
     * @param unknown $order_no
     */
    function uniopay($order_no){
        header ( 'Content-type:text/html;charset=utf-8' );
        require_once COMMON_PATH . 'Pay/AppUpay/common.php';
        require_once COMMON_PATH . 'Pay/AppUpay/httpClient.php';
        require_once COMMON_PATH . 'Pay/AppUpay/secureUtil.php';
        
        $order_price = M('order')->where(array('order_sn'=>$order_no))->getField('totalPrice');//订单金额
        $txnAmt = $order_price*100;
        // 初始化日志
        $params = array(
        		'version'     => '5.0.0',				//版本号
        		'encoding'    => 'utf-8',				//编码方式
        		'certId'      => getSignCertId (),		//证书ID
        		'txnType'     => '01',				    //交易类型	
        		'txnSubType'  => '01',				    //交易子类
        		'bizType'     => '000201',				//业务类型
        		'frontUrl'    => C('upay.SDK_FRONT_NOTIFY_URL'), //前台通知地址，控件接入的时候不会起作用
        		'backUrl'     => C('upay.SDK_BACK_NOTIFY_URL'),   //后台通知地址	
        		'signMethod'  => '01',		             //签名方法
        		'channelType' => '08',		//渠道类型，07-PC，08-手机
        		'accessType'  => '0',		//接入类型
        		'merId'       => '898510154111001',	//商户代码，请改自己的测试商户号
        		'orderId'     => $order_no,	//商户订单号，8-40位数字字母
        		'txnTime'     => date('YmdHis'),	//订单发送时间
        		'txnAmt'      => $txnAmt, //交易金额，单位分
        		'currencyCode'=> '156',	//交易币种
        		'orderDesc'   => '玖泽买单',  //订单描述，可不上送，上送时控件中会显示该信息
        		'reqReserved' => '透传信息', //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
        );
        // 签名
        $signature = sign ( $params );
        if (!$signature) return showData(new \stdClass(), '签名失败', 1);
        $params['signature'] = $signature;
        // 发送信息到后台
        $result = sendHttpRequest ( $params, C('upay.SDK_App_Request_Url') );
        if ($result){
            //返回结果展示
            $result_arr = coverStringToArray ( $result );
            //echo verify ( $result_arr ) ? '验签成功' : '验签失败';
            if (verify ( $result_arr )){
                return showData(array('tn'=>$result_arr['tn'],'result'=>$result_arr), '验签成功');
            }else {
                return showData(new \stdClass(), '验签失败', 1);
            }
        }else {
            return showData(new \stdClass(), '验签失败', 1);
        }
    }
    /**
     * 3.1 银联支付回调通知
     */
    function upaynotify(){
        require_once COMMON_PATH . 'Pay/AppUpay/secureUtil.php';
        $data = $_REQUEST;
        if (isset ( $data ['signature'] )) {            
            if (verify ( $data )){
                $out_trade_no = $data ['orderId']; //其他字段也可用类似方式获取
                M('order')->where(array('order_sn'=>$out_trade_no))->setField(array('type'=>1, 'pay'=>3));
            }else {
                echo '验签失败';
            }
            echo 'success';
        } else {
            echo '签名为空';
        }
    }
}