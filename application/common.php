<?php
use think\Db;
use taobao\AliSms;

/**
 * 字符串截取，支持中文和其他编码
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
	if (function_exists("mb_substr"))
		$slice = mb_substr($str, $start, $length, $charset);
	elseif (function_exists('iconv_substr')) {
		$slice = iconv_substr($str, $start, $length, $charset);
		if (false === $slice) {
			$slice = '';
		}
	} else {
		$re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		$re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		$re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		preg_match_all($re[$charset], $str, $match);
		$slice = join("", array_slice($match[0], $start, $length));
	}
	return $suffix ? $slice . '...' : $slice;
}



/**
 * 读取配置
 * @return array 
 */
function load_config(){
    $list = Db::name('config')->select();
    $config = [];
    foreach ($list as $k => $v) {
        $config[trim($v['name'])]=$v['value'];
    }

    return $config;
}



/**
 * 发送短信(参数：签名,模板（数组）,模板ID，手机号)
 */
function sms($signname='',$param=[],$code='',$phone)
{
    $alisms = new AliSms();
    $result = $alisms->sign($signname)->data($param)->code($code)->send($phone);
    return $result['info'];
}


//获取装备类别名称
function getLeiBieName($id)
{
    if(empty($id)){
        return "";
    }
    $info = Db::name('zb_lb')->where('id='.$id)->field('name')->find();
    if($info){
       return $info['name'] ;
    }else{
        return "";
    }
}



//获取装备品牌名称
function getPinPeiName($id)
{
    if(empty($id)){
        return "";
    }
    $info = Db::name('zb_pp')->where('id='.$id)->field('name')->find();
    if($info){
       return $info['name'] ;
    }else{
        return "";
    }
}


//生成网址的二维码 返回图片地址
function Qrcode($token, $url, $size = 8){ 

    $md5 = md5($token);
    $dir = date('Ymd'). '/' . substr($md5, 0, 10) . '/';
    $patch = 'qrcode/' . $dir;
    if (!file_exists($patch)){
        mkdir($patch, 0755, true);
    }
    $file = 'qrcode/' . $dir . $md5 . '.png';
    $fileName =  $file;
    if (!file_exists($fileName)) {

        $level = 'L';
        $data = $url;
        QRcode::png($data, $fileName, $level, $size, 2, true);
    }
    return $file;
}



/**
 * 循环删除目录和文件
 * @param string $dir_name
 * @return bool
 */
function delete_dir_file($dir_name) {
    $result = false;
    if(is_dir($dir_name)){
        if ($handle = opendir($dir_name)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir_name . DS . $item)) {
                        delete_dir_file($dir_name . DS . $item);
                    } else {
                        unlink($dir_name . DS . $item);
                    }
                }
            }
            closedir($handle);
            if (rmdir($dir_name)) {
                $result = true;
            }
        }
    }

    return $result;
}



//时间格式化1
function formatTime($time) {
    $now_time = time();
    $t = $now_time - $time;
    $mon = (int) ($t / (86400 * 30));
    if ($mon >= 1) {
        return '一个月前';
    }
    $day = (int) ($t / 86400);
    if ($day >= 1) {
        return $day . '天前';
    }
    $h = (int) ($t / 3600);
    if ($h >= 1) {
        return $h . '小时前';
    }
    $min = (int) ($t / 60);
    if ($min >= 1) {
        return $min . '分钟前';
    }
    return '刚刚';
}

//时间格式化2
function pincheTime($time) {
     $today  =  strtotime(date('Y-m-d')); //今天零点
      $here   =  (int)(($time - $today)/86400) ; 
      if($here==1){
          return '明天';  
      }
      if($here==2) {
          return '后天';  
      }
      if($here>=3 && $here<7){
          return $here.'天后';  
      }
      if($here>=7 && $here<30){
          return '一周后';  
      }
      if($here>=30 && $here<365){
          return '一个月后';  
      }
      if($here>=365){
          $r = (int)($here/365).'年后'; 
          return   $r;
      }
     return '今天';
}
function obj2array($obj)
{
    return json_decode(json_encode($obj),true);
}

function curl_request($url, $https = 'https', $method = 'get', $data = null,$token = '')
{
    //初始化curl
    $ch = curl_init();
    //设置curl
    $headers = array();
    $headers[] = 'Host:' . parse_url($url)['host'];
    $headers[] = 'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:55.0) Gecko/20100101 Firefox/55.0';
    $headers[] = 'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
    $headers[] = 'Accept-Language:zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3';
    $headers[] = 'Connection:keep-alive';
    $headers[] = 'Upgrade-Insecure-Requests:1';
    //发送url
    curl_setopt($ch, CURLOPT_URL, $url);
    //不直接输出，保存到变量中
//    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //设置链接等待时长
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    //关闭https协议头
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($https == 'https') {
        //因为是https协议，https的特点就是如要通信必须验证，验证不好做，所以关闭验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//关闭服务器ssl的证书验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //关闭服务器的证书验证
    }
    if ($method == 'post') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    //执行curl
    $res = curl_exec($ch);
    //销毁资源
    curl_close($ch);
    return $res;
}
