<?php

namespace app\admin\model;

use think\Model;
use app\admin\model\PersonnelModel;
use think\facade\Db;

class PersonnelInterviewModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'personnel_interview';

    public function profile()
    {
        return $this->hasOne(PersonnelModel::class,'pid','id');
    }


    public function getForExcel($start_time,$end_time){
        $mode = $this->getModel();
        $where = '';
        if($start_time){
            $where  =   "create_time >= '$start_time' and create_time<='".$end_time.' 23:59:59'."'";
        }
        return  $mode->where($where)->paginate(3);
    }
}