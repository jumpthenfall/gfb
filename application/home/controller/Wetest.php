<?php
/**
 * Created by PhpStorm.
 * User: zjc
 * email: zengjc08@163.com
 * Date: 2017/11/8/008
 * Time: 10:21
 */

namespace app\home\controller;


class WeTest extends Base
{
public function index()
{
    echo  $_GET['echostr'];exit;
    $this->checkSignature();
}
    private function checkSignature()
    {
        $temp = input("get.");
        file_put_contents('./wetest.log',json_encode($temp));
//        _GET["timestamp"];
//        _GET["nonce"];
//
//        tmpArr = array(timestamp, $nonce);
//        sort($tmpArr, SORT_STRING);
//        $tmpStr = implode( $tmpArr );
//        $tmpStr = sha1( $tmpStr );
//
//        if( signature ){
//            return true;
//        }else{
//            return false;
//        }
    }
}