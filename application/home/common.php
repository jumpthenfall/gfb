<?php

/**
 * description: 获取随机字符串
 * @param int $len
 * @return string
 * User: zjc
 * email: zengjc08@163.com
 */
function noncestr($len = 6)
{
    $content = array_merge(range(0,9),range('a','z'),range('A','Z'));
    $str = '';
    for($i=0;$i<$len;++$i){
        $str .= $content[rand(0,61)];
    }
    return $str;
}

/**
 * description: fsockopen发送请求
 * @param $url 请求地址
 * @param $postData 请求参数
 * @return string
 * User: zjc
 * email: zengjc08@163.com
 */
function sock_query($url,$postData)
{
    $row = parse_url($url);
    $host = $row['host'];
    $port = isset($row['port']) ? $row['port']:80;
    $file = $row['path'];
    $post = "";
    foreach($postData as $k=>$v){
        $post .= rawurlencode($k)."=".rawurlencode($v)."&";
    }
    $post = substr( $post , 0 , -1 );
    $len = strlen($post);
    $fp = @fsockopen( $host ,$port, $errno, $errstr, 10);
    if (!$fp) {
        return "$errstr ($errno)\n";
    } else {
        $receive = '';
        $out = "POST $file HTTP/1.1\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Content-type: application/x-www-form-urlencoded\r\n";
        $out .= "Connection: Close\r\n";
        $out .= "Content-Length: $len\r\n\r\n";
        $out .= $post;
        fwrite($fp, $out);
        while (!feof($fp)) {
            $receive .= fgets($fp, 128);
        }
        fclose($fp);
        $receive = explode("\r\n\r\n",$receive);
        unset($receive[0]);
        return $receive;
    }
}




