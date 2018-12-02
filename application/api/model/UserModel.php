<?php
namespace app\api\model;

use think\Model;

class UserModel extends Model
{
    protected $name = 'user';
    /**
     * 判断该用户是否需要补全信息
     * @param $user_id
     * @return bool
     */
    public function  need_more_info($user_id)
    {
        $res = $this->field('ali_account')->where('id','=',$user_id)->find();
        return $res['ali_account'] ?  0:1 ;
    }
}