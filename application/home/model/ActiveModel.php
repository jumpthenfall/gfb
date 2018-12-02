<?php
namespace app\home\model;

use think\Model;
use think\Db;

class ActiveModel extends Model
{
	public function getActiveList($map)
	{
		return Db::name('active')->where($map)->order('sentiment DESC')->paginate(200);
	}

	public function getActiveRes($marke)
	{
		return Db::name('active')->field('id,username,stores_id,sentiment,pic')->where($marke)->find();
	}
    
}