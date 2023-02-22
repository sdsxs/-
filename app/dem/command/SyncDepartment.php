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

class SyncDepartment extends Command
{
    /**简历维护人过期 任务 每小时执行一次
     * commad   :   php think dem:maintenance
     */
    protected function configure()
    {
        $this->setName('同步部门架构')
            ->setDescription('同步部门架构');
    }

    protected function execute(Input $input, Output $output)
    {

        $url    =   "https://hrservice.dgg188.cn/dept/v1/get_dept.do";
        $headers =[
            "Content-Type: application/json",
            "Accept: application/json"
        ];
        $field   =   [
            "currentDeptId"=>"",
            "currentDeptName"=>"coo线"
        ];
        $options    =   json_encode($field,JSON_UNESCAPED_UNICODE);;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $options);
        $sResult = curl_exec($ch);
        if($sError=curl_error($ch)){
            die($sError);
        }
        curl_close($ch);
        $array  =   json_decode($sResult,true);
        //var_dump($array['data']);
        foreach ($array['data'] as $key => $val){

            if(!isset($val['currentDeptName'])){
                //var_dump($val);
                continue;
            }
            $con['company']    = $val['fifthLevelDeptName'];
            $con['name']    = $val['sixthLevelDeptName'];
            $con['center']    =   $val['seventhLevelDeptName'];
            $con['s_department']    =   $val['eighthLevelDeptName'];
            $con['s_group']    =   $val['currentDeptName'];
            $con['currentDeptId']    =   $val['currentDeptId'];

            $data['port']    =   $val['fifthLevelDeptName'];
            $data['framework']    =   $val['secondLevelDeptName'];
            $data['t_department']    =   $val['firstLevelDeptName'];
            $data['name']    =   $val['sixthLevelDeptName'];
            $data['company']    =   $val['fifthLevelDeptName'];
            $data['zone']    =   $val['thirdLevelDeptName'];
            $data['center']    =   $val['seventhLevelDeptName'];
            $data['s_department']    =   $val['eighthLevelDeptName'];
            $data['s_group']    =   $val['currentDeptName'];
            $data['currentDeptId']    =   $val['currentDeptId'];
            if(Db::name('user_department')->where($con)->value('id')){
                Db::name('user_department')->where($con)->update($data);
            }else{
                Db::name('user_department')->insert($data);
            }
        }
        //return $sResult;
    }



}
