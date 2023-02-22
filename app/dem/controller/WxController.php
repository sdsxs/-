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
use think\App;
use think\facade\Config;
use think\facade\Db;
use app\dem\service\WeChat;
use app\dem\service\Token;
use app\admin\service\PoolService;
use DySDKLite\Sms;
use app\dem\service\SpSignService;

class WxController extends HomeBaseController
{
    protected  $wechat;
    protected $token;
    function __construct(App $app)
    {
        parent::__construct($app);
        $this->wechat = new WeChat();
        $this->token    =   new Token();
    }

    public function login(){
        $code   =   $this->request->param('code');
        $data   =   $this->wechat->login($code);
        $needbind = false;//不需要绑定电话
        $uid = Db::name('personnel_interview')->where(['openid'=>$data['openid']])->value('id');
        if(isset($data['openid']) && isset($data['session_key'])){
            if(!$uid){//不存在openid 则需要绑定电话
                $needbind = true;
                return $this->jsonApi("200",['openid'=>$data['openid'],'needbind'=>$needbind,'token'=>0]);die;
            }
            //session_start();
            //$_SESSION["$token"] = $uid;
            //return $this->(['openid'=>$data['openid'],'needbind'=>$needbind]);die;

        }
        return $this->jsonApi("200",['openid'=>$data['openid'],'needbind'=>$needbind,'token'=>$this->token->createToken($uid)]);
    }
    /*public function login(){
        $code   =   $this->request->param('code');
        $data   =   $this->wechat->login($code);
        $needbind = false;//不需要绑定电话
        $uid = Db::name('personnel_interview')->where(['openid'=>$data['openid']])->value('id');

        if(isset($data['openid']) && isset($data['session_key'])){

            if(!$uid){
                $uid =  Db::name('personnel_interview')->insertGetId(['openid'=>$data['openid']]);
            }
        }
        return $this->jsonApi("200",['openid'=>$data['openid'],'needbind'=>$needbind,'token'=>$this->token->createToken($uid)]);
    }*/
    public function index()
    {
        $phone   =   $this->request->param('phone');
        $code   =    $this->request->param('code');
        $openid   =    $this->request->param('openid');
        $error = '';
        if($phone && $code){
            $code = Db::name('personnel_verification')
                ->where(['phone'=>$phone,'code'=>$code])
                ->order('id desc')->find();
            //echo $code['creaetime'];die;
            if((time() -$code['creaetime']) <  60 ){
                $uid    =   Db::name('personnel_interview')->where(['phone'=>$phone])->value('id');
                if( $uid){
                    Db::name('personnel_interview')->where(['phone'=>$phone])->update(['openid'=>$openid]);
                }else{
                    //$uid = 0;
                    $uid    =   Db::name('personnel_interview')->insertGetId(['openid'=>$openid,'phone'=>$phone]);
                }
                return $this->jsonApi(200,['msg'=>"succedd",'token'=>$this->token->createToken($uid)]);
            }else{
                return $this->jsonApi(500,['msg'=>"验证码过期",'status'=>0,'token'=>0]);
            }
        }
        return $this->jsonApi(500,['msg'=>"参数错误",'status'=>0,'token'=>0]);
    }

    public function getCode(){
        $data['phone']  =   $this->request->param('phone');
        if(!$this->token->checkToken($this->request->header('token'))){
            return json_encode(['openid'=>$data['openid'],'token'=>0]);die;
        }
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

    public function basedata(){

    }

    public function second(){
        $openid   =   trim($this->request->param('openid'));
        $card   =   $this->request->param('card');

        if(!PoolService::validation_filter_id_card($card)){
            return $this->jsonApi(500,['msg'=>'身份证号码错误！']);
        }
        if(!$this->token->checkToken($this->request->header("token"))){
            return $this->jsonApi(500,['msg'=>'token错误！']);
        }
        if($this->request->param('is_recommend') == '是'){
            if(trim($this->request->param('recommend_user')) == ''){
                return $this->jsonApi(500,['msg'=>'请填写推荐人！']);
            }
        }
        if($this->request->isPost() && $openid){
            $phone  =   Db::name('personnel_interview')->where(['openid'=>$openid])->value("phone");
            if($phone){
                //$rr = Db::name('personnel')->where(['p_telphone'=>$phone])->update(['p_name'=>$this->request->param('name')]);
                $re = Db::name('personnel_interview')->where(['openid'=>$openid])->update($this->request->param());
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
            //$re = Db::name('personnel_interview')->insert($this->request->param());
            return $this->jsonApi(200,['msg'=>"success"]);
        }
        return $this->jsonApi(500,['msg'=>"fail"]);
    }

    /*public function second(){
        $openid   =   trim($this->request->param('openid'));
        //是否登录
        $card   =   $this->request->param('card');

        if(!PoolService::validation_filter_id_card($card)){
            return $this->jsonApi(500,['msg'=>'身份证号码错误！']);
        }
        if(!$this->token->checkToken($this->request->header("token"))){
            return $this->jsonApi(500,['msg'=>'token错误！']);
        }
        if($this->request->param('is_recommend') == '是'){
            if(trim($this->request->param('recommend_user')) == ''){
                return $this->jsonApi(500,['msg'=>'请填写推荐人！']);
            }
        }
        if($this->request->isPost() && $openid){
            $data  =   Db::name('personnel_interview')->where(['openid'=>$openid])->find();
            $phone =    $data['phone'];
            if($data){
                $re = Db::name('personnel_interview')->where(['openid'=>$openid])->update($this->request->param());
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
            //$re = Db::name('personnel_interview')->insert($this->request->param());
            return $this->jsonApi(200,['msg'=>"success"]);
        }
        return $this->jsonApi(500,['msg'=>"fail"]);
    }*/

    public function family(){

        $openid   =   trim($this->request->param('openid'));
        //是否登录
        if(!$this->token->checkToken($this->request->header("token"))){
            return $this->jsonApi(500,['msg'=>'token错误！']);
        }
        if($this->request->isPost() && $openid){
            $ss = $this->request->param();
            unset($ss['phone']);
            $data['family_member'] = json_encode($ss);
            $re = Db::name('personnel_interview')->where(['openid'=>$openid])->update($data);
            return $this->jsonApi(200,['msg'=>"success"]);
       }
        return $this->jsonApi(500,['msg'=>"fail"]);
    }

    public function education(){
        $openid   =   trim($this->request->param('openid'));
        //是否登录
        if(!$this->token->checkToken($this->request->header("token"))){
            return $this->jsonApi(500,['msg'=>'token错误！']);
        }
        if($this->request->isPost() && $openid){
            $ss = $this->request->param();
            unset($ss['phone']);
            $data['educationbackground'] = json_encode($ss);
            $re = Db::name('personnel_interview')->where(['openid'=>$openid])->update($data);
            return $this->jsonApi(200,['msg'=>"success"]);
        }
        return $this->jsonApi(500,['msg'=>"fail"]);
    }

    public function work(){
        $openid   =   trim($this->request->param('openid'));
        //是否登录
        if(!$this->token->checkToken($this->request->header("token"))){
            return $this->jsonApi(500,['msg'=>'token错误！']);
        }
        if($this->request->isPost() && $openid){
            $ss = $this->request->param();
            unset($ss['phone']);
            $data['work'] = json_encode($ss);
            $data['finish_status']=1;
            $re = Db::name('personnel_interview')->where(['openid'=>$openid])->update($data);
            return $this->jsonApi(200,['msg'=>"success"]);
        }
        return $this->jsonApi(500,['msg'=>"fail"]);
    }

    public function getinfo(){
        $openid   =   trim($this->request->param('openid'));
        //是否登录
        if(!$this->token->checkToken($this->request->header("token"))){
            return $this->jsonApi(500,['msg'=>'token错误！']);
        }
        if(!$openid || !$this->request->header("token")){
            return $this->jsonApi(500,['msg'=>'token为空！']);
        }

        $data   =   Db::name('personnel_interview')
            ->alias('a')
            ->join('personnel b','a.phone=b.p_telphone','left')
            ->where(['a.openid'=>$openid])->find();
        $family_member = isset($data['family_member']) && $data['family_member'] ? json_decode($data['family_member'],true):json_decode('{"relat":["",""],"name":["",""],"company":["",""],"address":["",""],"telephone":["",""]}',true);
        $educationbackground = isset($data['educationbackground']) && $data['educationbackground'] ? json_decode($data['educationbackground'],true) : json_decode('{"school":[""],"start":[""],"end":[""],"major":[""],"certificate":[""]}',true);
        $work = isset($data['work']) && $data['work'] ? json_decode($data['work'],true) : json_decode('{"company":["","",""],"start":["","",""],"end":["","",""],"job":["","",""],"pay":["","",""],"reson":["","",""],"witness_phone":["","",""]}',true);
        $data['family_member'] = $this->json_de($family_member);
        $data['educationbackground'] = $this->json_de($educationbackground);
        $data['work'] = $this->json_de($work);
        return $this->jsonApi(200,$data);
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

    public function jsonApi(int $code,array $data){
        return json_encode(['code'=>$code,"data"=>$data]);
    }

    public function getnation(){
        $nation = PoolService::getNation();
        return $this->jsonApi(200,$nation);
    }

    public function test(){
        echo '测试电话号码13076019190：979894';
        $sms = new SpSignService;
        $re = $sms->send( '13076019190', '979894');
        echo '测试结果：';
        print_r($re);
    }
}