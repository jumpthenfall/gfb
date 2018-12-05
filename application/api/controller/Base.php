<?php

namespace app\api\controller;
use think\cache\driver\Redis;
use think\Controller;
use JWT\JWT;
use think\Exception;
use think\Request;
use JWT\SignatureInvalidException;
use JWT\BeforeValidException;
use JWT\ExpiredException;
use \DomainException;
use \InvalidArgumentException;
use \UnexpectedValueException;
use uuid\UUID;



class Base extends Controller
{
    public  $jwt_base_playload = array(); //jwt 内容主体
    public  $jwt_base_token = ''; //jwt token 字符串
    public  $jwt_key = ''; //jwt 加密字符串
    public  $jwt_alg = ''; // jwt 加密算法
    public  $jwt_card_id = '';//卡id
    public  $jwt_iat = '';//会员ID
    public  $redis;
    public  $return_data = ['code'=>200,'result'=>null,'message'=>'成功'];
    public function _initialize()
    {
        $this->redis = new Redis();
        $this->jwt_key = config('jwt_token.jwt_key');
        $this->jwt_alg = config('jwt_token.jwt_alg');
        $config = load_config();

//        if(!$config){
//            $config = load_config();
//            cache('db_config_data',$config);
//        }

        $res = $this->checkJWTToken();
        if($res['code']!='200'){
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode($res));
        }

        $this->checkLoginPhone();//检测token生成时间与最后一直登录时间是否一致，不一致则认为该token无效
       
       config($config);
//       if(config('web_site_close') == 2 && $this->jwt_admin_id != 1){
//           if(!in_array(request()->ip(),explode('#',config('admin_allow_ip')))){
//               exit(json_encode( ['code'=>'21919','result'=>null,'message'=>'系统维护中，请稍后在访问 '])) ;
//           }
//       }
//        if($this->jwt_admin_id){
//            //访问权限验证
//            $this->hasPermission();
//        }
        $this->checkSite();
    }

    /**
     * description: 检测接收的token是否合法
     * @return array
     * User: zjc
     * email: zengjc08@163.com
     */
    public function checkJWTToken()
    {
        try{
            //判断当前模块是否需要登录验证
            $controller = strtolower(Request::instance()->controller());
            $action = strtolower(Request::instance()->action());
            $unauthority = config('unauthority_area');
            if(! in_array($controller.'/'.$action,$unauthority)){
                if(!isset($_SERVER['HTTP_AUTHORITY']) || empty($_SERVER['HTTP_AUTHORITY'])){
                    throw exception("请先登录") ;
                }
                $JWT = trim($_SERVER['HTTP_AUTHORITY']);
                //检测数据完整性
                JWT::checkTokenSegments($JWT);
                $jwt_res = JWT::decode($JWT,$this->jwt_key,[$this->jwt_alg]);
                $this->jwt_base_playload = json_decode(json_encode($jwt_res),true);
                $this->setParam();
                //刷新jwt_token
                $this->updateJWTToken();
            }else{
                if(isset($_SERVER['HTTP_AUTHORITY']) && empty($_SERVER['HTTP_AUTHORITY'])){
//                    $this->jwt_base_token =trim($_SERVER['HTTP_AUTHORITY']);
                }
            }

            return [ 'code' => '200','result'=>null,'message'=>'success'] ;
        }catch (Exception $e){
            return ['code'=>'21818','result'=>null,'message'=>$e->getMessage()] ;
        }catch (SignatureInvalidException $s){
            return ['code' =>'21818','result'=>null,'message'=>$s->getMessage()] ;
        }catch (BeforeValidException $s){
            return ['code' =>'21818','result'=>null,'message'=>$s->getMessage()] ;
        }catch (ExpiredException $s){
            return ['code' =>'21818','result'=>null,'message'=>$s->getMessage()] ;
        }catch (DomainException $s){
            return ['code' =>'21818','result'=>null,'message'=>$s->getMessage()] ;
        }catch (InvalidArgumentException $s){
            return ['code' =>'21818','result'=>null,'message'=>$s->getMessage()] ;
        }catch (UnexpectedValueException $s){
            return ['code' =>'21818','result'=>null,'message'=>$s->getMessage()] ;
        }


    }

    /**
     * description: 更新接口token
     * User: zjc
     * email: zengjc08@163.com
     */
    public function updateJWTToken()
    {
        $playload = $this->jwt_base_playload;
        $playload['iat'] = $_SERVER['REQUEST_TIME'];
        $playload['exp'] = $_SERVER['REQUEST_TIME'] + config('jwt_token.jwt_leeway');
        $this->jwt_base_token = JWT::encode($playload, $this->jwt_key,$this->jwt_alg);
    }

    /**
     * description: 生成token
     * @param array $arg
     * @return string
     * User: zjc
     * email: zengjc08@163.com
     */
    public function createToken(array $arg = array())
    {
        $playload =config('jwt_token.playload');
        $playload[ "exp"] = time() + config('jwt_token.jwt_leeway');
        $play = array_merge($playload,$arg);
       $this->jwt_base_token = JWT::encode($play,$this->jwt_key,$this->jwt_alg);
       return $this->jwt_base_token;

    }

    /**
     * description: 生成一个UUID
     * @return int
     * User: zjc
     * email: zengjc08@163.com
     */
    public function  UUID()
    {
        $U = new UUID(1);
        return $U->getUUID();
    }

    /**
     * description: 更新变量
     * User: zjc
     * email: zengjc08@163.com
     */
    public function setParam()
    {
        $this->jwt_card_id = isset($this->jwt_base_playload['id']) ? $this->jwt_base_playload['id'] : '';
        $this->jwt_iat = isset($this->jwt_base_playload['iat']) ? $this->jwt_base_playload['iat'] : '';
    }

    /**
     * description: 判断用户是否有操作权限
     * User: zjc
     * email: zengjc08@163.com
     */
    public function  hasPermission()
    {
        $auth = new \com\Auth();
        $module     = strtolower(request()->module());
        $controller = strtolower(request()->controller());
        $action     = strtolower(request()->action());
        $url        = $module."/".$controller."/".$action;
        //跳过检测以及主页权限
        if($this->jwt_admin_id !=1){
            if(!in_array($url, ['admin/index/index','admin/index/indexpage','admin/upload/upload','admin/index/uploadface'])){
                if(!$auth->check($url,$this->jwt_admin_id)){
                   $res =  ['code' =>'21818','result'=>null,'message'=>'抱歉，您没有操作权限'];
                    header('Content-Type:application/json; charset=utf-8');
                    exit(json_encode($res));
                }
            }
        }
    }
    /**
     * description: 检测是否是门店管理员登录系统
     * @throws Exception
     * User: zjc
     * email: zengjc08@163.com
     */
    public function check_stores_admin_login()
    {
        if(!$this->jwt_stores_id){
            throw new Exception('登录状态错误，请重新登录','21818');
        }
    }

    /**
     * 判断token生成时间与最后一次登录时间是否相同，如果不同则认为该token无效
     */
    public function checkLoginPhone()
    {
        if($this->jwt_iat != $this->redis->hGet('card_temp_data_'.$this->jwt_card_id,'login_time')){
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>21818,'result'=>null,'message'=>'该卡在其他设备登录，请重新登录']));
        }
    }

    /**
     * description: 设置返回状态码
     * @param $code
     * User: zjc
     * email: zengjc08@163.com
     */
    protected function setCode($code)
    {
        $this->return_data['code'] = $code;
    }
    /**
     * description: 设置返回数据主体
     * @param $data
     * User: zjc
     * email: zengjc08@163.com
     */
    protected function setData($data)
    {
        $this->return_data['result']['data']= $data;
    }

    /**
     * 设置token
     */
    protected  function setToken()
    {
        $this->return_data['result']['token']= $this->jwt_base_token;
    }
    /**
     * description: 设置返回消息
     * @param $error
     * User: zjc
     * email: zengjc08@163.com
     */
    protected function setMsg($error)
    {
        $this->return_data['message']=$error;
    }

    /**
     * description: 检测站点是否可用
     * User: zjc
     * email: zengjc08@163.com
     */
    protected function checkSite()
    {
        $controller = strtolower(Request::instance()->controller());
        $action = strtolower(Request::instance()->action());
        if(config('web_site_close') == 2){
            if($this->jwt_admin_id == 1 || ($controller == 'login' && input('username') == 'admin') || in_array(request()->ip(),explode('#',config('admin_allow_ip')))){

            }else{
                exit(json_encode( ['code'=>'21919','result'=>null,'message'=>'系统维护中，请稍后在访问 '])) ;
            }
        }
    }
    
    /**
     * description: 判断当前用户是不是化妆吧老板
     * @return bool
     * User: zjc
     * email: zengjc08@163.com
     */
    public function is_makeup_boss()
    {
        $where['id'] = $this->jwt_base_playload['uid'];
        $userModel = new UserModel();
        $userInfo = $userModel->userFind($where);//查询用户信息
        if(empty($userInfo)){
            throw Exception('未找到用户信息');
        }
        if(empty($userInfo['mobile'])){
            throw Exception('请先绑定手机号');
        }     
        $dressArr['mobile'] = $userInfo['mobile'];
        $dressArr['is_delete'] = 0;
        $dressArr['status'] = 1;
        $dressArr['identity'] = 1;
        $makeupModel = new RechargeModel();
        $dressInfo = $makeupModel->getDresserInfo($dressArr);//查询化妆师是否为老板
        if(empty($dressInfo)){
            return false;
        }
        $mk_model = new MakeupModel();
        $makeup = $mk_model->where(['status'=>1,'type'=>1,'is_delete'=>0])->find();
        if(!$makeup){
            return false;
        }
        return true;
    }

    /**
     * description: 发送下单提醒
     * @param $order_id
     * User: zjc
     * email: zengjc08@163.com
     */
    public function sendOrderNoticeToManager($order_id)
    {
        $o_model = new MKOrderModel();
        $order_info = $o_model->getOrderBaseInfo(['o.id'=>$order_id]);
        $d_model = new DresserModel();
        $makeup_id =$d_model-> getMakeupIdByUserId($order_info['user_id']);
        $mk_model = new MakeupModel();
        $mk_detail = $mk_model->getMakeupDetail($makeup_id);
        $wxmsg = new WXMsg();
        $ids = implode(',',config('mk_manager'));
        $res = $wxmsg->sendOrderNoticeToManager($ids, $mk_detail['storesname'] . '-' . $mk_detail['makeupname'] . '有新的采购订单!',$order_info['remark']);
//
    }
    
    public function sendOrderNoticeToDresser($user_id)
    {
        $u_model = new UserModel();
        $user_info = $u_model->userFind(['id'=>$user_id]);
        $open_id = $user_info['shop_openid'];
        $wxmsg = new WXMsg();
        $res = $wxmsg->sendOrderNoticeToManager($open_id, '你有一个心的饿了么订单', '你有一个心的饿了么订单');
    }

}