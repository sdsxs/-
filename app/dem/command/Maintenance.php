<?php

namespace app\dem\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\admin\service\PoolService;
use app\admin\model\PersonnelModel;
use app\admin\model\PersonnelMessageModel;

class Maintenance extends Command
{
    /**简历维护人过期 任务 每小时执行一次
     * commad   :   php think dem:maintenance
     */
    protected function configure()
    {
        $this->setName('简历维护时间')
            ->setDescription('简历维护时间');
    }

    protected function execute(Input $input, Output $output)
    {
        $msg_date   =   date('Y-m-d',time());
        $PersonnelModel =   new PersonnelModel();
        $time   =   PoolService::getMaintenanceTime(3);//还有3天过期 就通知消息
        $list   =  $PersonnelModel
            ->alias('a')
            ->field('a.*')
            ->where('a.maintenance_time<'.'"'.$time.'" and a.p_status <>2 and a.uid <>0 and a.pid not in((SELECT pid from tp_personnel_queen where queen_date="'.$msg_date.'"))')
            ->limit(10)
            ->select();
        $datetime   =   date('Y-m-d H:i:s',time());
        $time   =   time();
        //echo $PersonnelModel->getLastSql();die;
        foreach ($list as $key =>$val){
            if($datetime > $val['maintenance_time']){
                $PersonnelModel->where(['pid'=>$val['pid']])->update(['uid'=>0,'p_status'=>1]);
            }else{
                $strtotime  =   strtotime($val['maintenance_time']);
                //$day    =   ($strtotime  -   $time)/(24*60*60);
                $diff = abs($strtotime - $time);

                $years = floor($diff / (365*60*60*24));
                $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
                $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
                $hours = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24) / (60*60));
                Db::startTrans();
                try{
                    PersonnelMessageModel::create([
                        'uid'   =>  $val['uid'],
                        'msg_date'   =>  $msg_date,
                        'message'   => '您有简历需要维护:'.$val['p_name'].'-'.$val['p_telphone'].' 剩余时间 '.$days.'天'.$hours.'小时',
                    ]);
                    Db::name('personnel_queen')->insert([
                        'pid'=>$val['pid'],
                        'queen_date'=>$msg_date
                    ]);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }

            }
            }
    }


}
