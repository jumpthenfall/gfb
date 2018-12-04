<?php
error_reporting(0);
set_time_limit(0);
ini_set('memory_limit', '1024M');
// 定义应用目录
define('APP_PATH', __DIR__ . '/../');
// 定义上传目录
define('UPLOAD_PATH', __DIR__ . '/../../public');
// 定义应用缓存目录
define('RUNTIME_PATH', __DIR__ . '/../../runtime/');
// 开启调试模式
define('APP_DEBUG', true);
// 加载框架引导文件
require __DIR__ . '/../../thinkphp/start.php';
use app\api\model\AdsModel;
use app\api\model\CardAccountModel;
use app\api\model\CardModel;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

class Profit
{
    public $list_key = '';
    public $redis ;
    public $cardAccount_model;

    public function __construct()
    {
        $this->list_key = 'card_profit_list';//内容队列
        $this->redis = new Redis();//连接redis数据库
        $this->cardAccount_model = new CardAccountModel();//卡账户model
    }

    public function handle_card_profit_list(){
//        $redis = new Redis();
        try{
//            $list_key = 'card_profit_list';
            $redis = new Redis();
            $data = $redis->rpop($this->list_key);
            if(!$data){
                return ;
            }
            $item = unserialize($data);
//            $account_model = new CardAccountModel();
            $account_info = $this->cardAccount_model->getCardAccountInfoByCardId($item['id']);
            if(!$account_info){
                throw new Exception('卡信息错误',50002);
            }
            $up['total_money'] = bcadd($item['money'],$account_info['total_money'],4);
            $up['balance'] = bcadd($item['money'],$account_info['balance'],4);
            $up['total_num'] = $account_info['total_num'] + 1;
            $up_res = $this->cardAccount_model->where('card_id','=',$item['id'])->update($up);
            if(!$up_res){
                $this->redis->lPush($this->list_key,$data);
                throw new Exception('更新失败',50002);
            }
//            $redis = new Redis();
            if($up_res == 1){
                if($this->redis->has('card_account_money_'.$item['id']) &&$this->redis->has('card_account_number_'.$item['id'])){
                    $this->redis->set('card_account_money_'.$item['id'],bcadd($this->redis->get('card_account_money_'.$item['id']),$item['money'],4));
                    $this->redis->inc('card_account_number_'.$item['id']);
                }else{
                    $this->redis->set('card_account_money_'.$item['id'],$item['money']);
                    $this->redis->set('card_account_number_'.$item['id'],1);
                }
            }
        }catch (Exception $e){
            echo $e->getMessage();

        }


    }
}


$shell = new Profit();
$redis = new Redis();
while (true){
    if(!$redis->lLen($shell->list_key)){
        echo date('Y-m-d H:i:s') .PHP_EOL;
        sleep(1) ;
    }
    $shell->handle_card_profit_list();

}
