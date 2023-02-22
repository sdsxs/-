<?php

namespace app\dem\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;


class Sydata extends Command
{
    /**简历维护人过期 任务 每小时执行一次
     * commad   :   php think dem:maintenance
     */
    protected function configure()
    {
        $this->setName('同步部门架构')
            ->setDescription('同步部门架构');
    }

    protected function execute(Input $input, Output $output){
        $data = Db::name('personnel_interview_copy1')
            ->alias("a")
            ->field('a.*')
            ->join('personnel_interview b','a.id=b.id','left')
            ->where("b.name='李爽'")
            ->select()->toArray();
        foreach ($data as $key => $val){
            Db::name('personnel_interview')->where(['id'=>$val['id']])->update($val);
            unset($val);
        }
    }



}
