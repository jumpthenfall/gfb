<?php

namespace app\home\model;
use think\Model;
use think\Db;

class BaseModel extends Model
{
  
    /**
     * [getAllCate 获取文章分类]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function getAllCate()
    {
        return Db::name('article_cate')->field('id,name,status')->select();       
    }



}