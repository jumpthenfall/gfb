<?php
namespace app\admin\controller;

use app\admin\model\CardConfig;
use app\admin\model\CardModel;
use app\admin\model\UserWithdrawModel;
use think\Exception;
use think\Db;

class BonusPool extends Base
{
    /**
     * 资金池
     * @return mixed|\think\response\Json
     */
    public function index()
    {
        $param['page'] = input('page',1) ;
        $param['limit'] = config('list_rows');
        $where['status'] = 1;
        $where['is_delete'] = 0;
        $model= new CardConfig();
        $config = $model->getCardConfigList($where);
        $withdraw_model = new UserWithdrawModel();
        $count = $withdraw_model->getWithdrawCountData();
        $config = formatCardConfig($config);
        $config = obj2array($config);
        $config['withdraw_num'] = $count['withdraw_num'];
        $config['withdraw_money'] = $count['withdraw_money'];
        $this->assign('param',$config);
        return $this->fetch();
    }

}