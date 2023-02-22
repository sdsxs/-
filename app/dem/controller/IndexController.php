<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Released under the MIT License.
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------

namespace app\dem\controller;

use cmf\controller\HomeBaseController;
use function MongoDB\BSON\fromJSON;
use think\facade\Config;
use think\facade\Db;
use DySDKLite\Sms;
use app\admin\service\PoolService;
use app\dem\service\WeChat;
use app\dem\service\DggSms;
use app\dem\service\SpSignService;

class IndexController extends HomeBaseController
{
    public function index(SpSignService $SpSignService)
    {
        //$SpSignService->send('13076019190','555555');
        echo "目前只支持小程序二维码系统";die;
        $phone   =   $this->request->param('phone');
        $code   =    $this->request->param('code');
        $error = '';
        if($phone && $code){
            $code = Db::name('personnel_verification')
                ->where(['phone'=>$phone,'code'=>$code])
                ->order('id desc')->find();
            //echo $code['creaetime'];die;
            if((time() -$code['creaetime']) <  60 ){
                session($phone.'-'.'code_status', '1');
                return $this->redirect(url("dem/index/second",['phone'=>$phone]));
            }else{
                $error = '验证码超时';
            }
        }
        return $this->assign('error',$error)->fetch('index/index');
    }

    public function getCode(){
        $data['phone']  =   $this->request->param('phone');
        /*if( $data['phone'] ){
            $data['code']   =   rand(100000,999999);
            $data['creaetime']  =   time();
            Db::name('personnel_verification')->insert($data);
            $sms = new Sms;
            $re = $sms->send( $data['phone'], $data['code']);
        }*/
        if( $data['phone'] ){
            $data['code']   =   rand(100000,999999);
            $data['creaetime']  =   time();
            Db::name('personnel_verification')->insert($data);
            //$sms = new Sms;
            $sms = new SpSignService;
            $re = $sms->send( $data['phone'], $data['code']);
            return $this->jsonApi(200,['msg'=>"验证码发送成功！",'result'=>$re]);
        }
    }

    public function second(){
        header("Content-Type: text/html;charset=utf-8");
        $phone   =   $this->request->param('phone');
        //是否登录
        if(session($phone.'-'.'code_status') != '1'){
              return $this->redirect(url("dem/index/index"));
        }
        $nation =  PoolService::getNation();
        //电话验证
        if(!$phone){
            return $this->redirect('index');
        }
        if($this->request->isPost()){
            if(Db::name('personnel_interview')->where(['phone'=>$phone])->count() > 0){
                $rr = Db::name('personnel')->where(['p_telphone'=>$phone])->update(['p_name'=>$this->request->param('name')]);
                $re = Db::name('personnel_interview')->where(['phone'=>$phone])->update($this->request->param());
                $this->success('信息更新成功',url("dem/index/family",['phone'=>$phone]));
            }
            if(!Db::name('personnel')->where(['p_telphone'=>$phone])->find()){
                Db::name('personnel')->insert([
                    'uid'=>0,
                    'importid'=>0,
                    'p_telphone'=>$phone,
                    'p_name'=>$this->request->param('name'),
                    'p_status'=>1,
                    'maintenance_time'=>date('Y-m-d h:i:s',time()),
                    'interview_status'=>0
                ]);
            }else{
               Db::name('personnel')->where(['p_telphone'=>$phone])->update(['p_name'=>$this->request->param('name')]);
            }
            $re = Db::name('personnel_interview')->insert($this->request->param());
            $this->success('信息录入成功',url("dem/index/family",['phone'=>$phone]));
        }
        $myinterview =  PoolService::getInterviewByPhone($phone);
        $this->assign('phone',$phone);
        $this->assign('nation',$nation);
        if($myinterview){
            $this->assign('myinterview',$myinterview);
            return $this->fetch('index/secondEdit');
        }
        return $this->fetch('index/second');
    }

    public function family(){

        $phone   =   $this->request->param('phone');
        //是否登录
        if(session($phone.'-'.'code_status') != '1'){
            return $this->redirect(url("dem/index/index"));
        }
        //电话验证
        if(!$phone){
            return $this->redirect('index');
        }
        if($this->request->isPost()){
            $ss = $this->request->param();
            unset($ss['phone']);
            $data['family_member'] = json_encode($ss);
           $re = Db::name('personnel_interview')->where(['phone'=>$phone])->update($data);
            $this->success('信息更新成功',url("dem/index/education",['phone'=>$phone]));
        }
        $this->assign('phone',$phone);
        return $this->fetch('index/family');
    }

    public function education(){
        $phone   =   $this->request->param('phone');
        //是否登录
        if(session($phone.'-'.'code_status') != '1'){
            return $this->redirect(url("dem/index/index"));
        }
        //电话验证
        if(!$phone){
            return $this->redirect('index');
        }
        if($this->request->isPost()){
            $ss = $this->request->param();
            unset($ss['phone']);
            $data['educationbackground'] = json_encode($ss);
            $re = Db::name('personnel_interview')->where(['phone'=>$phone])->update($data);
            $this->success('信息录入成功',url("dem/index/work",['phone'=>$phone]));
        }
        $this->assign('phone',$phone);
        return $this->fetch('index/education');
    }

    public function work(){
        $phone   =   $this->request->param('phone');
        //是否登录
        if(session($phone.'-'.'code_status') != '1'){
            return $this->redirect(url("dem/index/index"));
        }
        //电话验证
        if(!$phone){
            return $this->redirect('index');
        }
        if($this->request->isPost()){
            $ss = $this->request->param();
            unset($ss['phone']);
            $data['work'] = json_encode($ss);
            $data['finish_status']=1;
            $re = Db::name('personnel_interview')->where(['phone'=>$phone])->update($data);

             $this->success('信息录入成功',url("dem/index/index"));


        }
        $this->assign('phone',$phone);
        return $this->fetch('index/work');
    }

    public function block()
    {
        echo "block";die;
        return $this->fetch();
    }

    public function ws()
    {
        echo "ws";die;
        return $this->fetch(':ws');
    }
    public function wapPersonnel(){
        $pid = $this->request->param('pid');
        $code = $this->request->param('code');
        if($pid){
            $data   =   Db::name('personnel')
                ->alias('a')
                ->field('a.create_time as personnel_create_time,a.pid,a.p_telphone,a.p_name,b.*')
                ->join('personnel_interview b','a.p_telphone = b.phone','left')
                ->where(['a.pid'=>$pid])
                ->find();
        }
        $list = [];
        $date = date('Y-m-d',time());
        if($code){//根据面试官的员工号查询简历
            $map[]  =   ['u.user_login','=',$code];
            if($interview_time = $this->request->param('interview_time')){
                $map[] = ['a.interview_time','=',$interview_time];
            }else{

                $map[] = ['a.interview_time','like',$date.'%'];
            }
            $data   =   Db::name('personnel_invite')
                ->alias('a')
                ->field('c.create_time as personnel_create_time
                ,u.user_login,u.user_nickname,a.interviewer,a.pid,a.interview_time,
                c.p_telphone,c.p_name,b.*')
                ->join('user u','a.interviewer = u.id','left')
                ->join('personnel c','a.pid = c.pid','left')
                ->join('personnel_interview b','c.p_telphone = b.phone','left')
                ->where($map)
                ->find();
            //echo Db::name('personnel_invite')->getLastSql();
        }else{
            return '今天无简历信息！';
        }
        //面试列表
        $list   =Db::name('personnel_invite')
            ->alias('a')
            ->field('c.create_time as personnel_create_time
                ,u.user_login,u.user_nickname,a.interviewer,a.pid,a.interview_time,
                c.p_telphone,c.p_name,b.*')
            ->join('user u','a.interviewer = u.id','left')
            ->join('personnel c','a.pid = c.pid','left')
            ->join('personnel_interview b','c.p_telphone = b.phone','left')
            ->where([
                ['u.user_login','=',$code],
                ['a.interview_time','like',$date.'%']
            ])
            ->select();
        $interview = [];
        if($data){//面试信息
            $interview  =  Db::name('personnel_invite')
                ->alias('a')
                ->field('a.*,b.user_login,b.user_nickname,f.*')
                ->join('personnel c','a.pid=c.pid','left')
                ->join('user b','a.interviewer=b.id','left')
                ->join('personnel_interview f','f.phone=c.p_telphone','left')
                ->where(['a.pid'=>$data['pid']])
                ->order('a.iid desc')
                ->select() ;
        }
        $family_member = isset($data['family_member']) && $data['family_member'] ? json_decode($data['family_member'],true):json_decode('{"relat":["",""],"name":["",""],"company":["",""],"address":["",""],"telephone":["",""]}',true);
        $educationbackground = isset($data['educationbackground']) && $data['educationbackground'] ? json_decode($data['educationbackground'],true) : json_decode('{"school":[""],"start":[""],"end":[""],"major":[""],"certificate":[""]}',true);
        $work = isset($data['work']) && $data['work'] ? json_decode($data['work'],true) : json_decode('{"company":["","",""],"start":["","",""],"end":["","",""],"job":["","",""],"pay":["","",""],"reson":["","",""],"witness_phone":["","",""]}',true);
        $family_member = $this->json_de($family_member);
        $educationbackground = $this->json_de($educationbackground);
        $work = $this->json_de($work);
        $nation = PoolService::getNation();
        //var_dump($work);die;
        return $this->assign('family_member',$family_member)
            ->assign('list',$list)
            ->assign('interview',$interview)
            ->assign('nation',$nation)
            ->assign('educationbackground',$educationbackground)
            ->assign('work',$work)
            ->assign('vo',$data)->fetch();

    }

    public function json_de($array){
        if(!is_array($array)){
            return false;
        }
        $arra_re = [];
        $tem = [];
        foreach ($array as $key =>$val){
            if(is_array($val)){
                foreach ($val as $ke => $va){
                    $tem ["$ke"]["$key"] = $va;// ['0'=>['company'=1],'1'=>['company'=>2]]
                }
            }
            // unset($tem);
        }
        return $tem;
    }
}
