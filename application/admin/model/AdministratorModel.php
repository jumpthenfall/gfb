<?php

namespace app\admin\model;
use think\Exception;
use think\Model;
use think\Db;

class AdministratorModel extends Base
{
    protected  $name = 'admin';

    /**
     * description: 获取管理员列表
     * @param $where
     * @param $Nowpage
     * @param $limits
     * @return false|\PDOStatement|string|\think\Collection
     * User: zjc
     * email: zengjc08@163.com
     */
    public function getAdminList($where, $Nowpage, $limits)
    {
        $res = $this->alias('a')
            ->field('a.id,username,portrait,loginnum,last_login_ip,real_name,a.status,groupid,title,
            FROM_UNIXTIME(last_login_time,"%Y-%m-%d %H:%i:%S") last_login_time')
            ->join('tp_auth_group g','a.groupid = g.id','left')
            ->where($where)->page($Nowpage, $limits)->order('id desc')->select();
        return $res;
    }

    /**
     * description: 通过条件查询数据量
     * @param $where
     * @return int|string
     * User: zjc
     * email: zengjc08@163.com
     */
    public function getCount($where)
    {
        $res = $this->alias('a')
            ->field('a.id')
            ->join('tp_auth_group g','a.groupid = g.id','left')
            ->count();
        return $res ? $res : 0;
    }
    /**
     * description: 添加管理员
     * @param $params
     * @return string
     * @throws Exception
     * User: zjc
     * email: zengjc08@163.com
     */
    public function addAdmin($params)
    {
        $res = $this->allowField(true)->save($params);
        if(!$res){
            throw new Exception('Failed to add Admin',10001);
        }
        return $this->getLastInsID();
    }

    /**
     * description: 更新管理员信息
     * @param $params
     * @return false|int
     * @throws Exception
     * User: zjc
     * email: zengjc08@163.com
     */
    public function updateAdmin($params)
    {
        $res = $this->allowField(true)->save($params,['id'=>$params['id']]);
        if(false === $res){
            throw new Exception('Failed to update Admin',10001);
        }
        return $res;
    }

    /**
     * description: 获取管理员详情
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * User: zjc
     * email: zengjc08@163.com
     */
    public function getAdminDetail($id)
    {
        $res =  $this->alias('a')
            ->field('a.id,username,portrait,loginnum,last_login_ip,real_name,a.status,groupid,title,
            FROM_UNIXTIME(last_login_time,"%Y-%m-%d %H:%i:%S") last_login_time')
            ->join('tp_auth_group g','a.groupid = g.id','left')
            ->where('a.id','=',$id)->find();
        if(!$res){
            throw new Exception('未查到该管理员信息',10002);
        }
        return $res;
    }

    /**
     * description: 删除管理员
     * @param $id
     * @throws Exception
     * User: zjc
     * email: zengjc08@163.com
     */
    public function deleteAdmin($id)
    {
        $res = $this->where('id','=',$id)->setField('is_delete',1);
        if(!$res){
            throw new Exception('Failed to delete Admin',10002);
        }
        return $res;
    }

    /**
     * description: 更改管理员状态
     * @param $id
     * User: zjc
     * email: zengjc08@163.com
     */
    public function changeAdminstatus($id)
    {
        $status = $this->where('id','=',$id)->value('status');
        $status = $status ? 0 : 1;
        $res = $this->where('id','=',$id)->setField('status',$status);
        if(!$res){
            throw new Exception('Failed to change status',10002);
        }
        return $res;
    }

    /**
     * description: 通过字段值获取管理员信息
     * @param $col 字段名
     * @param $val 值
     * @return array|false|\PDOStatement|string|Model
     * @throws Exception
     * User: zjc
     * email: zengjc08@163.com
     */
    public function getInfoByFV($col,$val)
    {
        $res = $this->where($col,'=',$val)->find();
        if(!$res){
            throw new Exception('管理员不存在',10002);
        }
        return $res;
    }

    /**
     * description: 修改管理员密码
     * @param $admin_id
     * @param $pwd string 密码
     * @return $this
     * User: zjc
     * email: zengjc08@163.com
     */
    public function updatePassword($admin_id,$pwd)
    {
        $noncestr = noncestr(mt_rand(8,12));
        $pd = md5($pwd . $noncestr);
        $res = $this->where('id','=',$admin_id)->update(['password'=>$pd,'salt'=>$noncestr]);
       if(false === $res){
           throw Exception('修改密码失败',10003);
       }
       return $res;
    }
    /**
     * 获取代理商列表
     */
    public function getAgentList()
    {
        $res = $this->alias('a')
            ->field('a.id,a.username,a.real_name')
            ->join('tp_auth_group g','a.groupid = g.id')
            ->where('g.title','like','%代理商%')
            ->select();
        return $res;
    }



}