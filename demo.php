<?php

require 'Pay.php';

$pay = new Pay();





try {
    
    $data = $pay->aliAppPay('w34fa12313sf111aa1', 0.01, '测试', '测试', 'http://www.baidu.com');
    echo "<pre>";
    print_r($data);
    die;
    
    $data = $pay->wxSdkPay('12312312312311221', 0.01, '测试', '测试', 'http://www.baidu.com');
    echo "<pre>";
    echo json_encode($data);
    die;
    
    $data = $pay->jsApiPay('123123123123123111', 0.01, '测试', '测试', 'http://www.baidu.com', 'oo9riw-');   
    echo "<pre>";
    print_r($data);
    die;

} catch (\Exception $e) {
    echo '<pre>';
    print_r($e);
}


