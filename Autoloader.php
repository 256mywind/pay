<?php

/**
 * 类库自动加载，写死路径，确保不加载其他文件。
 * @param string $class 对象类名
 * @return void
 */

class Autoloader
{

    public static function autoload($class)
    {
        $name = $class;
        if (false !== strpos($name, '\\')) {
            $name = strstr($class, '\\', true);
        }
        
        $filename = __DIR__ . "/Config/" . $name . ".php";
        if (is_file($filename)) {
            include $filename;
            return;
        }
        $filename = __DIR__ . "/WxPay/" . $name . ".php";
        if (is_file($filename)) {
            include $filename;
            return;
        }
        $filename = __DIR__ . "/WxPay/lib/" . $name . ".php";
        if (is_file($filename)) {
            include $filename;
            return;
        }
        $filename = __DIR__ . "/AliPay/lib/" . $name . ".php";
        if (is_file($filename)) {
            include $filename;
            return;
        }
    }
}

spl_autoload_register('Autoloader::autoload');
?>