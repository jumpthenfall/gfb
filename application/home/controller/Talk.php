<?php
/**
 * Created by PhpStorm.
 * User: zjc
 * email: zengjc08@163.com
 * Date: 2017/11/8/008
 * Time: 10:21
 */

namespace app\home\controller;

require_once "WxPayData.php";
require_once "WxPayApi.php";
use app\home\model\StoresModel;
use think\Db;
use think\Exception;
use sms\OZSMsg;
use com\Qrcode;
class Talk extends Base
{
    protected $appid;
    protected $appsecret;
    protected $token;

    public function __construct()
    {
        parent::__construct();
        $this->appid = config('wechat_appid');
        $this->appsecret = config('wechat_appsecret');
        $this->token = config('wechat_token');
    }

    /**
     * description: 公众号入口
     * User: zjc
     * email: zengjc08@163.com
     */
    public function index()
    {
        $postStr = file_get_contents("php://input");
        libxml_disable_entity_loader(true);
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        file_put_contents('./data/log/scene.log',json_encode($postObj).substr((string)$postObj->EventKey,8) . "||||||||||||",FILE_APPEND);
//        $postObj = json_decode(json_encode($postObj),true);
        if ($postObj->MsgType != 'event') {
            $str = $this->returnNews($postObj);
            echo $str;
        } else {
            if ($postObj->Event == 'subscribe') {//关注事件
                //获取用户基本信息
                $token = $this->getToken();
                $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $token . '&openid=' . $postObj->FromUserName . '&lang=zh_CN';
                $content = $this->_request($url);
                $content = json_decode($content, true);
                $data['wc_nickname'] = $content['nickname'];
                $data['wc_headimgurl'] = $content['headimgurl'];
                $data['wc_code'] = noncestr(8);
                $data['wc_openid'] = (string)$postObj->FromUserName;
                $data['wc_regtime'] = (string)$postObj->CreateTime;
                if(isset($postObj->EventKey)&& !empty($postObj->EventKey)){//推广商家ID
                    $data['wc_ws_id'] = substr((string)$postObj->EventKey,8);
                }else{
                    $data['wc_ws_id'] = 2;
                }
                //判断数据库有无该用户数据
                $isexist = DB::name('way_customer')->where('wc_openid','=',$data['wc_openid'])->find();
                if($isexist){
                    //用户之前关注过公众号
                    //更新基本信息
                   $res = DB::name('way_customer')->where('wc_openid','=',$data['wc_openid'])->update(['wc_nickname'=>$data['wc_nickname'],'wc_headimgurl'=>$data['wc_headimgurl']]);
                }else{
                    //之前未关注过
                    $res = DB::name('way_customer')->insert($data);
                    //给商家发放推广费，扫码2元
                    Db::name('way_shop')->where('id',$data['wc_ws_id'])->setInc('ws_wayTotal',2);
                }
            }elseif($postObj->Event == 'SCAN'){
                $str = $this->returnNews($postObj);
            }else{
                $str = $this->returnNews($postObj);
            }
//            if ($postObj->Event == 'unsubscribe') {
//                $data['wc_openid'] = (string)$postObj->FromUserName;
//
//                DB::name('way_customer')->where($data)->delete();
//            }
            $str = $this->returnNews($postObj);
            echo $str;
        }
    }
    public function activityOne()
    {
        return  $this->fetch('activity_one');
    }
    public function activityTwo()
    {
       return  $this->fetch('activity_two');
    }

    public function userCenter()
    {
        session_start();
        //获取手机号
        $phone = input('phone');
        $type = input('type');
        //判断是否登录
        if(!$phone&&!isset($_SESSION['phone'])){
            $this->assign('number',0);
            $this->assign('is_login',0);
            $this->assign('count',0);
            return  $this->fetch('user_center');
        }else{
            if(!$phone){
                $phone = $_SESSION['phone'];
            }
        }

        if($type == 'recharge'){
            //查看该手机号是否已经是会员
            $exist = Db::name('wine_user')->where('mobile','=',$phone)->find();
            if(!$exist){//该手机号不是会员
                //充值跳转，数据库新增数据
                $input['mobile'] = $phone;
                $input['name'] = "公众号用户".$phone;
                $input['wine_nums'] = 500;
                $input['regist_time'] = time();
                $input['status'] = 1;
                $user_id = Db::name('wine_user')->insertGetId($input);
                if($user_id){
                    //添加用户成功
                    $ws['wine_user_id'] = $user_id;
                    $ws['wine_nums'] = 500;
                    $ws['stores_id'] = 2;
                    Db::name('wine_stores')->insertGetId($ws);
                }
            }else{//该手机号已是会员
                $user_id = $exist['id'];
                //查看该手机号是不是平台会员
                $stores_exist = Db::name('wine_stores')->where(['wine_user_id'=>$exist['id'],'stores_id'=>2])->find();
                if($stores_exist){//已经是平台用户
                    //修改当前存酒
                    $wine_nums = $stores_exist['wine_nums'] + 500;
                    Db::name('wine_stores')->where(['wine_user_id'=>$exist['id'],'stores_id'=>2])->setField('wine_nums',$wine_nums);

                }else{
                    //添加用户成功
                    $ws['wine_user_id'] = $exist['id'];
                    $ws['wine_nums'] = 500;
                    $ws['stores_id'] = 2;
                    Db::name('wine_stores')->insertGetId($ws);
                }
            }
            //添加充值记录
            $log['order_num'] = "公众号用户充值" . $phone;
            $log['wine_user_id'] = $user_id;
            $log['amount'] = 1000;
            $log['wine_nums'] = 500;
            $log['admin_id'] = 1;
            $log['stores_id'] = 2;
            $log['referees'] = "公众号用户" . $phone;
            $log['regist_time'] = time();

            Db::name('wine_recharge')->insertGetId($log);

        }else{
//            判断用户是否登录
        }
        //通过手机号查询该用户在平台下的存酒数量
        $user = Db::name('wine_user')->alias('wu')
            ->field('ws.wine_nums,wu.wine_nums as number,wu.mobile,wu.id')
            ->join('tp_wine_stores ws',"ws.wine_user_id = wu.id",'left')
            ->where('wu.mobile','=',$phone)
            ->where('ws.stores_id','=',2)
            ->find();
        //累计充酒
        if($user){
            $total_count = Db::name('wine_recharge')->where(['wine_user_id'=>$user['id'],'stores_id'=>2])->sum('wine_nums');
        }else{
            $total_count = 0;
        }
        $total_num = $user ? $user['wine_nums'] : 0;
        $_SESSION['phone'] = $phone;
        $is_login = 1;
        $this->assign('number',$total_num);
        $this->assign('is_login',$is_login);
        $this->assign('count',$total_count);
        return  $this->fetch('user_center');
    }
    /**
     * description: 优惠券
     * User: zjc
     * email: zengjc08@163.com
     */
    public function coupons()
    {
        $getStr = input("get.");
        //获取网页授权access_token
        $token = $this->getAuthToken($getStr['code']);
        $token = json_decode($token, true);
        //获取用户基本信息
        $info = $this->userAuthInfo($token['access_token'], $token['openid']);
        $info = json_decode($info, true);
        //查询用户信息
        $where['wc_openid'] = $info['openid'];
        $detail = DB::name('way_customer')->where($where)->find();
        //
        $winfo['wc_nickname'] = $info['nickname'];
        $winfo['wc_headimgurl'] = $info['headimgurl'];
        if (!$detail) {
            //如果数据库没有该用户信息,则添加
            $winfo['wc_openid'] = $info['openid'];
            $winfo['wc_regtime'] = time();
            $winfo['wc_code'] = noncestr(8);
            $winfo['wc_ws_id'] =  1;
            $add_res = Db::name('way_customer')->insert($winfo);
        } else {
            //存在则更新基本信息
            $up_res = Db::name('way_customer')->where('id', $detail['id'])->update($winfo);
        }
        file_put_contents('./wechat.log', json_encode($info));
        session_start();
        $_SESSION['wechatUserInfo'] = $info;
        $this->redirect('home/coupon/couponsIndex');

    }

    /**
     * description:优惠券首页
     * @return mixed
     * User: zjc
     * email: zengjc08@163.com
     */
    public function couponsIndex()
    {
        session_start();
        if (!isset($_SESSION['wechatUserInfo'])) {
            $url = "http://a.whhykj.cn/home/coupon/coupons.html";
            $this->redirect($this->authUrl($url));
        }
        //用户微信信息
        $weinfo = $_SESSION['wechatUserInfo'];
        //查询用户优惠券信息
        $couponinfo = Db::name('way_customer')->field('id,wc_openid,wc_type,wc_code,wc_ws_id')->where(['wc_openid'=>$weinfo['openid'],'wc_status'=>1])->find();
        //获取微信接口配置参数
        $wxconfig = $this->wxconfig();
        //获取活动门店信息
        $s_model = new StoresModel();
        $storeinfo = $s_model->StoreInfoByShopId($couponinfo['wc_ws_id']);
        $this->assign('wxconfig',$wxconfig);
        $this->assign('couponinfo',$couponinfo);
        $this->assign('storeinfo',$storeinfo);
        if($couponinfo['wc_type'] ==1){
            return $this->fetch('couponsindex');
        }else{
            return $this->fetch('myCoupons');
        }
    }

    /**
     * description: 优惠券支付
     * @return \think\response\Json
     * User: zjc
     * email: zengjc08@163.com
     */
    public function webPay()
    {
        $openid = input('openid','oGMKCw7rON4DiEcOU7NOxltitEs0');
        $storeid = input('storeid');
        $money = 60 * 100;//支付金额，单位为分
        $out_trade_no = 'HYKJ'.time().noncestr(8);
        $input = new WxPayUnifiedOrder();
        $input->SetBody("tuokehuodong");
        $input->SetAttach($openid);
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($money);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("touke");
        $input->SetNotify_url("http://a.whhykj.cn/home/notify/wxpay.html");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
        $order = WxPayApi::unifiedOrder($input);

        if($order['return_code']== 'SUCCESS' && $order['result_code']== 'SUCCESS'){
            $param['appId'] = $order['appid'];
            $param['timeStamp'] = (string)time();
            $param['nonceStr'] = md5(nonceStr('8'));
            $param['signType'] = 'MD5';
            $param['package'] = "prepay_id=" . $order['prepay_id'];
            $param['paySign'] = $this->MakeSign($param);
            return json(['code'=>0,'data'=>$param,'msg'=>'']);
        }else{
            return json(['code'=>4001,'data'=>'','msg'=>$order['return_msg'] . '错误代码' . $order['return_code']]);
        }

    }

    /**
     * description: 商户中心登录页面
     * User: zjc
     * email: zengjc08@163.com
     */
    public function login()
    {
        if (request()->isPost()) {
            $param = input('post.');
            if(! isset($param['mobile']) || !preg_match('/^1[0-9]{10}$/',$param['mobile'])){
                return json(['code'=>-1,'data'=>'','msg'=>"请输入正确的手机号"]);
            }
            //通过手机号查询商家是否存在
            $shop = Db::name('way_shop')->where(['ws_mobile'=>$param['mobile']])->find();
            if(!$shop){
                return json(['code'=>-1,'data'=>'','msg'=>"该手机号不是商家"]);
            }
            if($shop['ws_status']== 2){
                return json(['code'=>-1,'data'=>'','msg'=>"该商家被禁用"]);
            }
            if(md5(trim($param['pwd'])) != $shop['ws_pwd']){
                return json(['code'=>-1,'data'=>'','msg'=>"密码错误"]);
            }
            $re['ws_mobile'] =$shop['ws_mobile'];
            session_start();
           $_SESSION['shop'] = $shop;

            return json(['code'=>200,'data'=>$re,'msg'=>"登录成功"]);
        }
        return $this->fetch();


    }

    /**
     * description: 商家用户中心
     * @return mixed|\think\response\Json
     * User: zjc
     * email: zengjc08@163.com
     */
    public function  Center()
    {
        if(request()->isPost()){
            $mobile = input('ws_mobile');
            session_start();
            if(! isset($_SESSION['shop']) || $_SESSION['shop']['ws_mobile'] != $mobile){
                return json(['code'=>500,'data'=>'','msg'=>"请登录"]);
            }
            if(! isset($mobile) || !preg_match('/^1[0-9]{10}$/',$mobile)){
                return json(['code'=>-1,'data'=>'','msg'=>"网络错误"]);
            }
            $shop = Db::name('way_shop')
                ->field('id as shopId,ws_name,ws_mobile,ws_head_img,ws_address,ws_talk_url,ws_wayTotal,ws_costTotal,ws_status,ws_couponTotal')
                ->where('ws_mobile','=',$mobile)->find();
            if(! $shop){
                return json(['code'=>-1,'data'=>'','msg'=>"网络错误"]);
            }

            return json(['code'=>200,'data'=>$shop,'msg'=>'']);
        }

        return $this->fetch('user_center');
    }

    /**
     * description: 商家推出登录
     * @return \think\response\Json
     * User: zjc
     * email: zengjc08@163.com
     */
    public function logout()
    {
        $mobile = input('mobile');
        session_start();
        if(isset($_SESSION['shop'])){
            unset($_SESSION['shop']);
        }
        return json(['code'=>200,'data'=>'','msg'=>'']);
    }

    /**
     * description: 商家提现
     * @return mixed|\think\response\Json
     * User: zjc
     * email: zengjc08@163.com
     */
    public function tiXian()
    {
        if(request()->isPost()){
            $params = input('post.');
            try{
                Db::startTrans();
                session_start();
                if(!isset($_SESSION['shop'])|| empty($_SESSION['shop'])){
                    Db::rollback();
                    return json(['code'=>4008,'data'=>'','msg'=>'登录状态异常，请重新登录']);
                }
                if(! $params['we_price'] || ! $params['we_bank'] ||! $params['we_bank_branch'] ||! $params['we_bank_name'] ||! $params['we_bank_number']){
                    throw Exception('请填写所有信息');
                }
                if(!is_numeric($params['we_price'])){
                    throw Exception('请填写正确的金额');
                }
                if(!is_numeric($params['we_bank_number'])){
                    throw Exception('请填写正确的卡号');
                }
                if(!is_numeric($params['shop_id'])){
                    throw Exception('网络错误');
                }
                if($_SESSION['shop']['id'] != $params['shop_id']){
                    Db::rollback();
                    return json(['code'=>4008,'data'=>'','msg'=>'登录状态异常，请重新登录']);
                }

                $content = Db::name('way_shop')->field('ws_wayTotal,ws_couponTotal')->where('id',$params['shop_id'])->find();
                if($params['we_price'] > $content['ws_couponTotal'] + $content['ws_wayTotal']){
                    throw Exception('申请金额大于可提现金额');
                }
                //更新奖励金额
                if($params['we_price']>$content['ws_wayTotal']){
                    $up['ws_wayTotal'] = 0;
                    $up['ws_couponTotal'] = $content['ws_wayTotal'] + $content['ws_couponTotal'] - $params['we_price'];
                }else{
                    $up['ws_wayTotal'] = $content['ws_wayTotal'] - $params['we_price'];
                }
                $res = Db::name('way_shop')->where('id',$params['shop_id'])->update($up);
                if(!$res){
                    throw Exception('更新奖金失败，请刷新重试');
                }
                //生成提现记录
                $params['we_ws_id'] = $params['shop_id'];
                unset($params['shop_id']);
                $params['we_apply_time'] = time();
                $params['we_status'] = 1;
                $id = Db::name('way_expressive')->insertGetId($params);
                if(!$id){
                    throw Exception('生成记录，请刷新重试');
                }
                Db::commit();
                return json(['code'=>200,'data'=>$params['we_price'],'msg'=>'操作成功']);
            }catch (Exception $e){
                Db::rollback();
                return json(['code'=>4001,'data'=>'','msg'=>$e->getMessage()]);
            }
        }
        $id = input('shopId');
        if($id){//查询商家可提现金额
            $content = Db::name('way_shop')->field('ws_wayTotal,ws_couponTotal')->where('id',$id)->find();
            $total = $content ? ($content['ws_wayTotal'] + $content['ws_couponTotal']) : 0;
        }
        $this->assign('id',$id);
        $this->assign('total',$total);
        return $this->fetch('tixian');
    }

    /**
     * description: 提现成页面
     * @return mixed
     * User: zjc
     * email: zengjc08@163.com
     */
    public function submitSuccess()
    {
        $total = input('money');
        $this->assign('money',$total);
        return $this->fetch('success');
    }


    /**
     * description: 获取网页授权access_token，此access_token与基础支持的access_token不同
     *  https://api.weixin.qq.com/sns/oauth2/access_token
     * ?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code
     * @param $code 填写第一步获取的code参数
     * User: zjc
     * email: zengjc08@163.com
     */
    protected function getAuthToken($code)
    {
        $param['appid'] = $this->appid;
        $param['secret'] = $this->appsecret;
        $param['code'] = $code;
        $param['grant_type'] = "authorization_code";
        //生成请求参数
        $query = http_build_query($param);
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?" . $query;
        $res = $this->_request($url);
        return $res;
    }

    /**
     * description: 通过网页授权access_token获取用户信息
     * @param $token 此access_token与基础支持的access_token不同
     * https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN
     * @param $openid
     * User: zjc
     * email: zengjc08@163.com
     */
    protected function userAuthInfo($token, $openid)
    {
        $param['access_token'] = $token;
        $param['openid'] = $openid;
        $param['lang'] = "zh_CN";
        $url = "https://api.weixin.qq.com/sns/userinfo?" . http_build_query($param);
        $res = $this->_request($url);
        return $res;
    }

    /**
     * description: 获取微信基础access_token
     * @return mixed
     * User: zjc
     * email: zengjc08@163.com
     */
    public function getToken()
    {
        $file = './data/log/accesstoken.log';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $content = json_decode($content, true);
            if (time() - filemtime($file) < $content['expires_in']) {
                return $content['access_token'];
            }
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->appid . '&secret=' . $this->appsecret;
        $result = $this->_request($url);
        file_put_contents($file, $result);
        $result = json_decode($result, true);
        return $result['access_token'];
    }
    /**
     * description:调用创建自定义菜单方法
     * @return string
     * User: zjc
     * email: zengjc08@163.com
     */
    public function createMenu()
    { //菜单名称不能重名
        //带授权链接
        //https://open.weixin.qq.com/connect/oauth2/authorize?
        //appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE#wechat_redirect
        $param = "appid=" . $this->appid . "&redirect_uri=" . urlencode("http://a.whhykj.cn/home/coupon/coupons.html");
        $param .= "&response_type=code&scope=snsapi_userinfo&state=399#wechat_redirect";
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?" . $param;
        //只能接收json，如果是from表单发送过来的数据，必须编码json_encode();
        $str = '{
             "button":[
                  {  
                      "type":"view",
                      "name":"优惠券", 
                      "url":"' . $url . '"
                  },
                  {  
                      "type":"view",
                      "name":"商户中心", 
                      "url":"http://a.whhykj.cn/home/coupon/userCenter.html"
                  }
              ] 
         }';
//        http://a.whhykj.cn/home/coupon/login.html
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$this->getToken()}";
        //发送要请求的url和数据
        $res = $this->_request($url, 'https', 'post', $str);
        $resObj = json_decode($res);
        if ($resObj->errmsg == 'ok') {
            return '创建成功!';
        } else {
            return '创建失败!';
        }
    }

    //发送请求的方法
    /*
     * param string $url
     */
    public function _request($url, $https = 'https', $method = 'get', $data = null)
    {
        //初始化curl
        $ch = curl_init();
        //设置curl
        //发送url
        curl_setopt($ch, CURLOPT_URL, $url);
        //不直接输出，保存到变量中
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //关闭https协议头
        curl_setopt($ch, CURLOPT_HEADER, false);
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
        return $res;
        //销毁资源
        curl_close($ch);
    }

    /**
     * description: 生成带授权的链接
     * @param $url
     * @return string
     * User: zjc
     * email: zengjc08@163.com
     */
    public function authUrl($url)
    {
        //带授权链接  参数的顺序必须保证正确
        //https://open.weixin.qq.com/connect/oauth2/authorize?
        //appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE#wechat_redirect
        $param = "appid=" . $this->appid . "&redirect_uri=" . urlencode($url) . "&response_type=code&scope=snsapi_userinfo&state=399#wechat_redirect";
        $authUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?" . $param;
        return $authUrl;
    }

    /**
     * description: 微信接口权限验证配置
     * @return string
     * User: zjc
     * email: zengjc08@163.com
     */
    public function wxconfig()
    {
        $url = "http://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        $arr['url'] = $url;
        $arr['timestamp'] = time();
        $arr['noncestr']= md5(noncestr(8));
        $arr['jsapi_ticket'] = $this->jsapi_ticket();
        ksort($arr);
        $str = '';
        foreach ($arr as $k=>$v){
            $str .= $k . "=" . $v ."&";
        }
        $str = substr($str,0,-1);
        $arr['signature']= sha1($str);
        $arr['appId'] = $this->appid;
        file_put_contents('./data/log/jsapi.log',json_encode($arr).$str);
        unset($arr['jsapi_ticket']);
        unset($arr['url']);
        return $arr;

    }

    /**
     * description: 获取微信网页签名jsapi_ticket
     * @return mixed
     * User: zjc
     * email: zengjc08@163.com
     */
    protected function jsapi_ticket()
    {
        $file = './data/log/jsapiticket.log';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $content = json_decode($content, true);
            if (time() - $content['time'] < $content['expires_in']) {
                return $content['ticket'];
            }
        }
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=". $this->getToken() ."&type=jsapi";
        $result = $this->_request($url);
        $result = json_decode($result, true);
        $result['time'] = time()-30;
        file_put_contents($file, json_encode($result));
        return $result['ticket'];
    }

    /**
     * description: 生成带参数二维码
     * @param array $param 请求参数
     * @param string $to_short 是否转短链接
     * User: zjc
     * email: zengjc08@163.com
     */
    public function getQRCode($action_name='QR_SCENE',array $scene=array(),$expire_seconds="604800")
    {
        $param['action_name'] = $action_name;
        $param['expire_seconds'] = $expire_seconds;
        $param['action_info'] =['scene'=>$scene];
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=" . $this->getToken();
        $long_result = $this->_request($url,'https','post',json_encode($param));
        file_put_contents('./data/log/qr.log',$long_result);
        $long_result = json_decode($long_result,true);
        $qr_url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . $long_result['ticket'];
        return $qr_url;
    }

    /**
     * description: 生成签名
     * @param $param
     * @return string
     * User: zjc
     * email: zengjc08@163.com
     */
    protected function MakeSign($param)
    {
        //签名步骤一：按字典序排序参数
        ksort($param);
        $string = "";
        foreach ($param as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $string .= $k . "=" . $v . "&";
            }
        }
        $string = trim($string, "&");
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".'40af637ea814759753770a4e87228a15';
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * description: 获取素材列表
     * User: zjc
     * email: zengjc08@163.com
     */
    public function batchget_material($type = 'news',$offset =0,$count =5)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=".$this->getToken();
        $param = '{"type":"' . $type . '", "offset":"' . $offset . '", "count":"' . $count .'"}';
        $material =  $this->_request($url,'https','post',$param);
        file_put_contents('./data/material.txt',$material);
    }

    public function returnNews($postObj)
    {
        $str = <<<ETO
<xml>
<ToUserName><![CDATA[$postObj->FromUserName]]></ToUserName>
<FromUserName><![CDATA[$postObj->ToUserName]]></FromUserName>
<CreateTime>time()</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>1</ArticleCount>
<Articles>
<item>
<Title><![CDATA[长久国际俱乐部，50元啤酒5元疯抢，狂戳......]]></Title> 
<Description><![CDATA[点380元或680套餐，房费全免，尽情畅游中...]]></Description>
<PicUrl><![CDATA[https://mmbiz.qlogo.cn/mmbiz_jpg/pzu0TzRGDEwOwtB4RAo1WcCP9ZwXGXDicDkFW4icU3TgXK7mQNYibjQ46oyeXVghICFRPV2VKAgud9bpTBSOwk76g/0?wx_fmt=jpeg]]></PicUrl>
<Url><![CDATA[https://mp.weixin.qq.com/s?__biz=MzI4MDEyNjQ4OA==&mid=100000080&idx=1&sn=7748c67603aea41fe764f00e03e8d751&chksm=6bbc74275ccbfd31e3be7cdf2f5a8321afab0fb14755c95e5316ee6f51689a247d4d3cd04cb9#rd]]></Url>
</item>
</Articles>
</xml>
ETO;
        return  $str;

    }

    public function smsApi(){
        $order_num = input('order_num');
        if($order_num == ""){
            return json(['code' => 1, 'data' => '订单号为空', 'msg' => '调用失败!!']);
        }
        $recharge = Db::name('wine_recharge')->where(array('order_num'=>$order_num))->find();
        if($recharge == ""){
            return json(['code' => 1, 'data' => '查不到改订单号的信息', 'msg' => '查询失败!!']);
        }
        $wineUser = Db::name('wine_user')->where(array('id'=>$recharge['wine_user_id']))->find();
        if($wineUser == ""){
            return json(['code' => 1, 'data' => '查不到改用户信息', 'msg' => '查询失败!!']);
        }
        //发送提醒短信
        $shop = DB::name('stores')->where('id','=',$recharge['stores_id'])->find();
        $sms = "【瓜分宝】 您好，您于" . date('Y-m-d H:i:s') . "在联盟商家:".$shop['storesname']."充值" . $recharge['amount'] . "元，存酒" . $recharge['wine_nums'] ."瓶。" ;
        $sms_res = OZSMsg::send($wineUser['mobile'],$sms);
        //日志信息
        $sms_log['stores_id'] = $recharge['stores_id'];
        $sms_log['operator_id'] = $recharge['wine_user_id'];
        $sms_log['send_time'] = time();
        $sms_log['amount'] = 1;
        $sms_log['content'] = $sms;
        $sms_log['phone'] = $wineUser['mobile'];
        $sms_log['sms_supplier'] = config('106msg.plat_name');
        if($sms_res['code']== 0){
            Db::name('sms_log')->insert($sms_log);
            return json(['code' => 0, 'data' => '', 'msg' => '短信已发送!!']);
        }else{
            $sms_log['status'] = 0;
            $sms_log['error_msg'] = $sms_res['remark'];
            Db::name('sms_log')->insert($sms_log);
            return json(['code' => 0, 'data' => '', 'msg' => '短信已发送!!']);
        }

    }

    public function qrcode()
    {
        try{
            $storeId = input('storeId/d');
            $referees = input('referees/d');
            if(!$storeId){
                throw Exception('参数storeId缺失');
            }
//            if(!$referees){
//                throw Exception('参数referees缺失');
//            }
            $store_res = Db::name('stores')->field('englishname')->where(array('id'=>$storeId))->find();
            if(!$store_res){
                throw Exception('未查到门店信息');
            }
//            $value = "https://www.whhykj.cn/hykjpt/drink?storeId=".$storeId."&referees=".$referees; //二维码内容
            $value = config('java_api_url')."/hykjpt/drink?storeId=".$storeId."&referees=".$referees; //二维码内容 测试
            $errorCorrectionLevel = 'L';//容错级别
            $matrixPointSize = 12;//生成图片大小
            $qrc = ROOT_PATH . 'public' . DS . 'uploads/qrcode/twocode/qrcode_'.$store_res['englishname'].'.png';

            //生成二维码图片
            QRcode::png($value, $qrc, $errorCorrectionLevel, $matrixPointSize, 2);
            $logo = ROOT_PATH . 'public' . DS . 'uploads/qrcode/logo.png';//准备好的logo图片
            $QR = $qrc;//已经生成的原始二维码图

            if ($logo !== FALSE) {
                $QR = ImageCreateFromString(file_get_contents($QR));
                $logo = ImageCreateFromString(file_get_contents($logo));

                $QR_width = imagesx($QR);//二维码图片宽度
                $QR_height = imagesy($QR);//二维码图片高度
                $logo_width = imagesx($logo);//logo图片宽度
                $logo_height = imagesy($logo);//logo图片高度

                $logo_qr_width = $QR_width / 5;
                $scale = $logo_width/$logo_qr_width;
                $logo_qr_height = $logo_height/$scale;
                $from_width = ($QR_width - $logo_qr_width) / 2;
                //重新组合图片并调整大小
                imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                    $logo_qr_height, $logo_width, $logo_height);
            }
            if(!$referees || $referees == '-1'){
                $out_qrcode = './uploads/qrcode/twocodelogo/finish_'.$store_res['englishname'].'.png';
                //输出图片
                imagepng($QR, $out_qrcode);
                Db::name('stores')->where(array('id'=>$storeId))->update(['talk_qrcode_img'=>substr($out_qrcode,1)]);
                $return_msg = '门店渠道二维码生成成功!!';
            }else{
                $out_qrcode = './uploads/qrcode/userCode/refereecode_'.$referees .'.png';
                //输出图片
                imagepng($QR, $out_qrcode);
                //二维码全路径
                $referee_qrcode_url = 'http://' . $_SERVER["HTTP_HOST"]  . substr($out_qrcode,1);
                $res = Db::name('wine_referee')->where(array('referee_id'=>$referees))->update(['referee_qrcode_url'=>$referee_qrcode_url,'referee_link_url'=>$value]);
                if(false === $res){
                    throw Exception('更新失败');
                }
                $return_msg = "数据库更新成功";

            }

            return json(['code' => 200, 'data' => substr($out_qrcode,1), 'msg' =>$return_msg ]);

        }catch(Exception $e){
            return json(['code'=>-1,'data'=>'','msg'=>$e->getMessage()]);
        }

    }

    /**
     * description: 拓客平台营销推广二维码
     * @return \think\response\Json
     * User: zjc
     * email: zengjc08@163.com
     */
    public function referee_qrcode()
    {

        $storeId = input('storeId');
        $referees = input('referees');
        $store_res = Db::name('stores')->field('englishname')->where(array('id'=>$storeId))->find();



        $value = "https://www.whhykj.cn/hykjpt/drink?storeId=".$storeId."&referees=".$referees; //二维码内容
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 12;//生成图片大小

        $qrc = './uploads/qrcode/twocode/qrcode_'.$store_res['englishname'].'.png';
        //生成二维码图片
        QRcode::png($value, $qrc, $errorCorrectionLevel, $matrixPointSize, 2);
        $logo = './uploads/qrcode/logo.png';//准备好的logo图片
        $QR = $qrc;//已经生成的原始二维码图





        if ($logo !== FALSE) {


            $QR = ImageCreateFromString(file_get_contents($QR));
            $logo = ImageCreateFromString(file_get_contents($logo));

            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度

            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                $logo_qr_height, $logo_width, $logo_height);
        }

        $out_qrcode = './uploads/qrcode/twocodelogo/finish_'.$store_res['englishname'].'.png';
        //输出图片
        imagepng($QR, $out_qrcode);
        // $res="./uploads/qrcode/twocodelogo/helloweixin11.png";
        Db::name('stores')->where(array('id'=>$storeId))->update(['talk_qrcode_img'=>$out_qrcode]);

        if($out_qrcode){
            return json(['code' => 0, 'data' => $out_qrcode, 'msg' => '门店渠道二维码生成成功!!']);
        }else{
            return json(['code' => 1, 'data' => '', 'msg' => '二维码生成失败!!']);
        }
    }
}

