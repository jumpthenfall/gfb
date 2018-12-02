<?php

namespace app\admin\model;
use think\Model;

class CardRecordModel extends Model
{
    protected $name = 'card_record';
    
    // 开启自动写入时间戳字段
   // protected $autoWriteTimestamp = true;


    /**
     * [getAllMenu 获取全部生成卡号段记录]
     * @author [core] 
     */
    public function getAllCard()
    {
        return $this->order('id desc')->select();
    }


    /**
     * [insertCard 生成卡号段]
      * @author [core] 
     */
    public function insertCard($param)
    {
        try{
            $result = $this->save($param);
            if(false === $result){            
               // writelog(session('uid'),session('username'),'用户【'.session('username').'】添加菜单失败',2);
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                //writelog(session('uid'),session('username'),'用户【'.session('username').'】添加菜单成功',1);
                return ['code' => 1, 'data' => '', 'msg' => '卡生成成功，并已经添加好记录'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [editMenu 编辑菜单]
      * @author [core] 
     */
    public function editMenu($param)
    {
        try{
            $result =  $this->save($param, ['id' => $param['id']]);
            if(false === $result){
                writelog(session('uid'),session('username'),'用户【'.session('username').'】编辑菜单失败',2);
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'用户【'.session('username').'】编辑菜单成功',1);
                return ['code' => 1, 'data' => '', 'msg' => '编辑菜单成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getOneMenu 根据菜单id获取一条信息]
      * @author [core] 
     */
    public function getOneMenu($id)
    {
        return $this->where('id', $id)->find();
    }
	
	/**
     * [getOneMenu 根据菜单recordMark获取一条信息]
      * @author [core] 
     */
    public function getOneCard($recordMark)
    {
        return $this->where('recordMark', $recordMark)->find();
    }



    /**
     * [delMenu 删除菜单]
      * @author [core] 
     */
    public function delMenu($id)
    {
        try{
            $this->where('id', $id)->delete();
            writelog(session('uid'),session('username'),'用户【'.session('username').'】删除菜单成功',1);
            return ['code' => 1, 'data' => '', 'msg' => '删除菜单成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}