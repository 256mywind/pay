<?php

/**
 * 	配置账号信息
 */

class AliPayConfig
{    
    public static function config()
    {
        return [
            //合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
            'partner'           => '11',
            
            'seller_id'         => '111',
            
            //商户的私钥（后缀是.pen）文件相对路径
            //'private_key_path  => __DIR__.'/../AliPay/key/rsa_private_key.pem',
            //商户的私钥,此处填写原始私钥去头去尾，RSA公私钥生成：https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.nBDxfy&treeId=58&articleId=103242&docType=1
            'private_key'  => '-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQDZoYlgJX2iNfVSv/sLjQs4dF+wolcd5IRVxFApCc9nfi6CW5Ze
gP+Vc6ddz/kpYiYRidRQEubVTWNkKc/cwh4I8AkH6OQf2VG0D+T4L//TaSsziyMT
ugoPEisnDOxbXTecwdq5Z6wcaoynGRocOfJh8+2snsOLKfUp2JQ6kK8jewIDAQAB
AoGASbhmiKMqg6AzkexmZetJOb5yC6tyRzX5ffQaE0y3bR9ZMd9EeI7KBR5AO48P
n+0XaCmAOf+tIeQtHujq+KTo9w23IL5Me/34obinzOzSgVDC+8/MhNQe7z0FnO5O
2FZArfiOUNuWVh57WkfYiKGMmASiunI5ce9/Z6Ui4k5Z8oECQQD+yhSCVxGfstOo
SRymSAkO9xh+itXovlxvPlPg7uoW+stCa0RqcPitfcEIv/FE3z5dqXurRWA/MCNP
Qb6+C1dzAkEA2qpCB3iMlRTe6fGWet80IMXnWxj45dMV2q6IN8E3pnHoLXdIOZLC
cvZxa46nYbAKwccblXFjjJCLPCuPRhcx2QJANMyRtXTvnQWE4RHNkxPIdMZ11/tT
WrjgFNl4rls0PXDZYDk0Y05n6iPuNa75A4mztdsiWpq02ENUfd8k9OMKywJBAL8s
8AG8IZ0N3D7JG3ldwCOPI1EAKkw1GvxQb4PfiBobqJRnn1vGtf7w/AS/ehPsZ9s3
iyRDOHfiv/jhVKhBCzECQEDfOui/We9h3MUdMV1pHd4HUB+O+22q8+dpHpVxr6Y4
/qYHA6fqXgQo4O6WwAIu1qB5ZXGmXuoHfS682/6ciPA=
-----END RSA PRIVATE KEY-----
        ',
            
            //支付宝的公钥，查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
            //'ali_public_key_path' => __DIR__.'/../AliPay/key/alipay_public_key.pem',
            'alipay_public_key'   => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB',
            
            'sign_type'         => strtoupper('RSA'),
            
            'input_charset'     => strtolower('utf-8'),
            
            'cacert'            => __DIR__.'/../AliPay/cacert.pem',
            
            'transport'         => 'http',
            
            'service'           => 'mobile.securitypay.pay',
        ];
    }
}