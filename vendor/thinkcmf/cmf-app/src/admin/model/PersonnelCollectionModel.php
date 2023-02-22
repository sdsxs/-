<?php

namespace app\admin\model;

use think\Model;
use app\admin\model\PersonnelModel;

class PersonnelCollectionModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'personnel_collection';

    public function profile()
    {
        return $this->hasOne(PersonnelModel::class,'pid','cid');
    }

    public function getAll($uid,$para){

        $where = [];
        if(isset($para['p_name'])){
            $where['b.p_name'] = ['like','%'.$para['p_name'].'%'];
        }
        if(isset($para['p_telphone'])){
            $where['b.p_telphone'] = ['like','%'.$para['p_telphone'].'%'];
        }
        $where['a.uid'] = $uid;
        $where['a.c_status'] = 1;
        return $this->getModel()
            ->alias('a')
            ->field('a.*,b.*,c.c_status as coop_status')
            ->join('personnel b','a.pid=b.pid','left')
            ->join('personnel_cooperation c','a.pid=c.pid and c.uid=a.uid','left')
            ->where($where)->order('a.cid desc')->paginate(3,0,['query'=>$para]);
    }

    /*public function getP_statusAttr($value)
    {
        $status = [-1=>'删除',0=>'禁用',1=>'正常',2=>'待审核'];
        return $status[$value];
    }*/
}