<?php

namespace app\home\model;
use think\Model;
use think\Db;

class ArticleCommentModel extends Model
{
    protected $name = 'article_comment';
    
    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * [insertCate 添加分类]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function insertComment($param)
    {
        try{
            $result = $this->save($param);
            if(false === $result){     
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '评论提交成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * [getAllArticle 获取全部文章]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function getAll($map)
    {
        return Db::name('article_comment')->field('tp_article_comment.id,tp_article_comment.content,tp_article_comment.writer,FROM_UNIXTIME(tp_article_comment.update_time) as update_time,tp_article_comment.views,headimgurl')
            ->join('tp_user', 'tp_article_comment.user_id = tp_user.id')
            ->where(array('tp_article_comment.status'=>$map['status'],'tp_article_comment.article_id'=>$map['article_id']))
            ->order('tp_article_comment.create_time desc')->select();
    }

}