<?php
namespace app\admin\controller;

use app\admin\model\AdministratorModel;
use app\admin\model\CardModel;
use app\admin\model\CardRecordModel;
use app\admin\model\CardOriginModel;
use app\admin\model\DistributeLogModel;
use app\admin\model\UserWithdrawModel;
use think\Exception;
use think\Db;

class Card extends Base
{
    /**
     * 未激活卡列表
     * @return \think\response\Json
     */
    public function unregister()
    {
        $param['page'] = input('page',1) ;
        $param['limit'] = config('list_rows');
        $where['c.is_distribute'] = 0;
        $where['c.is_delete'] = 0;
//        $where['c.agent_id'] = ['NULL',''];
        $model= new CardOriginModel();
        $res = $model->getUnregisterCardList($where,$param['page'],$param['limit']);
        $count = $model->alias('c')->where($where)->count();
        //获取所有代理列表
        $model = new AdministratorModel();
        $agent_list = $model->getAgentList();//代理商列表
        $peak_list = config('daily_earning_peak');
        sort($peak_list);
        $this->assign('allpage',ceil($count/$param['limit']));
        $this->assign('Nowpage', $param['page']); //当前页
        $this->assign('total', $count); //总条数
        $this->assign('agent_list', $agent_list); //所有代理
        $this->assign('peak_list', $peak_list); //总页数
//        $this->assign('key', $param['key']);

        if(input('page'))
        {
            return json($res);
        }
        return $this->fetch();
        
    }

    /**
     *删除卡
     * @return \think\response\Json
     */
    public function del_origin_card()
    {
        try{
            $id = input('id');
            if(!$id){
                throw Exception('参数缺失',10001);
            }
            $res = Db::name('card_origin')->where('id','=',$id)->update(['is_delete'=>1,'operator_id'=>session('uid')]);
            if(!$res){
                throw Exception('删除失败',50001);
            }
            writelog(session('uid'),session('username'),'成功删除卡，id='.$id,1);
        }catch (Exception $e){
            $this->setCode($e->getCode());
            $this->setMsg($e->getMessage());
            writelog(session('uid'),session('username'),'删除卡失败，id='.$id,2);
        }
        return json($this->return_data);


    }
    /**
     * 更改申请状态
     * @return \think\response\Json
     */
    public function change_status()
    {
        try{
            $status = input('status');
            $statusRemark = $status == 1 ? "开启": "禁用";
            $id = input('id');
            if(!$status || !$id){
                throw Exception('参数缺失',1001);
            }
            if(!in_array($status,[1,2])){
                throw Exception('参数错误',30002);
            }
            $model = new CardModel();
            $res = $model->where('id','=',$id)->setField('status',$status);
            if(!$res){
                throw Exception('更新失败',50001);
            }
            writelog(session('uid'),session('username'),$statusRemark."卡成功，id=" . $id,1);
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
            writelog(session('uid'),session('username'),$statusRemark."卡失败，id=" . $id,2);
        }
        return json($this->return_data);

    }

    /**
     * 卡激活
     * @return \think\response\Json
     */
    public function card_register()
    {
        try{
            $id = input('id');
            if(!$id){
                throw Exception('参数缺失',1001);
            }
            $model = new CardModel();
            $status = $model->where('id','=',$id)->value('status');
            $time_length = $model->where('id','=',$id)->value('time_length');
            if(4!=$status){
                throw Exception('卡状态异常',50001);
            }
            $up['status'] = 1;
            $up['start_time'] = date('Y-m-d H:i:s');
            $up['end_time'] = date('Y-m-d H:i:s',strtotime("+ {$time_length}months"));
            $status = $model->where('id','=',$id)->update($up);
            if(!$status){
                throw exception('激活失败',50001);
            }
            writelog(session('uid'),session('username'),"激活卡成功，id=" . $id,1);
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
            writelog(session('uid'),session('username'),"激活卡失败，id=" . $id,2);
        }
        return json($this->return_data);

    }


    /**
     * 已分配卡列表信息
     * @return [type] [description]
     * @author [echo] ['']
     */
    public function card_list()
    {
        $param['key'] = input('key');
        $param['starttime'] = input('starttime');
        $param['endtime'] = input('endtime');
        $param['page'] = input('page',1) ;
        $param['limit'] = config('list_rows');
        $param['status'] = input('status');
        $param['agent_id'] =session('groupid')!=1 ? session('uid') : input('agent_id');
        $where=[];
        if($param['status']){
            $where['c.status'] = $param['status'];
        }
        if($param['key']) {
            $where['c.card_number'] = ['like','%'.$param['key'] .'%'];
        }
        if($param['agent_id']) {
            $where['c.agent_id'] = $param['agent_id'];
        }else{
            $where['c.agent_id'] = ['NOT NULL',''];
        }
        if($param['starttime']&&$param['endtime']){
            $where['c.add_time'] = ['between',[$param['starttime'],$param['endtime']]];
        }elseif($param['starttime']){
            $where['c.add_time'] = ['>',$param['starttime']];
        }elseif ($param['endtime']){
            $where['c.add_time'] = ['<',$param['endtime']];
        }
        $model= new CardModel();
        $res = $model->getCardList($where,$param['page'],$param['limit']);
        $count = $model->alias('c')->where($where)->count();
        $this->assign('allpage',ceil($count/$param['limit']));
        $this->assign('Nowpage', $param['page']); //当前页
        $this->assign('starttime', $param['starttime']); //
        $this->assign('endtime', $param['endtime']); //总页数
        $this->assign('key', $param['key']);
        $this->assign('status', $param['status']);
        $this->assign('agent_id', $param['agent_id']);
        $this->assign('agent_list', $this->getAgentList());
        $this->assign('groupid', session('groupid'));


        if(input('page'))
        {
            return json($res);
        }
        return $this->fetch();

    }

    /**
     * 导出已分（已售）发卡
     */
    public function export_data(){
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $fileName = 'guafenbao_card'.date('_YmdHis');
        // 输出Excel文件头，可把user.csv换成你要的文件名
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='."$fileName".".csv");
        header('Cache-Control: max-age=0');


        $param['key'] = input('key');
        $param['starttime'] = input('starttime');
        $param['endtime'] = input('endtime');
        $param['page'] = input('page',1) ;
        $param['limit'] = config('list_rows');
        $param['status'] = input('status');
        $param['agent_id'] =session('groupid')!=1 ? session('uid') : input('agent_id');
        $where=[];
        if($param['status']){
            $where['c.status'] = $param['status'];
        }
        if($param['key']) {
            $where['c.card_number'] = ['like','%'.$param['key'] .'%'];
        }
        if($param['agent_id']) {
            $where['c.agent_id'] = $param['agent_id'];
        }else{
            $where['c.agent_id'] = ['NOT NULL',''];
        }
        if($param['starttime']&&$param['endtime']){
            $where['c.add_time'] = ['between',[$param['starttime'],$param['endtime']]];
        }elseif($param['starttime']){
            $where['c.add_time'] = ['>',$param['starttime']];
        }elseif ($param['endtime']){
            $where['c.add_time'] = ['<',$param['endtime']];
        }
        $model= new CardModel();
        $model= new CardModel();
        $list = $model->getExportList($where);
        $stmts  = $list;
        $stmt = [];
        foreach ($stmts as $k => $v) {
            $stmt[$k]['card_number'] = $v['card_number'];
            $stmt[$k]['earning_peak'] = $v['earning_peak'];
            $stmt[$k]['start_time'] = $v['start_time'];
            $stmt[$k]['end_time'] = $v['end_time'];
            $stmt[$k]['total_money'] = $v['total_money'];
            $stmt[$k]['balance'] = $v['balance'];
            $stmt[$k]['withdraw_money'] = $v['withdraw_money'];
            $stmt[$k]['withdraw_num'] = $v['withdraw_num'];
            $stmt[$k]['total_num'] = $v['total_num'];
            $stmt[$k]['agent_name'] = $v['agent_name'];
            $stmt[$k]['statusRemark'] = $v['statusRemark'];
        }

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        
        // 输出Excel列名信息
        $head = array('卡号','每日最大收益', '激活时间', '过期时间', '总收益金额', '可用余额', '提现金额', '提现次数', '总分润次数','代理商','状态');
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

    /**
     * 导出未分发（未售）卡
     */
    public function export_unregister(){
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $fileName = 'guafenbao_unregister'.date('_YmdHis');
        // 输出Excel文件头，可把user.csv换成你要的文件名
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='."$fileName".".csv");
        header('Cache-Control: max-age=0');

        $where['c.status'] = 4;
        $where['c.is_delete'] = 0;
        $where['c.agent_id'] = ['NULL',''];
        $model= new CardModel();
        $list = $model->getUnregisterExportDate($where);
        $stmts  = $list;
        $stmt = [];
        foreach ($stmts as $k => $v) {
            $stmt[$k]['card_number'] = $v['card_number'];
            $stmt[$k]['password'] = $v['password'];
            $stmt[$k]['add_time'] = $v['add_time'];
            $stmt[$k]['statusRemark'] = $v['statusRemark'];
        }

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');

        // 输出Excel列名信息
        $head = array('卡号', '密码', '添加时间', '状态');
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

    /**
     * 将原始卡分配供应商
     */
    public function add_agent()
    {
        try{
            Db::startTrans();
            $agent_id = input('agent_id/d');
            $daily_earning = input('peak_value');
            if(!in_array($daily_earning,config('daily_earning_peak'))){
                throw Exception('参数错误',10001);
            }
            $id = input('id');
            if(!$agent_id || !$id){
                throw Exception('参数错误',10001);
            }
            $card_origin = Db::name('card_origin')->where('id','in',$id)->select();
            $time = date('Y-m-d H:i:s');
            foreach ($card_origin as $c){
                $insert=[];
                if($c['is_distribute'] || $c['is_delete']){
                    throw Exception("卡号：{$c['card_number']} 暂时无法分配",50001);
                }
                $insert = [
                    'card_number'=>$c['card_number'],
                    'password'=>$c['password'],
                    'agent_id'=>$agent_id,
                    'earning_peak'=>$daily_earning,
                    'add_time'=>$time,
                    'status'=>4
                ];
                $card_model = new CardModel();
                $c_id = $card_model->insertGetId($insert);
                if(!$c_id){
                    throw Exception('添加代理商失败',50001);
                }
                $a_id = Db::name('card_account')->insertGetId(['card_id'=>$c_id]);
                if(!$a_id){
                    throw Exception('添加代理商失败!',50001);
                }
            }
            $log_res = Db::name('card_distribute_log')->insertGetId(['agent_id'=>$agent_id,'operator_id'=>session('uid'),'card_num'=>count($card_origin),'distribute_time'=>$time]);
            $u_res = Db::name('card_origin')->where('id','in',$id)->update(['is_distribute'=>1,'operator_id'=>session('uid'),'distribute_time'=>$time]);
            if(!$u_res){
                throw Exception('添加代理商失败.',50001);
            }
            $this->setData($u_res);
            Db::commit();
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
            Db::rollback();
        }
        return json($this->return_data);

    }
    /**
     * 获取持卡代理列表（已分配卡）
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function  getAgentList()
    {
        if(session('groupid') != 1){
            return [];
        }
        $card_model = new CardModel();
        $list = $card_model->getAgentList();
        return $list;
    }

    /**
     * 卡分配日志
     * @return mixed|\think\response\Json
     */
    public function distribute_log()
    {
        $param['starttime'] = input('starttime');
        $param['endtime'] = input('endtime');
        $param['page'] = input('page',1) ;
        $param['limit'] = config('list_rows');
        $param['agent_id'] =session('groupid')!=1 ? session('uid') : input('agent_id');
        $where=[];
        if($param['agent_id']) {
            $where['d.agent_id'] = $param['agent_id'];
        }
        if($param['starttime']&&$param['endtime']){
            $where['d.distribute_time'] = ['between',[$param['starttime'],$param['endtime']]];
        }elseif($param['starttime']){
            $where['d.distribute_time'] = ['>',$param['starttime']];
        }elseif ($param['endtime']){
            $where['d.distribute_time'] = ['<',$param['endtime']];
        }
        $model= new DistributeLogModel();
        $res = $model->getLogList($where,$param['page'],$param['limit']);
        $count = $model->alias('d')->where($where)->count();
        $this->assign('allpage',ceil($count/$param['limit']));
        $this->assign('Nowpage', $param['page']); //当前页
        $this->assign('starttime', $param['starttime']); //
        $this->assign('endtime', $param['endtime']); //总页数
        $this->assign('agent_id', $param['agent_id']);
        $this->assign('agent_list', $this->getAgentList());
        $this->assign('groupid', session('groupid'));


        if(input('page'))
        {
            return json($res);
        }
        return $this->fetch();
    }

    /**导出卡分配日志
     *
     */
    public function export_distribute_log(){
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $fileName = 'guafenbao_distribute_log'.date('_YmdHis');
        // 输出Excel文件头，可把user.csv换成你要的文件名
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='."$fileName".".csv");
        header('Cache-Control: max-age=0');

        $param['starttime'] = input('starttime');
        $param['endtime'] = input('endtime');
        $param['page'] = input('page',1) ;
        $param['limit'] = config('list_rows');
        $param['agent_id'] =session('groupid')!=1 ? session('uid') : input('agent_id');
        $where=[];
        if($param['agent_id']) {
            $where['d.agent_id'] = $param['agent_id'];
        }
        if($param['starttime']&&$param['endtime']){
            $where['d.distribute_time'] = ['between',[$param['starttime'],$param['endtime']]];
        }elseif($param['starttime']){
            $where['d.distribute_time'] = ['>',$param['starttime']];
        }elseif ($param['endtime']){
            $where['d.distribute_time'] = ['<',$param['endtime']];
        }
        $model= new DistributeLogModel();
        $list = $model->getLogExportData($where);
        $stmts  = $list;
        $stmt = [];
        foreach ($stmts as $k => $v) {
            $stmt[$k]['agent_name'] = $v['agent_name'];
            $stmt[$k]['card_num'] = $v['card_num'];
            $stmt[$k]['distribute_time'] = $v['distribute_time'];
            $stmt[$k]['operator_name'] = $v['operator_name'];
        }

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');

        // 输出Excel列名信息
        $head = array('代理商', '卡数量', '分配数量', '操纵人');
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
	
	    /**
     * 卡生成列表信息
     * @return [type] [description]
     * @author [echo] ['']
     */
    public function card_generate()
    {
        //$nav = new \org\Leftnav;
        $card_record = new CardRecordModel();
        $card_res = $card_record->getAllCard();
        $admin_model = new AdministratorModel();
        $agent_list = $admin_model->getAgentList();
//		$agent_res = Db::name('admin')->field('id,username,real_name')->where(array('status'=>1,'is_delete'=>0))->select();
		
        //$arr = $nav::rule($admin_rule);
        $this->assign('card_res',$card_res);
		$this->assign('agent_list', $agent_list);//$this->getAgentList());
        return $this->fetch();

    }
	
	    /**
     * 卡生成列表信息
     * @return [type] [description]
     * @author [echo] ['']
     */
    public function add_card()
    {

            if(request()->isAjax()){
                try{
                    Db::startTrans();
                set_time_limit(0);
                $param = input('post.');

                $nums = $param['cardNums'];//生成卡的数量
                $startnums = $param['cardDescribe'];//起始卡号段
                    $time_length =(int) $param['time_length'] ?(int) $param['time_length'] : 3;
//                $startTime = $param['startTime'];//有效起始时间
//                $endTime = $param['endTime'];//有效结束时间
                $peak_value = $param['peak_value'];//每张卡每天峰值
                $agent_id = $param['agent_id'];//卡下发对应的代理商
                $recordMark = 'CARD'.strtotime(date('Y-m-d H:i:s'));//每一次下发卡生成的唯一识别码
//                if(!$nums||!$startnums||!$startTime||!$endTime||!$peak_value||!$agent_id){
//                    throw Exception('请完成所有选项',10001);
//                }
//                if($endTime<date('Y-m-d H:i:s') || $endTime<$startTime){
//                    throw Exception('有效结束时间异常',10001);
//                }
                if($nums>10000){
                    throw Exception('单次最多生成10000张卡',10001);
                }
                for($i = 0; $i<$nums; $i++){
                    $data['card_number'] = $startnums + $i;
                    $data['password'] = rand(1000000,9999999);
//                    $data['start_time'] = $startTime;
//                    $data['end_time'] = $endTime;
                    $data['time_length'] =$time_length;
                    $data['add_time'] = date('Y-m-d H:i:s');
                    $data['peak_value'] = $peak_value;
                    $data['agent_id'] = $agent_id;
                    $data['earning_peak'] = $peak_value;
                    $data['recordMark'] = $recordMark;
                    $data['status'] = 4;
                    $id = Db::name('card')->insertGetId($data);
                    if(!$id){
                        throw Exception('操作失败，请重试',10001);
                    }
                    $a_id = Db::name('card_account')->insertGetId(['card_id'=>$id]);
                    if(!$a_id){
                        throw Exception('操作失败，请重试',50001);
                    }
                }

                    $param['addTime'] = date('Y-m-d H:i:s');
                    $param['recordMark'] = $recordMark;
                    $param['status'] = 1;
                    $card_record = new CardRecordModel();
                    $flag = $card_record->insertCard($param);
                    Db::commit();
                    return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                }catch (Exception $e){
                    Db::rollback();
                    if((1062 == $e->getCode())||(10501 == $e->getCode())){
                        return json(['code'=>$e->getCode(),'msg'=>'卡号重复，请重新输入起始卡号','data'=>null]);
                    }
                    return json(['code'=>$e->getCode(),'msg'=>$e->getMessage(),'data'=>null]);
                }
            }

            return $this->fetch();




    }
	
	 /**
     * 导出卡生成记录
     */
    public function export_card_record(){
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $fileName = 'guafenbao_card'.date('_YmdHis');
        // 输出Excel文件头，可把user.csv换成你要的文件名
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='."$fileName".".csv");
        header('Cache-Control: max-age=0');
		

        $param['marke'] = input('marke');
		
		$card_record = new CardRecordModel();
		$card_record_res = $card_record->getOneCard($param['marke']);
		$agent_name = Db::name('admin')->field('real_name')->where(array('id'=>$card_record_res['agent_id']))->find();
		
      
        //$param['agent_id'] = $card_record_res['agent_id'];
//        $where['c.status'] = 1;
        if($param['marke']) {
            $where['c.recordMark'] = $param['marke'];
        }
        
       
        $model= new CardModel();
        $list = $model->getExportListRecord($where);
        $stmts  = $list;
        $stmt = [];
		$j = 0;
        foreach ($stmts as $k => $v) {
			$stmt[$k]['id'] = ++$j;
			$stmt[$k]['recordMark'] = $v['recordMark'];
            $stmt[$k]['card_number'] = $v['card_number'];
			$stmt[$k]['password'] = $v['password'];
			$stmt[$k]['earning_peak'] = $v['earning_peak'];
            $stmt[$k]['start_time'] = $v['start_time'];
            $stmt[$k]['end_time'] = $v['end_time'];
            $stmt[$k]['add_time'] = $v['add_time'];
            $stmt[$k]['agent_name'] = $agent_name['real_name'];
            $stmt[$k]['status'] = $v['status'];
        }

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        
        // 输出Excel列名信息
        $head = array('序号','唯一标识','卡号','卡密','每天收益峰值', '有效起始时间', '有效过期时间', '卡生成时间','代理商','状态');
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