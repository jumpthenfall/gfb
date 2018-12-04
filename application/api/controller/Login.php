<?php

namespace app\api\controller;
use app\api\model\CardModel;

use app\api\model\UserModel;
use app\api\validate\LoginValidate;
use think\Controller;
use think\Db;
use think\Exception;

/**
 * swagger: 登录接口
 */
class Login extends Base
{

    /**
     * @SWG\Post(
     *     path="/api/login/login",
     *     tags={"login"},
     *     summary="卡登录接口",
     *     description="卡登录接口",
     * @SWG\Parameter(
     *     name="card_number",
     *     type="string",
     *     required=true,
     *     in="formData",
     *     description="卡号"
     * ),
     * @SWG\Parameter(
     *     name="password",
     *     type="string",
     *     required=true,
     *     in="formData",
     *     description="登录密码"
     * ),
     * @SWG\Parameter(
     *     name="phone_mac",
     *     type="string",
     *     required=true,
     *     in="formData",
     *     description="手机设备号"
     * ),
     * @SWG\Response( response="200", description="success" )
     *
     * )
     */
    public function login()
    {
        try{
            $param['card_number'] = input('card_number');
            $param['password'] = input('password');
            $param['phone_mac'] = input('phone_mac');
            $validate = new LoginValidate();
            $res = $validate->scene('login')->check($param);
            if(!$res){
                throw Exception($validate->getError(),10001);
            }
            $card_model = new CardModel();
            $where['card_number'] = $param['card_number'];
            $where['is_delete'] = 0;
            $card_info = $card_model->getCardInfo($where);
            if(!$card_info){
                throw Exception('卡号或密码错误',40001);
            }else{
                if($card_info['password'] != $param['password']){
                    throw Exception('卡号或密码错误',40001);
                }
                if(2 == $card_info['status']){
                    throw Exception('该卡被禁用，请联系管理员',40001);
                }
                if(3 == $card_info['status']){
                    throw Exception('该卡已过期',40001);
                }
                if(4 == $card_info['status']){//未激活卡登录激活开始算有效期
                    $up['end_time'] = date('Y-m-d H:i:s',strtotime("+ {$card_info['time_length']}month"));
                    $up['start_time'] = date('Y-m-d H:i:s');
                    $up['status'] = 1;
                    $up_res = $card_model->where('id','=',$card_info['id'])->update($up);
                    if(!$up_res){
                        throw Exception('该卡未激活，请联系管理员',40001);
                    }
                    $card_info['start_time'] = $up['start_time'];
                    $card_info['end_time'] = $up['end_time'];
                }
                if(!$card_info['phone_mac']){
                    $card_model->where('id','=',$card_info['id'])->update(['phone_mac'=>$param['phone_mac']]);
                }elseif ($param['phone_mac'] != $card_info['phone_mac']){
//                    throw Exception('不支持多设备登录',40001);
                }
                if(!$card_info['user_id']){
                    $card_info['need_info']=1;
                }else{
                    $user_model = new UserModel();
                    $card_info['need_info'] = $user_model->need_more_info($card_info['user_id']);
                }

            }
            unset($card_info['password']);
            unset($card_info['time_length']);
            $this->createToken(['id'=>$card_info['id']]);
            $this->setData($card_info);
            $this->setToken();
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
        }
        return json($this->return_data);
    }

    /**
     *
     * 新卡登记
     * @param $param
     */
    protected function card_register($param)
    {
        $info = Db::name('card_origin')->where(['card_number'=>$param['card_number'],'password'=>$param['password'],'status'=>0])->find();
        if(!$info){
            throw Exception('卡号或密码错误',40001);
        }
        $param['start_time'] = date('Y-m-d H:i:s');
        $param['end_time'] = date('Y-m-d H:i:s',strtotime('+ 3months'));
        $id = Db::name('card')->insertGetId($param);
        if(!$id){
            throw Exception('注册失败',50001);
        }
        $a_id = Db::name('card_account')->insert(['card_id'=>$id]);
        if(!$a_id){
            throw Exception('注册失败',50001);
        }
        Db::name('card_origin')->where('id','=',$info['id'])->setField('status',1);
        $param['id'] = $id;
        $param['need_info'] = 1;
        return $param;
    }

}