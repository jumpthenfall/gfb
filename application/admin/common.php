<?php
use think\Db;

/**
 * 将字符解析成数组
 * @param $str
 */
function parseParams($str)
{
    $arrParams = [];
    parse_str(html_entity_decode(urldecode($str)), $arrParams);
    return $arrParams;
}


/**
 * 子孙树 用于菜单整理
 * @param $param
 * @param int $pid
 */
function subTree($param, $pid = 0)
{
    static $res = [];
    foreach($param as $key=>$vo){

        if( $pid == $vo['pid'] ){
            $res[] = $vo;
            subTree($param, $vo['id']);
        }
    }

    return $res;
}


/**
 * 记录日志
 * @param  [type] $uid         [用户id]
 * @param  [type] $username    [用户名]
 * @param  [type] $description [描述]
 * @param  [type] $status      [状态]
 * @return [type]              [description]
 */
function writelog($uid,$username,$description,$status)
{

    $data['admin_id'] = $uid;
    $data['admin_name'] = $username;
    $data['description'] = $description;
    $data['status'] = $status;
    $data['ip'] = request()->ip();
    $data['add_time'] = time();
    $log = Db::name('Log')->insert($data);

}


/**
 * 整理菜单树方法
 * @param $param
 * @return array
 */
function prepareMenu($param)
{
    $parent = []; //父类
    $child = [];  //子类

    foreach($param as $key=>$vo){

        if($vo['pid'] == 0){
            $vo['href'] = '#';
            $parent[] = $vo;
        }else{
            $vo['href'] = url($vo['name']); //跳转地址
            $child[] = $vo;
        }
    }

    foreach($parent as $key=>$vo){
        foreach($child as $k=>$v){

            if($v['pid'] == $vo['id']){
                $parent[$key]['child'][] = $v;
            }
        }
    }
    unset($child);
    return $parent;
}


/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    for ($i = 0; $size >= 1024 && $i < 5; $i++) {
        $size /= 1024;
    }
    return $size . $delimiter . $units[$i];
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
//发送请求的方法
/*
 * param string $url
 */
// function curl_request($url, $https = 'https', $method = 'get', $data = null)
//{
//    //初始化curl
//    $ch = curl_init();
//    //设置curl
//    //发送url
//    curl_setopt($ch, CURLOPT_URL, $url);
//    //不直接输出，保存到变量中
//    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//    //关闭https协议头
//    curl_setopt($ch, CURLOPT_HEADER, false);
//    if ($https == 'https') {
//        //因为是https协议，https的特点就是如要通信必须验证，验证不好做，所以关闭验证
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//关闭服务器ssl的证书验证
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //关闭服务器的证书验证
//    }
//    if ($method == 'post') {
//        curl_setopt($ch, CURLOPT_POST, true);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//    }
//
//    //执行curl
//    $res = curl_exec($ch);
//    //销毁资源
//    curl_close($ch);
//    return $res;
//}

/**
 * description: 获取随机字符串
 * @param int $len
 * @return string
 * User: zjc
 * email: zengjc08@163.com
 */
function noncestr($len = 6)
{
    $content = "123456789ABCDEFGHIGKMNPQRSTUVWXYZ";
    $str = '';
    for($i=0;$i<$len;++$i){
        $str .= $content[rand(0,32)];
    }
    return $str;
}

/**
 * description: 检测手机号码
 * @param $phone
 * @return int
 * User: zjc
 * email: zengjc08@163.com
 */
function checkPhone($phone)
{
    $preg = "/^1[0-9]{10}$/";
    return preg_match($preg,$phone);
}

/**
 * description:隐藏手机号码中间四位，用*代替
 * @param $phone 需要处理的手机号码
 * @return mixed
 * User: zjc
 * email: zengjc08@163.com
 */
function hidePhone($phone)
{
    return substr_replace($phone,'****',3,4);
}

/**
 * description: 隐藏列表中所有手机号的后四位
 * @param $data array 需要处理的二维数组
 * @param $field string 手机号所在的字段名
 * @return mixed
 * User: zjc
 * email: zengjc08@163.com
 */
function hidePhoneinList($data,$field)
{
    if(empty($data)){//空值则直接返回
        return $data;
    }
    foreach ($data as &$d){
        $d[$field] = hidePhone($d[$field]);
    }

    return $data;
}

/**
 * 格式话卡配置表数据
 * @param $list
 */
function formatCardConfig($list)
{
    if(!$list || !is_array($list)){return [];}
    $config = [];
    foreach ($list as $l){
        if(!$l['col_name']){
            continue;
        }
        $config[$l['col_name']] = $l;
    }
    return $config;
}