<?php
namespace app\home\controller;

use app\home\model\AuditModel;
use app\home\model\UserModel;
use think\Db;


class Audit extends Base
{
   /*
   //审核提交图片 index
   //作者：史晓庆; 邮箱：974196336@qq.com
   */
    public function index()
    {
       $map = [];
       $map['status'] = 1;
       $audit = new AuditModel();
       $list = $audit->getStoresList($map);

       $this->assign('storeslist',$list);

       return $this->fetch();
    }

}