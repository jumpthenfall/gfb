<?php
namespace app\admin\controller;

use app\admin\model\UserWithdrawModel;
use think\Exception;

class Withdraw extends Base
{
    /**
     * 待审核列表
     * @return \think\response\Json
     */
    public function before_confirm(){
        $param['key'] = input('key');
        $param['starttime'] = input('starttime');
        $param['endtime'] = input('endtime');
        $param['page'] = input('page',1) ;
        $param['limit'] = config('list_rows');
        $where['uw.status'] = 1;
        if($param['key']) {
            $where['account|account_name'] = ['like','%'.$param['key'] .'%'];
        }
        if($param['starttime']&&$param['endtime']){
            $where['add_time'] = ['between',[$param['starttime'],$param['endtime']]];
        }elseif($param['starttime']){
            $where['add_time'] = ['>',$param['starttime']];
        }elseif ($param['endtime']){
            $where['add_time'] = ['<',$param['endtime']];
        }
        $model= new UserWithdrawModel();
        $res = $model->getWithdrawList($where,$param['page'],$param['limit']);
        $count = $model->alias('uw')->where($where)->count();
        $this->assign('allpage',ceil($count/$param['limit']));
        $this->assign('Nowpage', $param['page']); //当前页
        $this->assign('starttime', $param['starttime']); // 
        $this->assign('endtime', $param['endtime']); //总页数
        $this->assign('key', $param['key']);

        if(input('page'))
        {
            return json($res);
        }
        return $this->fetch();
        
    }

    /**
     * 更改申请状态
     * @return \think\response\Json
     */
    public function change_status()
    {
        try{
            $status = input('status');
            $id = input('id');
            if(!$status || !$id){
                throw Exception('参数缺失',1001);
            };
            $model = new UserWithdrawModel();
            $up['operator_id'] = session('uid');
            $up['status'] = $status;
            $up['finished_time'] = date('Y-m-d H:i:s');
            $res = $model->where('id','=',$id)->update($up);
            if(!$res){
                throw Exception('更新失败',50001);
            }
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
        }
        return json($this->return_data);

    }


    /**
     * [edit_ad 已完成申请]
     * @return [type] [description]
     * @author [echo] ['']
     */
    public function confirmed()
    {
        $param['key'] = input('key');
        $param['starttime'] = input('starttime');
        $param['endtime'] = input('endtime');
        $param['page'] = input('page',1) ;
        $param['limit'] = config('list_rows');
        $where['uw.status'] = ['>',1];
        if($param['key']) {
            $where['account|account_name'] = ['like','%'.$param['key'] .'%'];
        }
        if($param['starttime']&&$param['endtime']){
            $where['add_time'] = ['between',[$param['starttime'],$param['endtime']]];
        }elseif($param['starttime']){
            $where['add_time'] = ['>',$param['starttime']];
        }elseif ($param['endtime']){
            $where['add_time'] = ['<',$param['endtime']];
        }
        $model= new UserWithdrawModel();
        $res = $model->getWithdrawList($where,$param['page'],$param['limit']);
        $count = $model->alias('uw')->where($where)->count();
        $this->assign('allpage',ceil($count/$param['limit']));
        $this->assign('Nowpage', $param['page']); //当前页
        $this->assign('starttime', $param['starttime']); //
        $this->assign('endtime', $param['endtime']); //总页数
        $this->assign('key', $param['key']);

        if(input('page'))
        {
            return json($res);
        }
        return $this->fetch();

    }


    public function export_data(){
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $fileName = 'guafenbao_withdraw'.date('_YmdHis');
        // 输出Excel文件头，可把user.csv换成你要的文件名
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='."$fileName".".csv");
        header('Cache-Control: max-age=0');


        $param['key'] = input('key');
        $param['starttime'] = input('starttime');
        $param['endtime'] = input('endtime');
        $param['status'] = input('status');
        $where['uw.status'] = $param['status'] ? $param['status'] : ['<>',1];
        if($param['key']) {
            $where['account|account_name'] = ['like','%'.$param['key'] .'%'];
        }
        if($param['starttime']&&$param['endtime']){
            $where['add_time'] = ['between',[$param['starttime'],$param['endtime']]];
        }elseif($param['starttime']){
            $where['add_time'] = ['>',$param['starttime']];
        }elseif ($param['endtime']){
            $where['add_time'] = ['<',$param['endtime']];
        }
        $model= new UserWithdrawModel();
        $list = $model->getExportList($where);
        $stmts  = $list;
        $stmt = [];
        foreach ($stmts as $k => $v) {
            $stmt[$k]['mobile'] = $v['mobile'];
            $stmt[$k]['account'] = $v['account'];
            $stmt[$k]['account_name'] = $v['account_name'];
            $stmt[$k]['money'] = $v['money'];
            $stmt[$k]['add_time'] = $v['add_time'];
            $stmt[$k]['finished_time'] = $v['finished_time'];
            $stmt[$k]['username'] = $v['username'];
            $stmt[$k]['statusRemark'] = $v['statusRemark'];
        }

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        
        // 输出Excel列名信息
        $head = array('手机号', '收款账号', '收款姓名', '提现金额', '申请时间', '完成时间', '操作人', '状态');
        foreach ($head as $i => $v) {
            $encode = mb_detect_encoding($v, array("ASCII","UTF-8","GB2312","GBK","BIG5"));
            // CSV的Excel支持GBK编码，一定要转换，否则乱码
            $head[$i] = iconv($encode, "GBK//IGNORE", $v);
        }
        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $head);            
        // 计数器
        $cnt = 0;
        // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 50;    
        // 逐行取出数据，不浪费内存
        $count = count($stmt);
        for($t=0;$t<$count;$t++) {            
            $cnt ++;
            if ($limit == $cnt) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $cnt = 0;
            }
            $row = $stmt[$t];
            foreach ($row as $i => $v) {
                $encodes = mb_detect_encoding($v, array("ASCII","UTF-8","GB2312","GBK","BIG5"));
                // CSV的Excel支持GBK编码，一定要转换，否则乱码
                $row[$i] = iconv($encodes, "GBK//IGNORE", $v);
            }
            fputcsv($fp, $row);       
        }    
    }



}