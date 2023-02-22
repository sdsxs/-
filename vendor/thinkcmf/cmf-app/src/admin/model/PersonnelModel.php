<?php
namespace app\admin\model;

use think\Model;
use app\admin\service\PoolService;

class PersonnelModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'personnel';

    protected $pk='pid';


    public function getAll($para = null,$uid,$type = 'paginate'){
        $para = array_filter($para,function  ($arr){
            if($arr === '' || $arr === null){
                return false;
            }
            return true;
        });
        $where = [];
        if(isset($para['p_name'])){
            $where[] = ['a.p_name','like',$para['p_name'].'%'];
        }
        if(isset($para['p_telphone'])){
            $where[] = ['a.p_telphone','like',$para['p_telphone'].'%'];
        }
       /* if($para['p_name']){
            $where = ['a.p_name','like',$para['p_name'].'%'];
        }*/

        /*$where[] = ['a.p_status','=','1'];*/
        $where[] = ['a.uid','=','0'];
        //$para = array_merge($para,['a.p_status'=>1]);
        unset($para['page']);
        if($type == 'paginate'){
            return $this->getModel()->alias('a')
                ->field('a.*,a.uid as p_uid,b.uid,b.c_status')
                ->join('personnel_cooperation b','a.pid=b.pid and b.uid='.$uid,'left')
                ->where($where)->order('a.pid desc')->paginate(10);
        }
        if($type == 'all'){
            return $this->getModel()->alias('a')
                ->field('a.*,a.uid as p_uid,b.uid,b.c_status')
                ->join('personnel_cooperation b','a.pid=b.pid and b.uid='.$uid,'left')
                ->where($where)->order('a.pid desc')->select();
        }
    }

    public function getP_statusAttr($value)
    {
        $status = [-1=>'删除',0=>'禁用',1=>'正常',2=>'待审核'];
        return $status[$value];
    }

    public function getByuid($uid,$para = null){
        if(!$uid){
            return false;
        }
        $para = array_filter($para);
        $where  =   [];
        if(isset($para['p_name'])){
            $where[]    =   ['a.p_name','like','%'.$para['p_name'].'%'];
        }
        if(isset($para['p_telphone'])){
            $where[]    =   ['a.p_telphone','like','%'.$para['p_telphone'].'%'];
        }
        //$where   =   array_merge(['uid'=>$uid],$where);
        //var_dump($where);die;
          return $this->getModel()->alias('a')
            ->field('a.*,h.user_nickname as blang,b.c_status,b.cid,b.uid,a.create_time as p_create_time,c.user_nickname,f.job as apply_job,g.*')
            ->join('personnel_cooperation b','a.pid=b.pid and b.c_status=1','left')
             ->join('user h','a.uid=h.id','left')
            ->join('user c','b.uid=c.id','left')
            ->join('personnel_interview f','a.p_telphone=f.phone','left')
            //->join('(select max(iid) pid,create_time,departid from tp_personnel_invite where situation=1 group by pid,create_time) d','a.pid=d.pid','right')
             ->join('user_department g','a.current_departid=g.id','left')
           // ->where("b.c_status=1")
            ->where(['a.uid'=>$uid])->order('a.pid desc')->where($where)->paginate(10);
        // $this->getModel()->getLastSql();
    }


    public function insertAuth( $field){
        if(!$field['p_telphone']){
            return ['code'=>500,'msg'=>'信息不正确！'];
        }
        /*if(!$field['filepath']){
            return ['code'=>500,'msg'=>'请上传简历！'];
        }*/
        $condition = ['p_telphone'=>$field['p_telphone']];
        if($list = $this->getModel()->where($condition)->find()){
            if(!$list['uid']){
                $condition['maintenance_time']  =  PoolService::getMaintenanceTime();
                //echo $field['uid'];die;
                $this->getModel()->update(['uid'=>$field['uid'],'p_status'=>0],['p_telphone'=>$field['p_telphone']]);
                return ['code'=>500,'msg'=>'人才库中绑定成功!'];
            }
            return ['code'=>500,'msg'=>'人才库中已经存在!可申请转介合作'];
        }
        $field['maintenance_time']  =  PoolService::getMaintenanceTime();
        $field['importid']  =  $field['uid'];
        if($id = $this->getModel()->insertGetId($field)){
            return ['code'=>200,'msg'=>'新增人才成功!'];
        }
        return ['code'=>500,'msg'=>'新增失败，请联系管理员!'];
    }
}