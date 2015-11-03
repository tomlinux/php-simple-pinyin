<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
spl_autoload_register('loadClass'); 
$obj = new PinyinDict();
//
//$obj->save_dict_to_cache();
var_dump($obj->get_pinyin('银行'));
function loadClass($className)
{
    $fileName = namespaceConvert1($className);
    $fileName = dirname(__FILE__)  . '/../' . $fileName . '.php';
    if(file_exists($fileName)) {
        include_once $fileName;
    } else {
        die("$className not exist!");
    }
}

//$string = 'OpenAPI';
//$newStr = '';
// 方法1
function namespaceConvert1($string) {
    return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
}

//方法2 短期看效率比 方法1 高了一个数量级 但是长字符串拼接可能遇到性能瓶颈
function namespaceConvert2($string) {
    $len = strlen($string);
    $newStr = '';
    for ($i = 0; $i < $len; $i++) {
        $ch = $string[$i];
        if (($string[$i] <= 'Z') && ($string[$i] >= 'A')) {
            $ch = chr(ord($ch) + 32);
            if ($i > 0 && ($string[$i - 1] > 'Z' || $string[$i - 1] < 'A')) {
                $newStr .="_";
            }
        }
        $newStr .= $ch;
    }
    return $newStr;
}
