<?php
namespace app\home\controller;
use app\home\model\IdentificationModel;

class Jssdk{

  public function __construct() {
    $this->appId = 'wx1a977e6944e78282';
    $this->appSecret = '40af637ea814759753770a4e87228a15';
  }

  public function getSignPackage() {
    
    $jsapiTicket = $this->getJsApiTicket();
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );

    if(false !== $signature){
        $rest['code'] = 1;
        $rest['data'] = $signPackage;
        $rest['msg']  = '时间有效，无需更新';
        //return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
        return $rest['data'];
    }else{
        
        //return ['code' => 1, 'data' => '', 'msg' => '更新有效时间成功'];

      $rest['code'] = 1;
      $rest['data'] = $signPackage;
      $rest['msg'] = '更新有效时间成功';
      //return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
      return $rest['data'];
    }
   // return jsonp($signPackage); 
  }

  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  private function getJsApiTicket() {
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
    $map = [];
    $map['tempName'] = 'jsapi_ticket';
    $identification = new IdentificationModel();    
    $lists = $identification->getIdentification($map);

    if ($lists['tempValidTime'] < time()) {
      $accessToken = $this->getAccessToken();
      $url = "http://api.weixin.qq.com/cgi-bin/ticket/getticket?type=1&access_token=$accessToken";
     // exit;
      $res = json_decode($this->httpGet($url), true);

      $ticket = $res['ticket'];
     // exit;
      if ($ticket) {
        $data['tempValidTime'] = time() + 7000;
        $data['id'] = $lists['id'];
        $data['tempContent'] = $ticket;
        $resupdate = $identification->updateIdentification($data);
      }
    } else {
      $ticket = $lists['tempContent'];
    }

    return $ticket;
  }

  private function getAccessToken() {
    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
    $map = [];
    $map['tempName'] = 'access_token';
    $identification = new IdentificationModel();    
    $lists = $identification->getIdentification($map);

    if ($lists['tempValidTime'] < time()) {
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";

      $res = json_decode($this->httpGet($url), true);

     // $pp = explode('"', $res);

      $access_token = $res['access_token'];
  //var_dump($res["access_token"]);
      //exit;
      if ($access_token) {
        $data['tempValidTime'] = time() + 7000;
        $data['id'] = $lists['id'];
        $data['tempContent'] = $access_token;
        $resupdate = $identification->updateIdentification($data);
      }
    } else {
      $access_token = $lists['tempContent'];
    }
    return $access_token;
  }

  private function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    /*curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);*/
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }
}

