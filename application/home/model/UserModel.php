<?php
namespace app\home\model;

use think\Model;
use think\Db;

class UserModel extends Model
{
    protected $insert = ['regtime','userip'];
    
    
    /**
     * 创建时间
     * @return bool|string
     */
    protected function setRegtimeAttr()
    {
        return time();
    }
    protected function setUseripAttr()
    {
    	
    	return $_SERVER["REMOTE_ADDR"];
    }

    public function getUserOpenid($temp)
    {
        return Db::name('user')->field('id,openid,headimgurl')->where($temp)->find();
    }

    public function insertUserInfo($temp){
        try{
            $result = Db::table('tp_user')->insert($temp);
           //$result =  $order->add($temp);
            
            return $result;
        }catch( PDOException $e){
            return 0;
        }
    }

    public function getId($data_getid){
        return Db::name('user')->field('id')->where($data_getid)->find();
    }


}