<?php
namespace app\admin\model;

use think\Model;
use think\Db;

class UserModel extends Model
{
    protected $name = 'admin';

    /**
     * 根据搜索条件获取管理员列表信息
     */
    public function getUsersByWhere($map, $Nowpage, $limits)
    {
        return $this->field('tp_admin.*,tp_auth_group.title')
                    ->join('tp_auth_group', 'tp_admin.groupid = tp_auth_group.id')
                    ->where($map)
                    ->page($Nowpage, $limits)
                    ->order('tp_auth_group.id desc')
                    ->select();
    }

    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllUsers($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入管理员信息
     * @param $param
     */
    public function insertUser($param)
    {
        try{
            $result = $this->validate('UserValidate')->allowField(true)->save($param);
            if(false === $result){            
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'用户【'.$param['username'].'】添加成功',1);
                return ['code' => 1, 'data' => '', 'msg' => '添加用户成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑管理员信息
     * @param $param
     */
    public function editUser($param)
    {
        try{
            $result =  $this->validate('UserValidate')->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){            
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'用户【'.$param['username'].'】编辑成功',1);
                return ['code' => 1, 'data' => '', 'msg' => '编辑用户成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    /**
     * 根据管理员id获取角色信息
     * @param $id
     */
    public function getOneUser($id)
    {
        return $this->where('id', $id)->find();
    }


    /**
     * 删除管理员
     * @param $id
     */
    public function delUser($id)
    {
        try{

            $this->where('id', $id)->delete();
            Db::name('auth_group_access')->where('uid', $id)->delete();
            writelog(session('uid'),session('username'),'用户【'.session('username').'】删除管理员成功(ID='.$id.')',1);
            return ['code' => 1, 'data' => '', 'msg' => '删除用户成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

     /**
     * 根据搜索条件获取用户列表信息
     */
    public function getAverageuser($map, $Nowpage, $limits)
    {
        return Db::name('user_account')->field('tp_user.*,tp_user_account.balance,tp_user_account.branch,tp_stores.storesname')
                    ->join('tp_user', 'tp_user.id = tp_user_account.user_id')
                    ->join('tp_stores', 'tp_stores.id = tp_user_account.laststores_id')
                    ->where($map)
                    ->page($Nowpage, $limits)
                    ->order('tp_user_account.id desc')
                    ->select();
    }
    /**
     * 根据搜索条件获取用户列表信息
     */
    public function getAverageuserSelect($map, $Nowpage, $limits)
    {
        return Db::name('user_account')
            -> field('tp_user.id,tp_user.point,tp_user.username,tp_user.nickname,
            tp_user.mobile,tp_user.status,tp_user.headimgurl,FROM_UNIXTIME(tp_user.regtime,"%Y-%m-%d %H:%i:%S") as regtime,
            tp_user_account.balance,tp_user_account.branch,tp_stores.storesname')
            ->join('tp_user', 'tp_user.id = tp_user_account.user_id')
            ->join('tp_stores', 'tp_stores.id = tp_user_account.laststores_id')
            ->where($map)
            ->page($Nowpage, $limits)
            ->order('tp_user_account.id desc')
            ->select();
    }
    /**
     * 根据搜索条件获取用户信息
     */
    public function getAverageuserOne($map)
    {
        return Db::name('user')->field('tp_user.*,tp_user_account.balance,tp_user_account.branch')
                    ->join('tp_user_account', 'tp_user_account.user_id = tp_user.id')
                    ->where(array('tp_user.id' => $map))
                    ->find();
    }

     /**
     * 编辑管理员信息
     * @param $param
     */
    public function insertRecharge($param,$tem,$balancenew)
    {
        try{
            $result =  Db::table('tp_recharge')->insert($param);
            if(false === $result){            
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                $sel = Db::table('tp_user_account')->where(array('user_id' => $tem))->find();
                $map = [];
                $map['rechargemoney'] = $sel['rechargemoney'] + $balancenew;
                $map['balance']       = $sel['balance'] + $balancenew;

                $resulttc =  Db::table('tp_user_account')->where(array('id' => $sel['id']))->update($map);
                return ['code' => 1, 'data' => '', 'msg' => '用户充值成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /*
    *
    *
    */
    public function getUserCount($map)
    {
        return Db::name('user')->where($map)
                    ->count();     
    }
}