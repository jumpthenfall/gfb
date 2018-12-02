<?php

namespace app\home\model;
use think\Model;
use think\Db;

class IdentificationModel extends Model
{



    /**
     * [getAllArticle 获取全部文章]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function getIdentification($map)
    {
        return Db::name('Identification')
            ->where($map)
			->find();
    }

    /**
     * 更新观看条数成功
     * @param $param
     */
    public function updateIdentification($param)
    {
        try{

            $result = Db::table('tp_identification')->update($param, ['id' => $param['id']]);

            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                
                return ['code' => 1, 'data' => '', 'msg' => '更新有效时间成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



}