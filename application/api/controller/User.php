<?php

namespace app\api\controller;
use app\api\model\CardModel;
use app\api\model\UserModel;
use app\api\validate\UserValidate;
use think\Controller;
use think\Db;
use think\Exception;

/**
 * swagger: 用户中心
 */
class User extends Base
{
    /**
     * @SWG\Post(
     *     path="/api/user/update",
     *     tags={"user"},
     *     summary="更新用户信息",
     *     description="更新用户信息",
     * @SWG\Parameter(
     *     name="card_id",
     *     type="integer",
     *     required=true,
     *     in="formData",
     *     description="卡ID"
     * ),
     * @SWG\Parameter(
     *     name="mobile",
     *     type="string",
     *     required=true,
     *     in="formData",
     *     description="手机号"
     * ),
     * @SWG\Parameter(
     *     name="ali_account",
     *     type="string",
     *     required=true,
     *     in="formData",
     *     description="支付宝账号"
     * ),
     * @SWG\Parameter(
     *     name="ali_nickname",
     *     type="string",
     *     required=true,
     *     in="formData",
     *     description="支付宝用户名"
     * ),
     * @SWG\Parameter(
     *     name="phone_brand",
     *     type="string",
     *     required=true,
     *     in="formData",
     *     description="手机品牌"
     * ),
     * @SWG\Parameter(
     *     name="phone_version",
     *     type="string",
     *     required=true,
     *     in="formData",
     *     description="手机型号"
     * ),
     * @SWG\Response( response="200", description="success" )
     *
     * )
     */
    public function update()
    {
        try{
            $param = input('post.');
            $validate = new UserValidate();
            $res = $validate->scene('update')->check($param);
            if(!$res){
                throw Exception($validate->getError(),10001);
            }
            $card_model = new CardModel();
            $where['id'] = $param['card_id'];
            $card_info = $card_model->getCardInfo($where);
            if(!$card_info){
                throw Exception('卡不存在',40001);
            }
            $user_model = new UserModel();
            if(!$user_model->need_more_info($card_info['user_id'])){
                throw Exception('无法更改卡信息',40001);
            }
            $user_data['mobile'] = $param['mobile'];
            $user_data['ali_account'] = $param['ali_account'];
            $user_data['ali_nickname'] = $param['ali_nickname'];
            $user = $user_model->where($user_data)->find();
            if(!$user){
                $u_c = $card_model->where('id','=',$param['card_id'])->update(['phone_brand'=>$param['phone_brand'],'phone_version'=>$param['phone_version']]);
               $id =   $user_model->insertGetId($user_data);
            }else{
                $id = $user['id'];
            }
            $result = $card_model->where('id','=',$param['card_id'])->setField('user_id',$id);
            $this->setData($result);
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
        }
        return json($this->return_data);
    }
    /**
     * @SWG\Post(
     *     path="/api/user/user_info",
     *     tags={"user"},
     *     summary="用户信息",
     *     description="用户信息",
     * @SWG\Parameter(
     *     name="card_id",
     *     type="string",
     *     required=true,
     *     in="formData",
     *     description="卡ID"
     * ),
     * @SWG\Response( response="200", description="success" )
     *
     * )
     */
    public function user_info()
    {
        try{
            $card_id = input('card_id/d');
            if(!$card_id){
                throw Exception('参数缺失',10001);
            }
            $model = new CardModel();
            $info = $model -> getUserInfoByCardId($card_id);
            $this->setData($info);
        }catch (Exception $e){
            $this->setCode($e->getCode());
            $this->setMsg($e->getMessage());
        }
        return json($this->return_data);

    }

    /**
     * @SWG\Post(
     *     path="/api/user/feedback",
     *     tags={"user"},
     *     summary="更新用户信息",
     *     description="更新用户信息",
     * @SWG\Parameter(
     *     name="card_id",
     *     type="integer",
     *     required=true,
     *     in="formData",
     *     description="卡ID"
     * ),
     * @SWG\Parameter(
     *     name="content",
     *     type="string",
     *     required=true,
     *     in="formData",
     *     description="反馈内容"
     * ),
     * @SWG\Parameter(
     *     name="contact",
     *     type="string",
     *     required=false,
     *     in="formData",
     *     description="联系方式"
     * ),
     * @SWG\Parameter(
     *     name="score",
     *     type="integer",
     *     required=false,
     *     in="formData",
     *     description="用户评分"
     * ),
     * @SWG\Response( response="200", description="success" )
     *
     * )
     */
    public function feedback()
    {
        try{
            $param = input('post.');
            $validate = new UserValidate();
            $res = $validate->scene('feedback')->check($param);
            if(!$res){
                throw Exception($validate->getError(),10001);
            }
            $insert['content']= htmlspecialchars($param['content']);
            $insrt['card_id'] = $param['card_id'];
            $insrt['contact'] = $param['contact'] ?$param['contact'] :'';
            $insrt['score'] = $param['score'] ?$param['score'] :5;
            $res = Db::name('user_feedback')->insertGetId($insert);
            $this->setData([]);
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
        }
        return json($this->return_data);
    }
}