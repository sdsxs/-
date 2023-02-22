<?php

namespace app\admin\model;

use think\Model;
use app\admin\model\PersonnelModel;
use think\facade\Db;

class PersonnelInviteModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'personnel_invite';

    public function profile()
    {
        return $this->hasOne(PersonnelModel::class,'pid','iid');
    }

    public static function getAll($uid,$para){

        $where = [];
        if(isset($para['p_name'])){
            $where['b.p_name'] = ['like','%'.$para['p_name'].'%'];
        }
        if(isset($para['p_telphone'])){
            $where['b.p_telphone'] = ['like','%'.$para['p_telphone'].'%'];
        }
        if(isset($para['i_status'])){
            $where['a.i_status'] = $para['i_status'];
        }
        if(isset($para['pid'])){
            $where['a.pid'] = $para['pid'];
        }
        $where['a.uid'] = $uid;
        //var_dump($where);die;
        //$where['a.c_status'] = 1;
        return DB::name('personnel_invite')
            ->alias('a')
            ->field('a.*,b.*,c.user_nickname,c.code')
            ->join('personnel b','a.pid=b.pid','left')
            ->join('user c','a.interviewer=c.id','left')
            ->where($where)->order('a.iid desc')->paginate(10,false,['query'=>$para]);
    }

    public function getByInterviewer($uid =false,$para=null,$pid =false){
        if($uid){
            $con['a.interviewer']   =  $uid;
        }
        if($pid){
            $con['a.pid']   =  $pid;
        }
        if(isset($para['i_status'])){
            $con['a.i_status']   =   $para['i_status'];
        }
        return $this->alias('a')
            ->join('personnel b','a.pid=b.pid','left')
            ->join('user_department c','a.departid = c.id','left')
            ->where($con)->paginate(10);
    }

    public function getByIid($iid){
        return $this->alias('a')->join('personnel b','a.pid=b.pid','left')->where(['a.iid'=>$iid])->find();
    }

    public function getI_statusAttr($value)
    {
        $status = [-1=>'删除',0=>'禁用',1=>'正常',2=>'待审核'];
        return $status[$value];
    }

    public function getForExcel($start_time,$end_time){
         $this->getModel()->where("create_time >= '$start_time' and create_time<='".$end_time.' 23:59:59'."'")->paginate(10);
        echo $this->getModel()->getLastSql();
    }
}