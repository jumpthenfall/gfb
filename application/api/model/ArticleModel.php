<?php

namespace app\api\model;
use think\Model;
use think\Db;

class ArticleModel extends Model
{
    protected  $name = 'card';

    /**
     * @param $map
     * @return int|string
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
        return Db::name('article')->field('think_article.*,name')
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
        return Db::name('article')->field('tp_article.id,tp_article.title,tp_article.photo,tp_article.content,tp_article.writer,FROM_UNIXTIME(tp_article.update_time) as update_time,name')
            ->join('tp_article_cate', 'tp_article.cate_id = tp_article_cate.id')
            ->where($map)
            ->order('id desc')->select();
    }

    /**
     * 更新观看条数成功
     * @param $param
     */
    public function editViews($param)
    {
        try{

            $result = Db::name('article')->update($param, ['id' => $param['id']]);

            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                $result_res = Db::name('article')->field('id,views')->where(array('id' => $param['id']))->find();
                return ['code' => 1, 'data' => $result_res, 'msg' => '更新观看条数成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



}