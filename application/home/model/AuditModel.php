<?php
namespace app\home\model;

use think\Model;
use think\Db;

class AuditModel extends Model
{
	public function getStoresList($map)
	{
		return Db::name('stores')->where($map)->order('id DESC')->paginate(200);
	}

	public function getActiveRes($marke)
	{
		return Db::name('active')->field('id,username,stores_id,sentiment,pic')->where($marke)->find();
	}
    
}