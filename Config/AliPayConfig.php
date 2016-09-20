<?php

/**
 * 	配置账号信息
 */

class AliPayConfig
{
    //这里是你在成功申请支付宝接口后获取到的PID；
    const partner           = '11';
    
    //商户的私钥（后缀是.pen）文件相对路径
    const private_key_path  = __DIR__.'/../AliPay/key/rsa_private_key.pem';
    
    //支付宝公钥（后缀是.pen）文件相对路径
    const ali_public_key_path = __DIR__.'/../AliPay/key/alipay_public_key.pem';
    
    const sign_type         = 'RSA';
    
    const input_charset     = 'utf-8';
    
    const cacert            = __DIR__.'/../AliPay/cacert.pem';
    
    const transport         = 'http';
    
    const seller_id         = '111';
    
}