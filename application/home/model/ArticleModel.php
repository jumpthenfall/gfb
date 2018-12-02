<?php

namespace app\home\model;
use think\Model;
use think\Db;

class ArticleModel extends Model
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

    /**
     * [getAllArticle 获取全部文章]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function getArticleAll($map)
    {
        return Db::name('article')->field('tp_article.*,name')
            ->join('tp_article_cate', 'tp_article.cate_id = tp_article_cate.id')
            ->where($map)
            ->order('id desc')->select();
    }

    /**
     * [getAllArticle 获取全部文章]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function getAll($map)
    {
        return Db::name('article')->field('tp_article.id,tp_article.title,tp_article.photo,tp_article.content,tp_article.writer,FROM_UNIXTIME(tp_article.update_time) as update_time,tp_article.views,tp_article.comment_num,name')
            ->join('tp_article_cate', 'tp_article.cate_id = tp_article_cate.id')
            ->where($map)
            ->where(array('tp_article.status' => 1))
            ->order('id desc')->select();
    }

    /**
     * [getAllArticle 获取全部文章]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function getAllRed($map)
    {
        return Db::name('article')->field('tp_article.id,tp_article.title,tp_article.photo,tp_article.content,tp_article.writer,FROM_UNIXTIME(tp_article.update_time) as update_time,tp_article.views,tp_article.comment_num,name')
            ->join('tp_article_cate', 'tp_article.cate_id = tp_article_cate.id')
            ->where($map)
            ->where(array('tp_article.status' => 1))
            ->where(array('tp_article.views' => array('gt',10)))
            ->order('tp_article.views desc')->select();
    }

    /**
     * [getAllArticle 获取全部文章]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function getOne($map)
    {
        return Db::name('article')->field('tp_article.id,tp_article.comment_num,tp_article.title,tp_article.photo,tp_article.content,tp_article.writer,FROM_UNIXTIME(tp_article.update_time) as update_time,tp_article.views,name')
            ->join('tp_article_cate', 'tp_article.cate_id = tp_article_cate.id')
            ->where(array('tp_article.id' => $map['id']))
            ->find();
    }

    /**
     * [getAllArticle 获取全部文章]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function updateArticle($param)
    {
        try{
            $result = Db::table('tp_article')->where(array('id' => $param['id']))->update($param);
            if(false === $result){     
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '评论更新成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}