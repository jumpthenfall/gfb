<?php

namespace app\admin\controller;
use app\admin\model\AdministratorModel;
use app\admin\model\UserModel;
use app\admin\model\UserType;
use app\admin\model\StoresModel;
use app\admin\model\ActiveRuleModel;

use app\admin\validate\AdministratorValidate;
use think\Db;
use think\Exception;

class User extends Base
{

    /**
     * 管理员列表
     * @return mixed|\think\response\Json
     */
    public function index(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['username'] = ['like',"%" . $key . "%"];          
        }       
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('admin')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $user = new UserModel();
        $lists = $user->getUsersByWhere($map, $Nowpage, $limits);
        foreach($lists as $k=>$v)
        {
            $lists[$k]['last_login_time']=date('Y-m-d H:i:s',$v['last_login_time']);
        }    
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * 添加用户
     * @return mixed|\think\response\Json
     */
    public function userAdd()
    {
        if(request()->isAjax()){
            try{
                $validate = new AdministratorValidate();
                $param = input('post.');
                $v_res = $validate->scene('add')->check($param);
                if(!$v_res){
                    throw Exception($validate->getError(),10001);
                }
                $param['salt'] = noncestr(mt_rand(8,12));//干扰值
                $param['password'] = md5($param['password'].$param['salt']);//处理密码
                $model = new AdministratorModel();
                $res = $model->addAdmin($param);
                $accdata = array(
                    'uid'=> $res,
                    'group_id'=> $param['groupid'],
                );
                $group_access = Db::name('auth_group_access')->insert($accdata);
                return json(['code' => 1, 'data' => '', 'msg' => '添加成功']);
            }catch (Exception $e){
                return json(['code' => -2, 'data' => '', 'msg' => $e->getMessage()]);
            }

        }
        $role = new UserType();
        $this->assign('role',$role->getRole());
        return $this->fetch();
    }


    /**
     * 编辑用户
     * @return mixed|\think\response\Json
     */
    public function userEdit()
    {
        $user = new UserModel();

        if(request()->isAjax()){

            try{
                $param = input('post.');
                $validate = new AdministratorValidate();
                $v_res = $validate->check($param);
                if(!$v_res){
                    throw Exception($validate->getError(),10001);
                }
                if(isset($param['password']) && $param['password']){//传密码则修改
                    $param['salt'] = noncestr(mt_rand(8,11));
                    $param['password'] = md5($param['password'] . $param['salt']);
                }else{
                    unset($param['password']);
                }
                $param['update_time'] = time();
                $model = new AdministratorModel();
                $res = $model->updateAdmin($param);
                $group_access = Db::name('auth_group_access')->where('uid', $param['id'])->update(['group_id' => $param['groupid']]);
                return json(['code' => 1, 'data' => '', 'msg' => '添加成功']);
            }catch (Exception $e){
                return json(['code' => -2, 'data' => '', 'msg' => $e->getMessage()]);
            }
        }

        $id = input('param.id');
        $role = new UserType();
        $this->assign([
            'user' => $user->getOneUser($id),
            'role' => $role->getRole()
        ]);
        return $this->fetch();
    }


    /**
     * [UserDel 删除用户]
     * @return [type] [description]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function UserDel()
    {
        $id = input('param.id');
        $role = new UserModel();
        $flag = $role->delUser($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * [user_state 用户状态]
     * @return [type] [description]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function user_state()
    {
        $id = input('param.id');
        $status = Db::name('admin')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('admin')->where('id',$id)->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('admin')->where('id',$id)->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    
    } 

}