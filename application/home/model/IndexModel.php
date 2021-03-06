<?php

namespace app\home\model;
use think\Model;
use think\Db;

class IndexModel extends Model
{

    /**
     * [getAllArticle 获取文章总数]
     * @author [史晓庆] [974196336@qq.com]
     */
	public function getByCount($map)
	{
		return Db::name('article')->where($map)->count();
	}


	
    /**
     * [getAllArticle 获取全部文章]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function getArticleByWhere($map, $nowpage, $limits)
    {
        return Db::name('article')->field('tp_article.*,name')
            ->join('tp_article_cate', 'tp_article.cate_id = tp_article_cate.id')
            ->where($map)
            ->page($nowpage, $limits)
            ->order('id desc')->select();
    }

}