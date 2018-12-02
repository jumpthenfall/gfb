<?php
/**
 * Created by PhpStorm.
 * User: zjc
 * email: zengjc08@163.com
 * Date: 2017/11/8/008
 * Time: 10:21
 */

namespace app\home\controller;


class Wechat extends Base
{
    protected $appid= 'wx3b2ecd7a607b101d';
    protected $appsecret= 'b7ffd4bc26ab9b6e869300b282a04c03';
    protected $token= 'tuoke';


    public function index()
    {
        $postStr = file_get_contents('php://input');
        libxml_disable_entity_loader(true);
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $ss = json_encode($postObj);
        file_put_contents('./wechat.log',$ss);

        if($postObj->MsgType != 'event'){
            $str = <<<ETO
<xml>
<ToUserName><![CDATA[$postObj->FromUserName]]></ToUserName>
<FromUserName><![CDATA[$postObj->ToUserName]]></FromUserName>
<CreateTime>time()</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[$ss]]></Content>
</xml>
ETO;

            echo $str;
        }else{
            if($postObj->Event == 'subscribe'){
                //获取用户基本信息
                $token = $this->getToken();
                $url =  'https://api.weixin.qq.com/cgi-bin/user/info?access_token='. $token .'&openid='. $postObj->FromUserName .'&lang=zh_CN';
                $content = $this->_request($url);
                $content = json_decode($content,true);
                $data['wc_nickname'] = $content['nickname'];
                $data['wc_headimgurl'] = $content['headimgurl'];
                $data['wc_openid'] =(string) $postObj->FromUserName;
                $data['wc_regtime'] =(string) $postObj->CreateTime;
                $data['wc_ws_id'] = empty($postObj->EventKey)? 1 :(int) $postObj->EventKey;
                DB::name('way_customer')->insert($data);
            }
            if($postObj->Event == 'unsubscribe'){
                $data['wc_openid'] =(string) $postObj->FromUserName;

                DB::name('way_customer')->where($data)->delete();
            }
            $ed = json_encode($postObj->EventKey);
            $time = time();
            $str = <<<ETO
<xml>
<ToUserName><![CDATA[$postObj->FromUserName]]></ToUserName>
<FromUserName><![CDATA[$postObj->ToUserName]]></FromUserName>
<CreateTime>$time</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[$ss]]></Content>
</xml>
ETO;
            echo $str;
        }
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
    /**
     * description: 获取微信access_token
     * @return mixed
     * User: zjc
     * email: zengjc08@163.com
     */
    private function getToken()
    {
        $file = './accesstoken.log';
        if(file_exists($file)){
            $content = file_get_contents($file);
            $content = json_decode($content,true);
            if(time() - filemtime($file)<$content['expires_in']){
                return $content['access_token'];
            }
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->appid .'&secret='.$this->appsecret;
        $result =$this->_request($url);
        file_put_contents($file,$result);
        $result = json_decode($result,true);
        return $result['access_token'];
    }

    /**
     * description: 优惠券
     * User: zjc
     * email: zengjc08@163.com
     */
//    public function coupons()
//    {
//        echo 'aa';
//        $postStr = input("php://input"); //必须是网上服务器才能接受到数据
//        $phpi = file_get_contents('php://input');var_dump($phpi);
////        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
//
//        var_dump($postStr);
//        $post = input('request.');var_dump($post);
//        libxml_disable_entity_loader(true);
//        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
//        var_dump($postObj);exit;
//        //获取当前用户优惠券详情
//        $where['wc_openid'] = $postObj->FromUserName;
//        $detail =  M('way_customer')->where($where)->find();
//        $this->assign('openid',(string)$postObj->FromUserName);
//        $this->assign('status',$detail['wc_type']);
//        $this->display();
//
//    }
    public function login()
    {
        echo 'login';
//        $postStr = input("php://input");var_dump($postStr); //必须是网上服务器才能接受到数据
        $phpi = file_get_contents('php://input');var_dump($phpi);
        file_put_contents('./login.log',$phpi);
        echo 'end';
//        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];var_dump($postStr);


    }

    /**
     * description:调用创建自定义菜单方法
     * @return string
     * User: zjc
     * email: zengjc08@163.com
     */
    public function createMenu()
    { //菜单名称不能重名
        //只能接收json，如果是from表单发送过来的数据，必须编码json_encode();
        $str = '{
             "button":[
                 {  
                      "type":"view",
                      "name":"优惠券", 
                      "url":"http://a.whhykj.cn/home/wechat/coupons.html"
                  },
                  {  
                      "type":"view",
                      "name":"denglu", 
                      "url":"http://a.whhykj.cn/home/wechat/login.html"
                  }
              ] 
         }';
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$this->getToken()}";
        //发送要请求的url和数据
        $res = $this->_request($url,'https','post',$str);
        $resObj = json_decode($res);
        if($resObj->errmsg == 'ok'){
            return '创建成功!';
        }else{
            return '创建失败!';
        }
    }

    //发送请求的方法
    /*
     * param string $url
     */
    public function _request($url,$https='https',$method='get',$data=null){
        //初始化curl
        $ch = curl_init();
        //设置curl
        //发送url
        curl_setopt($ch,CURLOPT_URL,$url);
        //不直接输出，保存到变量中
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        //关闭https协议头
        curl_setopt($ch,CURLOPT_HEADER,false);
        if($https == 'https'){
            //因为是https协议，https的特点就是如要通信必须验证，验证不好做，所以关闭验证
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);//关闭服务器ssl的证书验证
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false); //关闭服务器的证书验证
        }
        if($method == 'post'){
            curl_setopt($ch,CURLOPT_POST,true);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        }

        //执行curl
        $res = curl_exec($ch);
        return $res;
        //销毁资源
        curl_close($ch);
    }

}