<?php

namespace app\api\controller;
use think\Controller;
use app\api\model\CodeModel;
use think\Db;

/**
 * Code: 验证码
 */
class Code
{
	/**
	 * get: 文章列表
	 * path: list
	 * method: list
	 */

    public function index(){

        $mobile = input('param.mobile');

        if(!(preg_match('/^(((13[0-9]{1})|(15[0-9]{1})|(14[0-9]{1})|(18[0-9]{1})|(17[0-9]{1}))+\d{8})$/', $mobile))){
             
            $array_userinfo_res['code'] = 0;
            $array_userinfo_res['message'] = '请输入正确的手机号码！';
            $array_userinfo_res['result'] = $mobile;
             
            return jsonp($array_userinfo_res);
        }
    
        //随机生成6位验证码
        srand((double)microtime()*10000);//create a random number feed.
        $ychar="0,1,2,3,4,5,6,7,8,9";
        $list=explode(",",$ychar);
        $authnum = '';
        for($i=0;$i<4;$i++){
            $randnum  = rand(0,9); // 10+26;
            $authnum .= $list[$randnum];
        }
    
        $chc_code = $authnum;
    
        $cont = "【优益GO】您的手机号：".$mobile."，验证码：".$chc_code."。";
    
        $data=array(
            'account'=>'E2D68E90E29C4B869C5F1CCE5B500395',
            'token'=>'0f3e517302514622b27eb44c543ef9b1',
            'tempid'=>67,
            'mobile'=>$mobile,
            'type'=>0,
            'content'=>$cont
        );
    
        $data=http_build_query($data);
    
    
    
        $opts=array(
    
            'http'=>array(
    
                'method'=>'POST',
    
                'header'=>"Content-type: application/x-www-form-urlencoded/r/n".
    
                "Content-Length: ".strlen($data)."/r/n",
    
                'content'=>$data
    
            ),
    
        );
    
        $context=stream_context_create($opts);
    
        $html=file_get_contents('http://apis.laidx.com:80/SMS/Send',false,$context);
        //120.24.161.220:8800
        //120.76.25.160
        //apis.laidx.com:80
    
        $html_res = json_decode($html,true);
        
    
    
    
        if($html_res['Message'] == '提交成功' AND $html_res['Code']== 0){

            $map = [];
            $map['mobile']    = $mobile;
            $map['code']      = $chc_code;
            $map['source']    = '微信端获取验证码';
            $map['regtime']   = time();
            $map['status']    = 1;
             
            $code = new CodeModel();    
            $lists = $code->insertCode($map);

            return jsonp($lists);

    
        }else {
            $restun_inf['code'] = 0;
            $restun_inf['msg'] = '验证码发送失败！';
            $restun_inf['data'] = '';
            return jsonp($restun_inf);
        }
    }

}