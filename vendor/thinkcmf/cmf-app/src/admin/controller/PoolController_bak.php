<?php
namespace app\admin\controller;

use app\admin\model\PersonnelJob;
use cmf\controller\AdminBaseController;
use think\App;
use think\facade\Db;
use app\admin\model\AdminMenuModel;
use app\admin\service\AdminMenuService;
use app\admin\model\PersonnelModel;
use app\admin\model\PersonnelCollectionModel;
use app\admin\model\PersonnelInviteModel;
use app\admin\model\PersonnelFollowModel;
use think\Request;
use think\facade\Validate;
use app\admin\model\UserModel;
use app\admin\model\PersonnelMessageModel;
use app\admin\service\PoolService;
use app\admin\model\PersonnelInterviewModel;

class PoolController extends AdminBaseController
{
    public $uid = 0;
    public $user = [];
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->uid = cmf_get_current_admin_id();
        $this->user = cmf_get_current_user();
    }

    /**人才库首页
     * @param PersonnelModel $PersonnelModel
     * @param
     * @return mixed
     */
    public function index(PersonnelModel $PersonnelModel,PersonnelJob $PersonnelJob){
        $where = '';
        $request = $this->request->param();
        if(isset($request['export'])){
            $data = $PersonnelModel->getAll($request,$this->uid,'all');
            PoolService::exportindex($data);
        }
        $data   =   $PersonnelModel->getAll($request,$this->uid);
        return $this->assign('data',$data)->fetch();
    }

    /**我的收藏
     * @param PersonnelCollectionModel $PersonnelCollectionModel
     * @param Request $request
     * @return mixed
     */
    public function collect(PersonnelCollectionModel $PersonnelCollectionModel,Request $request){
        $para = $request->param();
        $data = $PersonnelCollectionModel->getAll($this->uid,$para);
        return $this->assign('data',$data)->fetch();
    }

    /**
     * 收藏简历
     */
    public function doCollect(){
        $pid = $this->request->param('pid');
        $re = PoolService::doCollect($this->uid,$pid);
        return $re ? $this->success('收藏成功！') : $this->error('收藏失败！你已经收藏了该简历');
    }
    /**我的邀约
     * @param PersonnelInviteModel $PersonnelInviteModel
     * @param Request $request
     * @return mixed
     */
    public function invite(PersonnelInviteModel $PersonnelInviteModel,Request $request){
        $data = $PersonnelInviteModel->getAll($this->uid,$request->param());
        return $this->assign('data',$data)->fetch();
    }

    /**我添加的职位列表
     * @return mixed
     */
    public function jobList(){
        $jobList = PoolService::getMyjobList($this->uid);
        return $this->assign('jobList',$jobList)->fetch();
    }
    /**添加职位
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addJob(){
        if($this->request->isPost()){
            $data = array_merge(['uid'=>$this->uid],$this->request->param());
            // var_dump($data);die;
            $re = PoolService::createJob($data);
            return  $re ? $this->success('success') : $this->error('error');
        }
        $company = PoolService::getCompany();
        return $this->assign('company',$company)->fetch();
    }

    /**
     * 关闭职位
     */
    public function closeJob(){
        $id = $this->request->param('id');
        if($id){
            $re = PoolService::closeJob($id);
            return  $re ?  $this->success($re,url('admin/pool/jobList')) : $this->error('error',0);
        }
        return  $this->result('error',0);
    }

    /**获取部门
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDepartment(){
        $companyid   =   $this->request->param('companyid');
        $data   =   PoolService::getDepartment($companyid=$companyid);
        return $this->result($data);
    }
    /**我上传的简历
     * @param PersonnelModel $PersonnelModel
     * @return mixed
     */
    public function myPersonnel(PersonnelModel $PersonnelModel){
        $where = '';
        $request = $this->request->param();
        if($this->request->post() || isset($request['export'])){
            if(isset($request['create_time']) && $request['create_time']){
                $where .= $where ? " and a.create_time like '".$request['p_name']."%'" : "a.create_time like '".$request['create_time']."%'";
            }
            if(isset($request['p_name']) && $request['p_name']){
                $where .= $where ? " and a.p_name='".$request['p_name']."'" : "a.p_name='".$request['p_name']."'";
            }
            if(isset($request['genjin_time']) && $request['genjin_time']){
                $where .= $where ? " and a.update_time like '".$request['genjin_time']."%'" : "a.update_time like '".$request['genjin_time']."%'";
            }
            if(isset($request['p_telphone']) && $request['p_telphone']){
                $where .= $where ? " and a.p_telphone='".$request['p_telphone']."'":"a.p_telphone='".$request['p_telphone']."'";
            }
            if(isset($request['zone']) && $request['zone']){
                $where .= $where ? " and g.zone='".$request['zone']."'" : "g.zone='".$request['zone']."'";
            }
            if(isset($request['company']) && $request['company']){
                $where .= $where ?  " and g.company='".$request['company']."'" : "g.company='".$request['company']."'";
            }
            if(isset($request['name']) && $request['name']){
                $where .= $where? " and g.name='".$request['name']."'" : "g.name='".$request['name']."'";
            }
            if(isset($request['job']) && $request['job']){
                $where .= $where ? " and f.job='".$request['job']."'" : "f.job='".$request['job']."'";
            }
            if(isset($request['i_status']) && $request['i_status']){
                $where .= $where ? " and a.i_status='".$request['i_status']."'" : "a.i_status='".$request['i_status']."'";
            }
            if(isset($request['interview_status']) && $request['interview_status']!='' ){
                $where .= $where ? " and a.interview_status='".$request['interview_status']."'" : "a.interview_status='".$request['interview_status']."'";
            }
            if(isset($request['blang']) && $request['blang']){
                $where .= $where ? " and h.user_nickname like '".$request['blang']."%'" : "h.user_nickname like '".$request['blang']."%'";
            }
            //$where  .= $where ? " and a.interview_status != 7" : "a.interview_status != 7";
            if(isset($request['export']) && $request['export']){
                $data   =   Db::name('personnel')->alias('a')
                    ->distinct(true)
                    ->field('a.*,h.user_nickname as blang,b.c_status,b.cid,b.uid,c.user_nickname,a.create_time as p_create_time,f.job as apply_job,g.*')
                    ->join('personnel_cooperation b','a.pid=b.pid and b.c_status=1','left')
                    ->join('user h','a.uid=h.id','left')
                    ->join('user c','b.uid=c.id','left')
                    ->join('personnel_interview f','a.p_telphone=f.phone','left')
                      ->join('user_department g','a.current_departid=g.id','left');
                    if($where){
                        $data = $data->where($where)->order('a.pid desc')->select();
                    }else{
                        $data = $data->where(['a.uid'=>$this->uid])->order('a.pid desc')->select();
                    }
                //echo DB::name('personnel')->getLastSql();die;
                PoolService::exportmypersonnel($data);
            }


        }/*else{
            $data = $PersonnelModel->getByuid(cmf_get_current_admin_id(),$request->param());
        }*/
        $data =   Db::name('personnel')->alias('a')
            ->distinct(true)
            ->field('a.*,h.user_nickname as blang,b.c_status,b.cid,b.uid,c.user_nickname,a.create_time as p_create_time,f.job as apply_job,g.*')
            ->join('personnel_cooperation b','a.pid=b.pid and b.c_status=1','left')
            ->join('user h','a.uid=h.id','left')
            ->join('user c','b.uid=c.id','left')
            ->join('personnel_interview f','a.p_telphone=f.phone','left')
            ->join('user_department g','a.current_departid=g.id','left');
        if($where){
            $data = $data->where($where)->order('a.pid desc')->paginate(['query'=>$request, //url额外参数
                'list_rows' => 10 ]);
        }else{
                $data = $data->where(['a.uid'=>$this->uid])->order('a.pid desc')->paginate(['query'=>$request, //url额外参数
                    'list_rows' => 10 ]);
        }
        $pid_array  =   [];
        foreach ($data as $val){
            $pid_array[]    =   $val['pid'];
        }
        $str    =   join(',',$pid_array);

        //var_dump(Db::name('personnel')->getLastSql());die;
        $cooperation    =   PoolService::getCooperation($str);
        //var_dump($cooperation);die;
        return $this->assign('data',$data)->assign('cooperation',$cooperation)->fetch();
    }

    public function delPersonnel(){
        $data   =   $this->request->param();
        if(isset($data['id'])){
            if(isset($data['filepath'])){
                unlink($data['filepath']);
            }
            $re =   Db::name('personnel')->where(['pid'=>$data['id']])->update(['filepath'=>"",'filename'=>'']);
            return $re ? $this->success('删除成功！') : $this->error('删除失败！');
        }
    }
    /**添加人才简历
     * @param PersonnelModel $PersonnelModel
     * @return mixed
     */
    public function addPersonnel(PersonnelModel $PersonnelModel,Request $request){
        if($request->isPost()){
            $data = array_merge(['uid'=>$this->uid],$request->param());
           // var_dump($data);die;
            $re = $PersonnelModel->insertAuth($data);
            return $this->result([],$re['code'],$re['msg']);
        }
        return $this->fetch();
    }

    /**添加跟进记录
     * @param PersonnelFollowModel $PersonnelFollowModel
     * @param Request $request
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addFollow(PersonnelFollowModel $PersonnelFollowModel,Request $request){
        if($pid    =   $request->isPost()){
            $data = array_merge(['uid'=>$this->uid],$request->param());
            $re = $PersonnelFollowModel->authUpdate($data);
            return $this->result([],$re['code'],$re['msg']);
        }
        $data = PersonnelModel::find($request->param('pid'));
        return $this->assign('data',$data)->fetch();
    }

    /**跟进记录
     * @param PersonnelFollowModel $PersonnelFollowModel
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function follow(PersonnelFollowModel $PersonnelFollowModel,Request $request){
        $data   =   $PersonnelFollowModel->getByPidUser($this->uid);
        return $this->assign('data',$data)->fetch();
    }

    /**添加邀约
     * @param Request $request
     * @param PersonnelInviteModel $PersonnelInviteModel
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     *
     */
    public function addInvite(PersonnelInviteModel $PersonnelInviteModel){
        if( $this->request->isPost()){
            $para = array_merge($this->request->param(),['uid'=>$this->uid]);
            $invite  =   Db::name('personnel_interview_set')->where(['sid'=>$this->request->param('interview_time_retset')])->find();
            $interview_time_retset  =   $invite['start_time'];
            if($this->request->param('situation') == 2){
                if(Db::name('personnel_invite')->where(['situation'=>1,'pid'=>$this->request->param('pid')])->count()<1){
                    $this->error("邀约失败 ,该简历无初试！");
                }
                if($invite['code']){
                    $array  =   json_decode($invite['code'],true);
                    if(!in_array($para['number'],$array)){
                        $this->error("邀约失败 ,会议号不正确或浏览器不支持插件！");
                    }
                }
                $para['interview_time'] = $interview_time_retset;
                $para['sid'] = $this->request->param('interview_time_retset');
                if(strtotime($interview_time_retset) - time() < 3600){
                    $this->error("邀约失败 ,面试前1小时停止预约！");
                }
                $count = Db::name('personnel_interview_set')->where(['start_time'=>$para['interview_time']])->value('count');
                if($count){
                    $count =  $count * 10;
                }else{
                    $count = 10;
                }
                if(Db::name('personnel_invite')->where(['situation'=>$this->request->param('situation'),'interview_time'=>$this->request->param('interview_time')])->count() >= $count){
                    $this->error("邀约失败 ,面试人员超过$count人".$this->request->param('interview_time').'已经有10人面试！');
                }
                //print_r(['situation'=>$this->request->param('situation'),'interview_time'=>$para['interview_time'],'number'=>$this->request->param('number')]);die;
                if(Db::name('personnel_invite')->where(['situation'=>$this->request->param('situation'),'interview_time'=>$para['interview_time'],'number'=>$this->request->param('number')])->count() >= 10){
                    $this->error("邀约失败 ,面试会议号".$this->request->param('number').'已经有10人面试！');
                }
                $re = Db::name('personnel_invite')->where(['pid'=>$this->request->param('pid')])->order('iid desc')->find();
               if(!$re['departid']){
                   $this->error("邀约失败！没有分配部门");
               }
                if($re){
                    $para = array_merge($para,['departid'=>$re['departid'],
                        'job'=>$re['job'],
                        'center'=>$re['center'],
                        's_department'=>$re['s_department'],
                        's_group'=>$re['s_group'],
                        'channel'=>$re['channel']
                    ]);
                }
            }else{
                $para['interview_time'] = $this->request->param('interview_time_test');
            }
            unset($para['interview_time_test']);
            unset($para['interview_time_retset']);
            if(!$para['interview_time']){
                $this->error("邀约失败！无面试时间");
            }
            if(!isset($para['invite_time']) || !$para['invite_time']){
                $para['invite_time']    =   date("Y-m-d",time());
            }
            $re = PoolService::addInvite($para);
            if($re){
                $this->success("邀约成功！", cmf_url('admin/pool/addInvite',['pid'=>$this->request->param('pid')]));
            }
            $this->error("请确认邀约时间、面试时间和面试人员！");
        }
        $data   =   (new PersonnelModel)->find($this->request->param('pid'));
        $invite =    (new PersonnelInviteModel())
            ->alias('a')
            ->field('a.*,b.user_nickname as interview_name')
            ->join('user b','a.interviewer=b.id','left')
            ->where(['pid'=>$this->request->param('pid')])->select();
        return $this->assign('data',$data)->assign('invite',$invite)->fetch();
    }

    /**用户查询
     * @param UserModel $user
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function findUser(UserModel $user,Request $request){
        $keywords = $request->param('name');
        $list = $user->where('user_nickname like "'.$keywords.'%"')->select();
        //echo $user->getLastSql();
        return $this->result(['data'=>$list],200);
    }

    /**我的消息
     * @param PersonnelMessageModel $PersonnelMessageModel
     * @return mixed
     */
    public function message(PersonnelMessageModel $PersonnelMessageModel){
        $para   =   [];
        $request = $this->request->param();
        if(isset($request['m_status'])){
            $para['m_status']   =   $request['m_status'];
        }
        $list   =   $PersonnelMessageModel->getByUid($this->uid,$para);
        return $this->assign('data',$list)->fetch();
    }

    public function msgRead(){
       if($id = $this->request->param('id')){
           $re = PoolService::msgRead($id);
           return $this->result(['data'=>$re],200);
        }
        return $this->result('',0);
    }
    /**我的面试
     * @param PersonnelInviteModel $PersonnelInviteModel
     * @return mixed
     */
    public function interview(PersonnelInviteModel $PersonnelInviteModel){
        $para   =   [];
        $request = $this->request->param();
        if(isset($request['i_status'])){
            $para['i_status']   =   $request['i_status'];
        }
        if(isset($request['pid'])){
            $list = $PersonnelInviteModel->getByInterviewer(false,$para,$request['pid']);
        }else{
            $list = $PersonnelInviteModel->getByInterviewer($this->uid,$para);
        }
        $uid    =   $this->uid;
        $role_array =   PoolService::getUsridByRole(6);
        $role   =    in_array($uid,$role_array)  ?  1:0;
        return $this->assign('data',$list)->assign('role',$role)->fetch();
    }

    public function interviewList(){
        $list   =   PoolService::getInterviewList();
        return $this->assign('list',$list)->fetch();
    }
    /**面试反馈
     * @param PersonnelInviteModel $PersonnelInviteModel
     * @return mixed|void
     */
    public function conclusion(PersonnelInviteModel $PersonnelInviteModel){
        if($iid =   $this->request->param('iid')){
            if($this->request->isPost()){
                $invite =  $PersonnelInviteModel->where(['iid'=>$iid])->find();
                //操作初试通过之前，在系统填写简历；
                if(!$invite){
                    return $this->error('面试信息错误~');
                }
                /*$record     =   Db::name('personnel')
                    ->alias('a')
                    ->field('a.pid,b.family_member,b.educationbackground')
                    ->join('personnel_interview b','a.p_telphone=b.phone')
                    ->where(['a.pid'=>$invite['pid']])->find();
                if(!$record['family_member'] || !$record['educationbackground'] ){
                    return $this->error('完善简历后才能进行此操作！~');
                }*/
                $auth = PoolService::authPersonnl($invite['pid']);
                if($auth['status'] == 'fail'){
                    return $this->error($auth['msg'].' 完善简历后才能进行此操作！~');
                }
                Db::startTrans();
                try{
                    if($this->request->param('i_status') == '2'){//面试没有通过简历私有60天
                        $Personnel['maintenance_time'] = PoolService::getMaintenanceTime(60);
                    }

                    if($invite['situation'] == 1){//初试
                        if($this->request->param('i_status') == '2'){
                            $Personnel['interview_status']  = 2;
                        }
                        if($this->request->param('i_status') == '3'){
                            $Personnel['interview_status']  = 1;
                        }
                    }
                    if($invite['situation'] == 2){//复试
                        if($this->request->param('i_status') == '2'){
                            $Personnel['interview_status']  = 4;
                        }
                        if($this->request->param('i_status') == '3'){
                            $Personnel['interview_status']  = 3;
                        }
                    }
                    if(isset($Personnel)){
                        (new PersonnelModel())->where(['pid'=>$this->request->param('pid')])->update($Personnel);
                    }
                    $face_result = isset($Personnel['interview_status']) ? PoolService::getPersonnelStatus($Personnel['interview_status']) : '其他';
                    $PersonnelInviteModel->where(['iid'=>$iid])->update($this->request->param());
                    PoolService::operationLog($this->uid,'添加了简历'.$this->request->param('pid').'的面试结果:'.$face_result,$this->request->param('pid'));
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return $this->error('面试信息错误~');
                }
                return $this->success('结果已经上传！');
            }
            $list   =   $PersonnelInviteModel->getByIid($iid);
            return $this->assign('data',$list)->fetch();
        }
        return $this->error('面试信息错误~');
    }

    /**申请合作
     *
     */
    public function addCooperation(){
        $request    =   $this->request->param();
        if(empty($request)){
            return $this->error();
        }
        $re =   false;
        if(isset($request['pid'])){
            $belongid =   Db::name('personnel')->where(['pid'=>$request['pid']])->value('uid');
            $re =   PoolService::addCooperation($uid=$this->uid,$pid = $request['pid'],false,$belongid);
        }
        if(isset($request['p_telphone'])){
            $belongid =   Db::name('personnel')->where(['p_telphone'=>$request['p_telphone']])->value('uid');
            $re =   PoolService::addCooperation($uid=$this->uid,false,$p_telphone = $request['p_telphone'],$belongid);
        }

        return $re ? $this->success('success'):$this->error('不能合作,已经申请合作或者被驳回');
    }

    /**
     * 确定合作
     */
    public function doCooperation(){
        $cid    =   $this->request->param('cid');
        $pid    =   $this->request->param('pid');
        if($cid){
            $re = PoolService::doCooperation($cid,$pid,$this->uid);
            return $re ? $this->success('success'):$this->error('已经申请合作');
        }
    }
    public function doPublic(){
        $p_status    =   $this->request->param('status');
        $pid    =   $this->request->param('pid');
        if($pid){
            $re = PoolService::doPublic($pid,$p_status);
            PoolService::operationLog($this->uid,'公开了简历',$pid);
            return $re ? $this->success('success'):$this->error('已经申请合作');
        }
    }

    /**合作列表
     * @return mixed
     * @throws \think\db\exception\DbException
     */
    public function cooperationList(){
        $data = PoolService::cooperationList($this->uid);
        return $this->assign('list',$data)->fetch();
    }

    /**导出面试通过名单
     * @param PersonnelInterviewModel $PersonnelInterviewModel
     * @return mixed
     * @throws \PHPExcel_Reader_Exception
     */
    public function exportName(PersonnelInterviewModel $PersonnelInterviewModel){
        $list = [];
        if($this->request->param('time_start')){
            $start_time =   $this->request->param('time_start');
            $end_time   =   $this->request->param('time_end');
            //var_dump($start_time);die;
            //$data = PoolService::exportName($header_arr,$list,$indexKey);
        }else{
            $start_time =   false;
            $end_time   =   false;
        }
        $list   =   $PersonnelInterviewModel->getForExcel($start_time,$end_time);
        if($this->request->param('export')){
            $header_arr = [
                'A'=>'姓名',
                'B'=>'电话',
                'C'=>'证件号码',
                'D'=>'性别',
                'E'=>'民族',
                'F'=>'地址',
                'G'=>'学历',
                'H'=>'入职时间'
            ];
            $indexKey   =   ['name','phone','card','sex','family','address','education','create_time'];
            PoolService::exportName($header_arr,$list,$indexKey);
        }
        //var_dump($list);die;
        return $this->assign('list',$list)->fetch();
    }

    /**简历拉私
     * @throws \think\db\exception\DbException
     */
    public function pullPersonnel(){
        $pid    =   $this->request->param('pid');
        if($pid){
            $re =   PoolService::pullPersonnel($pid,$this->uid);
            $re1 =   PoolService::operationLog($this->uid,'拉私了简历',$pid);
            return $re ? $this->success('success'):$this->error('该简历已被收录或你收录的简历达到上限！');
        }
    }

    /**
     *
     */
    public function statistics(){

        return $this->fetch();
    }

    /**
     * 初试管理
     */
    public function preliminaryTest(){
        $where = '';
        $request = $this->request->param();
        if(isset($request['update_time']) && $request['update_time']){
            $where .= $where ? " and a.invite_time like '".$request['update_time']."'%" : "a.invite_time like '".$request['update_time']."%'";
        }
        if(isset($request['p_name']) && $request['p_name']){
            $where .= $where ? " and b.p_name='".$request['p_name']."'" : "b.p_name='".$request['p_name']."'";
        }
        if(isset($request['genjin_time']) && $request['genjin_time']){
            $where .= $where ? " and a.interview_time like '".$request['genjin_time']."%'" : "a.interview_time like '".$request['genjin_time']."%'";
        }
        if(isset($request['start']) && $request['start']){
            $where .= $where ? " and a.interview_time > '".$request['start']."'" : "a.interview_time > '".$request['start']."'";
        }
        if(isset($request['end'])  && $request['end']){
            $where .= $where ? " and a.interview_time < '".$request['end']."'" : "a.interview_time < '".$request['end']."'";
        }
        if(isset($request['p_telphone']) && $request['p_telphone']){
            $where .= $where ? " and b.p_telphone='".$request['p_telphone']."'":"b.p_telphone='".$request['p_telphone']."'";
        }
        if(isset($request['zone']) && $request['zone']){
            $where .= $where ? " and c.zone='".$request['zone']."'" : "c.zone='".$request['zone']."'";
        }
        if(isset($request['company']) && $request['company']){
            $where .= $where ?  " and c.company='".$request['company']."'" : "c.company='".$request['company']."'";
        }
        if(isset($request['name']) && $request['name']){
            $where .= $where? " and c.name='".$request['name']."'" : "c.name='".$request['name']."'";
        }
        if(isset($request['job']) && $request['job']){
            $where .= $where ? " and f.job='".$request['job']."'" : "f.job='".$request['job']."'";
        }
        if(isset($request['i_status']) && $request['i_status'] !=''){
            $where .= $where ? " and a.i_status='".$request['i_status']."'" : "a.i_status='".$request['i_status']."'";
        }
        if(isset($request['blang']) && $request['blang']){
            $where .= $where ? " and d.user_nickname like '".$request['blang']."%'" : "d.user_nickname like '".$request['blang']."%'";
        }
        $where .= $where? " and a.situation=1" : 'a.situation=1';
        $where .= " and a.iid in (select max(iid) from tp_personnel_invite group by pid,situation )";
        //$where .= " and a.iid = (select max(iid) from tp_personnel_invite as tb where a.pid=tb.pid)" ;
        if(isset($request['export']) && $request['export']){
            $data = DB::name('personnel_invite')
                ->alias('a')
               // ->distinct(true)
                ->field('a.*,b.*,c.*,d.user_nickname as blang,f.job as apply_job,
                f.education,f.birthday,f.is_graduation,f.sex,f.card,f.channels,f.is_recommend,f.recommend_user,
                d.code,g.user_nickname as interviewer')
                ->join('personnel b','a.pid=b.pid','left')
                ->join('user_department c','b.current_departid=c.id','left')
                ->join('personnel_interview f','b.p_telphone=f.phone','left')
                ->join('user d','b.uid=d.id','left')
                ->join('user g','a.interviewer=g.id','left')
                ->where($where)->order('a.iid desc')
                ->select();
            PoolService::preliminaryTest($data);
        }
        $data = DB::name('personnel_invite')
            ->alias('a')
            //->distinct(true)
            ->field('a.*,b.*,c.*,d.user_nickname as blang,f.job as apply_job')
            ->join('personnel b','a.pid=b.pid','left')
            ->join('user_department c','b.current_departid=c.id','left')
            ->join('personnel_interview f','b.p_telphone=f.phone','left')
            ->join('user d','b.uid=d.id','left')
            ->where($where)->order('a.iid desc')->paginate(['query'=>$request, //url额外参数
                'list_rows' => 10 ]);
        //echo DB::name('personnel_invite')->getLastSql();
        $array  =   [];
        foreach ($data as $key => $val){
            $array[] = $val['pid'];
        }
        $coop = [];
        if(!empty($array)){
            $where1  =   join(',',$array);
            if(isset($request['cooperat']) && $request['cooperat']){
                $where1 .= $where1 ? " and b.user_nickname like '".$request['cooperat']."%'": " b.user_nickname like'".$request['cooperat']."%'" ;
            }
            $coop   =   Db::name('personnel_cooperation')
                ->alias('a')
                ->field('a.*,b.user_nickname as coopuser')
                ->join('user b','a.uid =b.id','left')
                ->where('pid in('.$where1.') and c_status=1')
                ->select();
        }

        //合作人
        //var_dump(DB::name('personnel_invite')->getLastSql());die;
        return $this->assign('data',$data)->assign('coop',$coop)->fetch();
    }
    public function retest(){
        $where = '';
        $request = $this->request->param();
        if(isset($request['update_time']) && $request['update_time']){
            $where .= $where ? " and a.invite_time like '".$request['update_time']."'%" : "a.invite_time like '".$request['update_time']."%'";
        }
        if(isset($request['genjin_time']) && $request['genjin_time']){
            $where .= $where ? " and a.interview_time like '".$request['genjin_time']."%'" : "a.interview_time like '".$request['genjin_time']."%'";
        }
        if(isset($request['p_name']) && $request['p_name']){
            $where .= $where ? " and b.p_name='".$request['p_name']."'" : "b.p_name='".$request['p_name']."'";
        }
        if(isset($request['start']) && $request['start']){
            $where .= $where ? " and a.interview_time > '".$request['start']."'" : "a.interview_time > '".$request['start']."'";
        }
        if(isset($request['end'])  && $request['end']){
            $where .= $where ? " and a.interview_time < '".$request['end']."'" : "a.interview_time < '".$request['end']."'";
        }
        if(isset($request['p_telphone']) && $request['p_telphone']){
            $where .= $where ? " and b.p_telphone='".$request['p_telphone']."'":"b.p_telphone='".$request['p_telphone']."'";
        }
        if(isset($request['zone']) && $request['zone']){
            $where .= $where ? " and c.zone='".$request['zone']."'" : "c.zone='".$request['zone']."'";
        }
        if(isset($request['company']) && $request['company']){
            $where .= $where ?  " and c.company='".$request['company']."'" : "c.company='".$request['company']."'";
        }
        if(isset($request['name']) && $request['name']){
            $where .= $where? " and c.name='".$request['name']."'" : "c.name='".$request['name']."'";
        }
        if(isset($request['job']) && $request['job']){
            $where .= $where ? " and f.job='".$request['job']."'" : "f.job='".$request['job']."'";
        }
        if(isset($request['i_status']) && $request['i_status'] !=''){
            $where .= $where ? " and a.i_status='".$request['i_status']."'" : "a.i_status='".$request['i_status']."'";
        }
        if(isset($request['blang']) && $request['blang']){
            $where .= $where ? " and d.user_nickname like '".$request['blang']."%'" : "d.user_nickname like '".$request['blang']."%'";
        }
        if(isset($request['number']) && $request['number']){
            $where .= $where ? " and a.number like '".$request['blang']."%'" : "a.number like '".$request['number']."%'";
        }
        $where .= $where? " and situation=2" : 'situation=2';
        $where .= " and a.iid in (select max(iid) from tp_personnel_invite group by pid,situation )";
//echo $where;die;
        if(isset($request['export']) && $request['export']){
            $data = DB::name('personnel_invite')
                ->alias('a')
                ->distinct(true)
                ->field('a.*,b.*,c.*,d.user_nickname as blang,d.user_login as code,f.job as apply_job,f.education,f.birthday,f.is_graduation,f.sex,g.user_nickname as interviewer')
                ->join('personnel b','a.pid=b.pid','left')
                ->join('user_department c','b.current_departid=c.id','left')
                ->join('personnel_interview f','b.p_telphone=f.phone','left')
                ->join('user d','b.uid=d.id','left')
                ->join('user g','a.interviewer=g.id','left')
                ->where($where)->order('a.iid desc')
                ->select();
            PoolService::preliminaryTest($data,'retest');
        }
        $data = DB::name('personnel_invite')
            ->alias('a')
            ->distinct(true)
            ->field('a.*,b.*,c.*,d.user_nickname as blang,f.job as apply_job')
            ->join('personnel b','a.pid=b.pid','left')
            ->join('user_department c','b.current_departid=c.id','left')
            ->join('personnel_interview f','b.p_telphone=f.phone','left')
            ->join('user d','b.uid=d.id','left')
            ->where($where)->order('a.iid desc')->paginate(['query'=>$request, //url额外参数
            'list_rows' => 10 ]);
        $array  =   [];
        foreach ($data as $key => $val){
            $array[] = $val['pid'];
        }
        $coop = [];
        if(!empty($array)){
            $where1  =   join(',',$array);
            if(isset($request['cooperat']) && $request['cooperat']){
                $where1 .= $where1 ? " and b.user_nickname like '".$request['cooperat']."%'": " b.user_nickname like'".$request['cooperat']."%'" ;
            }
            $coop   =   Db::name('personnel_cooperation')
                ->alias('a')
                ->field('a.*,b.user_nickname as coopuser')
                ->join('user b','a.uid =b.id','left')
                ->where('a.pid in('.$where1.') and c_status=1')
                ->select();
        }

        //是否有复试编辑结果的权限
        $uid    =   $this->uid;
        $role_array =   PoolService::getUsridByRole(6);
        $role   =    in_array($uid,$role_array)  ?  1:0;
        return $this->assign('data',$data)->assign('role',$role)->assign('coop',$coop)->fetch();
    }

    public function detail(){
        $iid = $this->request->param('iid');
        if($zone = $this->request->param('zone')){
            $company = $this->request->param('company');
            $name = $this->request->param('department');
            $center = $this->request->param('center');
            $s_department = $this->request->param('s_department');
            $s_group = $this->request->param('s_group');
            $departid   =   Db::name("user_department")->where([
                'name'=>$name,
                'company'=>$company,
                'zone'=>$zone,
                'center'=>$center,
                's_department'=>$s_department,
                's_group'=>$s_group
            ])->value('id');
            //echo Db::name("user_department")->getLastSql();die;
            if(!$departid){
                $this->error('该部门不存在！');
            }
            $up =   [
                'departid'  =>  $departid,
                'center'    =>  $this->request->param('center'),
                's_department'    =>  $this->request->param('s_department'),
                's_group'    =>  $this->request->param('s_group'),
            ];
            Db::startTrans();
            try{
                DB::name('personnel_invite')->where(['iid'=>$iid])->update($up);
                $pid =  DB::name('personnel_invite')->where(['iid'=>$iid])->value('pid');
                DB::name('personnel')->where(['pid'=>$pid])->update(['current_departid'=>$departid]);
                PoolService::operationLog($this->uid,'分配了事业部',$pid);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->success('error');
            }
            $this->success('success');
        }
        $data = DB::name('personnel_invite')
            ->alias('a')
            ->field('a.*,b.*,c.*,d.user_nickname as blang')
            ->join('personnel b','a.pid=b.pid','left')
            ->join('user_department c','a.departid=c.id','left')
            ->join('user d','a.uid=d.id','left')
            ->where('a.iid = '.$iid)->find();
        $coop   =   Db::name('personnel_cooperation')
            ->alias('a')
            ->field('a.*,b.user_nickname as coopuser')
            ->join('user b','a.uid =b.id','left')
            ->where('a.pid = '.$data['pid'])
            ->select();
        $port = PoolService::getAllPort();
        return $this->assign('data',[$data])->assign('coop',$coop)->assign('port',$port)->fetch();
    }

    public function getdepart(){
        $request = $this->request->param();
        if(isset($request['s_department'])){
            return  $this->result( PoolService::getAllSgroup($request['s_department'],$request['center'],$request['department'],$request['company'],$request['zone'],$request['port']));
        }
        if(isset($request['center'])){
            return  $this->result( PoolService::getAllSdepartment($request['center'],$request['department'],$request['company'],$request['zone'],$request['port']));
        }
        if(isset($request['department'])){
            return  $this->result( PoolService::getAllCenter($request['department'],$request['company'],$request['zone'],$request['port']));
        }
        if(isset($request['company'])){
            return  $this->result( PoolService::getAllDepartment($request['company'],$request['zone'],$request['port']));
        }
        if(isset($request['zone'])){
            return  $this->result( PoolService::getCompanyByZone($request['zone'],$request['port']));
        }
        if(isset($request['port'])){
            return  $this->result( PoolService::getCompanyByPort($request['port']));
        }
    }


    public function addTrain(){

        $request    =   $this->request->param();
        if(isset($request['iid']) && $request['iid']){
            $invite =   Db::name('personnel_invite')->where(['iid'=>$request['iid']])->find();
            if($invite){
                if(isset($request['time']) && $request['time']){
                    $data['train_date'] =   $request['time'] ;
                }
                $data['departid'] =   $invite['departid'];
                $data['iid'] =   $invite['iid'];
                $data['pid'] =   $invite['pid'];
                //var_dump($data);die;
                if(Db::name('personnel_train')->where(['iid'=>$request['iid']])->count() >0){
                    $data['add_time']   =   date('Y-m-d H:i:s',time());
                    Db::name('personnel_train')->where(['pid'=>$invite['pid'],'iid'=>$request['iid']])->update($data);
                    PoolService::operationLog($this->uid,"修改了简历的培训信息",$invite['pid']);
                }else{
                    Db::name('personnel_train')->insert($data);
                    PoolService::operationLog($this->uid,"添加了简历的培训信息",$invite['pid']);
                }
                return $this->success('添加培训成功');
            }
        }
    }
    /**添加培训管理结果
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function trainResult(){
        $pid = $this->request->param('pid');
        $invite =   Db::name('personnel_invite')->where('pid ='.$pid.' and departid is not null')->order("iid desc")->select();
        $list = Db::name('personnel_train')
            ->alias('a')
            ->field('a.*,c.*,b.channel,b.center,b.s_department,b.s_group,d.periods,d.zone as p_zone,d.start_time,d.end_time,d.teacher,d.id as setid')
            ->join('personnel_invite b','a.iid=b.iid','left')
            ->join('user_department c','b.departid=c.id','left')
            ->join('personnerl_train_time_set d','a.periodsid=d.id','left')
            ->where(['a.pid'=>$pid])->select();
        $zone = Db::name('user_department')->field('zone')->group('zone')->select();
        $modifyTrainResult  =   cmf_auth_check($this->uid,'admin/pool/modifyTrainResult');
        return $this->assign('modifyTrainResult',$modifyTrainResult)->assign('data',$list)->assign('invite',$invite)->assign('zone',$zone)->fetch();
    }

    public function modifyTrainResult(){
        $tid    =   $this->request->param('tid');
        $pid    =   $this->request->param('pid');
        if( $tid   && $pid && $this->request->param('is_pass')){
            $data= $this->request->param();
            Db::startTrans();
            try{
                $data   =   array_filter($data);
                $data['update_time'] =  date('Y-m-d H:i:s',time());
                if( Db::name('personnel_train')->where(['tid'=>$tid])->count() > 0){
                    Db::name('personnel_train')->where(['tid'=>$tid])->update($data);
                }else{
                    $re =   Db::name('personnel_train')->insert($data);
                }
                if($this->request->param('is_pass') == '1'){
                    $trainResult = '通过';
                    $pernel = 5;
                }else{
                    $trainResult = '不通过';
                    $pernel =6;
                }
                Db::name('personnel')->where(['pid'=>$pid])->update(['interview_status'=>$pernel]);
                PoolService::operationLog($this->uid,"添加了简历 $pid 的培训结果".$trainResult,$pid);
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $this->error('添加失败');
            }
            return $this->success('添加成功');
        }
    }

    /**上传简历
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function upPersonnel(){
        $data = $this->request->param();
        if($this->request->isPost()){
            $re =   Db::name('personnel')->where(['pid'=>$data['pid']])->update($data);
            return $re ? $this->success('上传成功') : $this->error('上传失败');
        }
        $list = Db::name('personnel')->where(['pid'=>$data['pid']])->find();
        return $this->assign('data',$list)->fetch();
    }
    /**
     * 入职列表
     */
    public function inductionList(){
        $pid    =   $this->request->param('pid');
        $data   =   Db::name('personnel_induction')
            ->alias('a')
            ->field('a.*,b.*,c.channel')
            ->join('user_department b','a.departid=b.id','left')
            ->join('personnel_invite c','a.iid=c.iid','left')
            ->where(['a.pid'=>$pid])->select();
        return $this->assign('data',$data)->fetch();
    }

    /**日志列表
     * @return mixed
     * @throws \think\db\exception\DbException
     */
    public function logList(){
        $req = $this->request->param('pid');
        $data   =   Db::name('personnel_operation_log')
            ->alias('a')
            ->field('a.*,b.user_nickname')
            ->join('user b','a.uid=b.id','left')
            ->order("a.id desc")
            ->where("a.obj=$req")
            ->paginate(['query'=>['pid'=>$req], 'var_page'  => 'page', //分页变量
                'list_rows' => 10]);
        return $this->assign('data',$data)->fetch();
    }

    /**个人信息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function personnelInfo(){
        $pid = $this->request->param('pid');
        $data   =   [];
        if($this->request->post() && $pid){
            $post =$this->request->post();
            //$post = array_filter($post);
            $card   =   $this->request->param('card');
            /*if(!PoolService::validation_filter_id_card($card) && !PoolService::checkOtherIDCard($card)){
                return $this->error('身份证号码错误！');
            }*/
            $id = $this->request->param('id');
            $post['family_member'] = json_encode($post['family_member']);
            $post['educationbackground'] = json_encode($post['educationbackground']);
            $post['work'] = json_encode($post['work']);
            if($id){
                Db::name('personnel_interview')->where(['id'=>$id])->update($post);
                PoolService::operationLog($this->uid,'修改了简历！'.$post['name'],$pid);
            }else{
                Db::name('personnel_interview')->insert($post);
            }
            if($post['phone']){
                if(Db::name('personnel')->where(['p_telphone'=>$post['phone']])->count() > 0){
                    Db::name('personnel')->where(['p_telphone'=>$post['phone']])->update(['p_name'=>$post['name']]);
                }
            }
        }
        if($pid){
            $data   =   Db::name('personnel_interview')
                ->alias('a')
                ->join('personnel b','a.phone=b.p_telphone','left')
                ->where(['b.pid'=>$pid])->find();
        }
        if(!$data){
            $data   =   Db::name('personnel')
                ->alias('a')
                ->join('personnel_interview b','a.p_telphone=b.phone','left')
                ->where(['a.pid'=>$pid])->find();
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

    /**培训管理
     * @return mixed
     *
     */
    public function train(){
        $where = '';
        $request = $this->request->param();
        if(isset($request['genjin_time']) && $request['genjin_time']){
            $where .= $where ? " and a.invite_time like '".$request['genjin_time']."'%" : "a.invite_time like '".$request['genjin_time']."%'";
        }
        if(isset($request['train_date']) && $request['train_date']){
            $where .= $where ? " and f.train_date like '".$request['train_date']."'%" : "f.train_date like '".$request['train_date']."%'";
        }
        if(isset($request['p_name']) && $request['p_name']){
            $where .= $where ? " and e.p_name='".$request['p_name']."'" : "e.p_name='".$request['p_name']."'";
        }
        if(isset($request['p_telphone']) && $request['p_telphone']){
            $where .= $where ? " and e.p_telphone='".$request['p_telphone']."'":"e.p_telphone='".$request['p_telphone']."'";
        }
        if(isset($request['zone']) && $request['zone']){
            $where .= $where ? " and c.zone='".$request['zone']."'" : "c.zone='".$request['zone']."'";
        }
        if(isset($request['company']) && $request['company']){
            $where .= $where ?  " and c.company='".$request['company']."'" : "c.company='".$request['company']."'";
        }
        if(isset($request['name']) && $request['name']){
            $where .= $where? " and c.name='".$request['name']."'" : "c.name='".$request['name']."'";
        }
        if(isset($request['job']) && $request['job']){
            $where .= $where ? " and a.job='".$request['job']."'" : "a.job='".$request['job']."'";
        }
        if(isset($request['periods']) && $request['periods']){
            $where .= $where ? " and g.periods='".$request['periods']."'" : "g.periods='".$request['periods']."'";
        }
        if(isset($request['is_pass']) && $request['is_pass'] !=''){
            $where .= $where ? " and f.is_pass='".$request['is_pass']."'" : "f.is_pass='".$request['is_pass']."'";
        }
        if(isset($request['blang']) && $request['blang']){
            $where .= $where ? " and b.user_nickname like '".$request['blang']."%'" : "b.user_nickname like '".$request['blang']."%'";
        }
        $where .= $where ? " and a.situation=1 and a.i_status=3  and a.interview_time > '2022-04-07 18:00:00'" : "a.situation=1 and a.i_status=3 and a.interview_time > '2022-04-07 18:00:00'";
        if(isset($request['export'])  && $request['export']){
            $data = Db::name('personnel_invite')
                ->alias('a')
                ->field('a.*,c.*,b.user_nickname,b.user_login as code,f.train_date,f.tid,f.is_pass,f.update_time,e.p_name,e.p_telphone,g.start_timsetPeriodse,g.end_time,g.periods')
                ->join('user_department c','a.departid=c.id','left')
                ->join('personnel e','a.pid=e.pid','left')
                ->join('user b','e.uid=b.id','left')
                ->join('personnel_train f','a.iid=f.iid','left')
                ->join('personnerl_train_time_set g','f.periodsid=g.id','left')
                ->where($where)
                ->order('a.iid desc')
                ->select()->toArray();
            PoolService::train($data);
        }
        $list = Db::name('personnel_invite')
            ->alias('a')
            ->field('a.*,c.*,b.user_nickname,f.train_date,f.is_pass,f.update_time,f.periodsid,e.p_name,e.p_telphone,g.start_time,g.end_time')
            ->join('user_department c','a.departid=c.id','left')
            ->join('personnel e','a.pid=e.pid','left')
            ->join('user b','e.uid=b.id','left')
            ->join('personnel_train f','a.iid=f.iid','left')
            ->join('personnerl_train_time_set g','f.periodsid=g.id','left')
            ->where($where)
            ->order('a.iid desc')
            ->paginate(['query'=>$request, //url额外参数
                'list_rows' => 10 ]);
        //echo Db::name('personnel_invite')->getLastSql();die;
        $arr=[];
        foreach ($list as $va){
            $arr[]=$va['pid'];
        }
        $coop = [];
        //var_dump($arr);
        if(!empty($arr)){
            $where1 = join(',',$arr);
            $coop   =   Db::name('personnel_cooperation')
                ->alias('a')
                ->field('a.*,b.user_nickname as coopuser')
                ->join('user b','a.uid =b.id','left')
                ->where('a.pid in('.$where1.') and c_status=1')
                ->select();
        }
        return $this->assign('data',$list)->assign('coop',$coop)->fetch();
    }

    /*
     * 培训记录
     * */
    public function trainRecord(){
        $where = '';
        $request = $this->request->param();
        if(isset($request['genjin_time']) && $request['genjin_time']){
            $where .= $where ? " and a.invite_time like '".$request['genjin_time']."'%" : "a.invite_time like '".$request['genjin_time']."%'";
        }
        if(isset($request['train_date']) && $request['train_date']){
            $where .= $where ? " and f.train_date like '".$request['train_date']."'%" : "f.train_date like '".$request['train_date']."%'";
        }
        if(isset($request['p_name']) && $request['p_name']){
            $where .= $where ? " and e.p_name='".$request['p_name']."'" : "e.p_name='".$request['p_name']."'";
        }
        if(isset($request['p_telphone']) && $request['p_telphone']){
            $where .= $where ? " and e.p_telphone='".$request['p_telphone']."'":"e.p_telphone='".$request['p_telphone']."'";
        }
        if(isset($request['zone']) && $request['zone']){
            $where .= $where ? " and c.zone='".$request['zone']."'" : "c.zone='".$request['zone']."'";
        }
        if(isset($request['company']) && $request['company']){
            $where .= $where ?  " and c.company='".$request['company']."'" : "c.company='".$request['company']."'";
        }
        if(isset($request['name']) && $request['name']){
            $where .= $where? " and c.name='".$request['name']."'" : "c.name='".$request['name']."'";
        }
        if(isset($request['job']) && $request['job']){
            $where .= $where ? " and a.job='".$request['job']."'" : "a.job='".$request['job']."'";
        }
        if(isset($request['periods']) && $request['periods']){
            $where .= $where ? " and g.periods='".$request['periods']."'" : "g.periods='".$request['periods']."'";
        }
        if(isset($request['is_pass']) && $request['is_pass'] !=''){
            $where .= $where ? " and f.is_pass='".$request['is_pass']."'" : "f.is_pass='".$request['is_pass']."'";
        }
        if(isset($request['blang']) && $request['blang']){
            $where .= $where ? " and b.user_nickname like '".$request['blang']."%'" : "b.user_nickname like '".$request['blang']."%'";
        }
        //$where .= $where ? " and a.situation=1 and a.i_status=3  and a.interview_time > '2022-06-06 00:00:00'" : "a.situation=1 and a.i_status=3 and a.interview_time > '2022-06-06 00:00:00'";
        if(isset($request['export'])  && $request['export']){
            $data = Db::name('personnel_train')
                ->alias('f')
                ->field('a.*,c.*,b.user_nickname,b.user_login as code,f.train_date,f.tid,f.is_pass,f.update_time,e.p_name,e.p_telphone,g.start_time,g.end_time,g.periods')
                ->join('personnel_invite a','a.iid=f.iid','left')
                ->join('user_department c','a.departid=c.id','left')
                ->join('personnel e','a.pid=e.pid','left')
                ->join('user b','e.uid=b.id','left')
                ->join('personnerl_train_time_set g','f.periodsid=g.id','left')
                ->where($where)
                ->order('a.iid desc')
                ->select()->toArray();
            PoolService::train($data);
        }
        $list = Db::name('personnel_train')
            ->alias('f')
            ->field('a.*,c.*,b.user_nickname,f.train_date,f.is_pass,f.update_time,f.periodsid,e.p_name,e.p_telphone,g.start_time,g.end_time')
            ->join('personnel_invite a','a.iid=f.iid','left')
            ->join('user_department c','a.departid=c.id','left')
            ->join('personnel e','a.pid=e.pid','left')
            ->join('user b','e.uid=b.id','left')
            ->join('personnerl_train_time_set g','f.periodsid=g.id','left')
            ->where($where)
            ->order('f.tid desc')
            ->paginate(['query'=>$request, //url额外参数
                'list_rows' => 10 ]);
        //echo Db::name('personnel_invite')->getLastSql();die;
        $arr=[];
        foreach ($list as $va){
            $arr[]=$va['pid'];
        }
        $coop = [];
        //var_dump($arr);
        if(!empty($arr)){
            $where1 = join(',',$arr);
            $coop   =   Db::name('personnel_cooperation')
                ->alias('a')
                ->field('a.*,b.user_nickname as coopuser')
                ->join('user b','a.uid =b.id','left')
                ->where('a.pid in('.$where1.') and c_status=1')
                ->select();
        }
        return $this->assign('data',$list)->assign('coop',$coop)->fetch();
    }
    /**入职管理
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function induction(){
        $where = '';
        $request = $this->request->param();
        if(isset($request['induction_date']) && $request['induction_date']){
            $where .= $where ? " and g.induction_date like '".$request['p_name']."'%" : "g.induction_date '".$request['create_time']."%'";
        }
        if(isset($request['genjin_time']) && $request['genjin_time']){
            $where .= $where ? " and f.invite_time like '".$request['genjin_time']."'%" : "f.invite_time '".$request['genjin_time']."%'";
        }
        if(isset($request['p_name']) && $request['p_name']){
            $where .= $where ? " and e.p_name='".$request['p_name']."'" : "e.p_name='".$request['p_name']."'";
        }
        if(isset($request['p_telphone']) && $request['p_telphone']){
            $where .= $where ? " and e.p_telphone='".$request['p_telphone']."'":"e.p_telphone='".$request['p_telphone']."'";
        }
        if(isset($request['zone']) && $request['zone']){
            $where .= $where ? " and c.zone='".$request['zone']."'" : "c.zone='".$request['zone']."'";
        }
        if(isset($request['company']) && $request['company']){
            $where .= $where ?  " and c.company='".$request['company']."'" : "c.company='".$request['company']."'";
        }
        if(isset($request['name']) && $request['name']){
            $where .= $where? " and c.name='".$request['name']."'" : "c.name='".$request['name']."'";
        }
        if(isset($request['job']) && $request['job']){
            $where .= $where ? " and a.job='".$request['job']."'" : "a.job='".$request['job']."'";
        }
        if(isset($request['interview_status']) && $request['interview_status'] !=''){
            if($request['interview_status'] == 7){
                $where .= $where ? " and e.interview_status =".$request['interview_status'] : " e.interview_status =".$request['interview_status'];
            }else{
                $where .= $where ? " and g.inid is  null" : "g.inid is  null";
            }

        }
        if(isset($request['blang']) && $request['blang']){
            $where .= $where ? " and b.user_nickname like '".$request['blang']."%'" : "b.user_nickname like '".$request['blang']."%'";
        }
        $where .= $where ? " and a.is_pass=1" : " a.is_pass=1";
        $where .= $where ? " and g.code IS NOT NULL" : " g.code IS NOT NULL";
        if(isset($request['export']) && $request['export'] == 1){
            $data = Db::name('personnel_train')
                ->alias('a')
                ->field('a.*,c.*,b.user_nickname,f.*,g.induction_date,g.code,e.interview_status,e.p_name,h.card,h.email,h.phone')
                ->join('user_department c','a.departid=c.id','left')
                ->join('personnel e','a.pid=e.pid','left')
                ->join('user b','e.uid=b.id','left')
                ->join('personnel_invite f','a.iid=f.iid','left')
                ->join('personnel_induction g','a.pid=g.pid','left')
                ->join('personnel_interview h','e.p_telphone=h.phone','left')
                ->where($where)
                ->order('a.tid desc')
                ->select()->toArray();
            PoolService::induction($data);
        }
        $list = Db::name('personnel_train')
            ->alias('a')
            ->field('a.*,c.*,b.user_nickname,f.*,g.induction_date,g.code,e.p_telphone,e.interview_status,e.p_name')
            ->join('user_department c','a.departid=c.id','left')
            ->join('personnel e','a.pid=e.pid','left')
            ->join('user b','e.uid=b.id','left')
            ->join('personnel_invite f','a.iid=f.iid','left')
            ->join('personnel_induction g','a.pid=g.pid','left')
            ->where($where)
            ->order('a.tid desc')
            ->paginate(['query'=>$request, //url额外参数
                'list_rows' => 10 ]);
        $arr=[];
        foreach ($list as $va){
            if($va['pid']){
                $arr[]=$va['pid'];
            }
        }
        $coop = [];
        //var_dump($list);die;
        if(!empty($arr)){
            $where1 = join(',',$arr);
            $coop   =   Db::name('personnel_cooperation')
                ->alias('a')
                ->field('a.*,b.user_nickname as coopuser')
                ->join('user b','a.uid =b.id','left')
                ->where('a.pid in('.$where1.') and c_status=1')
                ->select();
        }
        return $this->assign('data',$list)->assign('coop',$coop)->fetch();
    }

    public function doInduction(){
        $request    =   $this->request->param();
        if(isset($request['iid']) && $request['iid']){
            $invite =   Db::name('personnel_train')->where(['iid'=>$request['iid']])->find();
            if($invite){
                $data['induction_date'] =   $request['time'];
                $data['departid'] =   $request['departid'];
                $data['pid'] =   $invite['pid'];
                $data['iid'] =   $request['iid'];
                $data['code'] =  $request['code'];
                Db::startTrans();
                try{//更新入职表信息
                    if(Db::name('personnel_induction')->where(['pid'=>$invite['pid']])->count() >0){
                        Db::name('personnel_induction')->where(['pid'=>$invite['pid']])->update($data);
                    }else{
                        Db::name('personnel_induction')->insert($data);
                    }
                    Db::name('personnel')->where(['pid'=>$invite['pid']])->update(['interview_status'=>7]);//更改简历状态已入职
                    PoolService::operationLog($this->uid,'添加了简历'.$invite['pid'].'的入职时间为：'.$request['time'],$invite['pid']);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return $this->error('添加入职信息失败');
                }

                return $this->success('添加入职信息成功');
            }
            return $this->success('添加入职信息失败');
        }
    }

    /**导入功能
     *  PoolService::exportdepart($filepath='ja1.xlsx') 导入部门
     *  PoolService::exportuser($filepath='user.xlsx') 导入用户
     * @param string $filepath
     */
    public function export(){
        //PoolService::exportdepart('ja1.xlsx');
        //PoolService::exportuser('user.xlsx');
    }

    /**转归属人
     * @throws \think\db\exception\DbException
     */
    public function transction(){
        $array = $this->request->post('arraypid');
        if($array){
            $uid = $this->request->post('uid');
            if(!$uid){
                return $this->error('没有选择归属人');
            }
            $arr = explode(',',$array);
            foreach ($arr as $val){
                Db::name('personnel')->where(['pid'=>$val])->update(['uid'=>$uid]);
                $nickname   =   Db::name('user')->where(['id'=>$uid])->value('user_nickname');
                PoolService::operationLog($this->uid,'转了归属人为'.$nickname,$val);
            }
            return $this->success('success');
        }
    }

    public function delInvite(){
        $iid = $this->request->param('iid');
        if($iid){
            $pid = Db::name('personnel_invite')->where(['iid'=>$iid])->value('pid');
            if(Db::name('personnel_train')->where(['pid'=>$pid])->count() > 0){
                $this->error('删除失败！培训已经开始');
            }
            $re = Db::name('personnel_invite')->where(['iid'=>$iid])->delete();
            PoolService::operationLog($this->uid,$this->user['user_nickname'].'删除了面试记录',$pid);
            if($re){
                return $re ? $this->success('删除成功！') : $this->success('删除失败！');
            }
        }
        $this->success('删除失败！参数错误');
    }

    /**设置面试时间
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setInterview(){
        if( $this->request->isPost()){
            $data = $this->request->param();
            $data['code'] = json_encode($data['code']);
            $data['interviewer'] = json_encode($data['interviewer']);
            $data['uid']    =   $this->uid;
            $start_time = $this->request->param('start_time');
             Db::name('personnel_interview_set')->insert($data);
        }
        //复试面试官列表
        $setlist = Db::name('personnel_interview_set')
            ->alias('a')
            ->field('a.*,b.user_nickname')
            ->join('user b','a.uid=b.id','left')
            ->order('sid desc')->paginate(10);
        $interviewer    =   Db::name('user')
            ->alias('a')
            ->field('a.*,b.role_id')
            ->join('role_user b','a.id=b.user_id','left')
            ->where(['b.role_id'=>8])->select();
        return $this->assign('setlist',$setlist)->assign('interviewer',$interviewer)->fetch();
    }

    public function getSet(){
        $interview_time = $this->request->param('interview_time');
        if($interview_time){
           $data =  Db::name('personnel_interview_set')->where('start_time like '."'$interview_time%'")->select()->toArray();
           if(!$data) {
               return $this->result([]);
           }
               //var_dump($data);die;
           foreach ($data as $key => $val){
               $data[$key]['code']   =   json_decode( $data[$key]['code'] ,true);
               $data[$key]['interviewer']   =   json_decode( $data[$key]['interviewer'] ,true);
           }
            return $this->result($data);
        }
    }

    public function getSetByTime(){
        $sid = $this->request->param('sid');
        if($sid){
            $data =  Db::name('personnel_interview_set')->where(['sid'=>$sid])->select()->toArray();
            if(!$data) {
                return $this->result([]);
            }
            //var_dump($data);die;
            foreach ($data as $key => $val){
                $re['code']   =   json_decode( $data[$key]['code'] ,true);
                $re['interviewer']   =   json_decode( $data[$key]['interviewer'] ,true);
            }
            $uer = join(',',$re['interviewer']);
            $inte = Db::name('user')->field('id,user_login,user_nickname')->where("id in ($uer)")->select();
            $temp=[];
            foreach ( $re['interviewer'] as $val){
                foreach ( $inte as $va){
                    $a = $va['id'];
                    if($a == $val)
                    $temp[] = $va;
                }
            }



            $re['interviewer'] =$temp;
            return $this->result($re);
        }
    }
    public function delSet(){
        $sid = $this->request->param('sid');
        if($sid){
            //$interview_time = Db::name('personnel_interview_set')->where(['sid'=>$sid])->value('start_time');
            if(Db::name('personnel_invite')->where(['sid'=>$sid,'situation'=>2])->count() > 0){
                return $this->error('有面试记录，删除失败！');
            }
            $re = Db::name('personnel_interview_set')->where(['sid'=>$sid])->delete();
                return $re ? $this->success('删除成功！') : $this->success('删除失败！');
        }
        $this->success('删除失败！参数错误');
    }

    /**我的面试列表
     * @return mixed
     */
    public function myInterview(){
        $para   =   [];
        $request = $this->request->param();
        if(isset($request['i_status']) && $request['i_status'] != ''){
            $para[]   =  ['i_status','=', $request['i_status']];
        }
        if(isset($request['interview_time']) && $request['interview_time']){
            $para[]   =  ['a.interview_time','like', $request['interview_time']."%"];
        }
        if(isset($request['p_name']) && $request['p_name']){
            $para[]   =  ['b.p_name','=', $request['p_name']];
        }
        if(isset($request['p_telphone']) && $request['p_telphone']){
            $para[]   =  ['b.p_telphone','=', $request['p_telphone']];
        }
        $para[]  =   ['a.interviewer','=',$this->uid];

        $list   =   Db::name('personnel_invite')
            ->alias('a')
            ->field('a.*,b.p_name,b.p_telphone,c.user_login,c.user_nickname')
            ->join("personnel b",'a.pid=b.pid','left')
            ->join('user c','a.interviewer=c.id','left')
            ->where($para)->paginate(10);
        return $this->assign('data',$list)->fetch();
    }

    public function updateInterviewer(){
        $interviewerid = $this->request->param('interviewerid');
        $iid           = $this->request->param('iid');
        if($interviewerid && $iid){
            Db::startTrans();
            try{
                $invite =    Db::name('personnel_invite')->where(['iid'=>$iid])->find();
                if(!$invite){
                    $this->error('修改失败,无该条记录');
                }
                $re     =   Db::name('personnel_invite')->where(['iid'=>$iid])->update(['interviewer'=>$interviewerid]);
                PersonnelMessageModel::create([
                    'uid'   =>  $interviewerid,
                    'msg_date'   => $invite['interview_time'],
                    'message'    =>  $invite['interview_time'].'您有一场面试！',
                ]);
                $user = Db::name('user')->field('user_login,user_nickname')->where(['id'=>$interviewerid])->find();
                PoolService::operationLog($this->uid,'修改了面试官为'.$user['user_login'].'-'.$user['user_nickname'],$invite['pid']);
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error('修改失败');
            }
           return $re ? $this->success('修改成功') : $this->error('修改失败');
        }
    }

    public function wapPersonnel(){
        if($pid =   $this->request->param('pid')){
            $data   =   Db::name('personnel')
                ->alias('a')
                ->field('a.pid,a.p_telphone,a.p_name,b.*')
                ->join('personnel_interview b','a.p_telphone = b.phone','left')
                ->where(['a.pid'=>$pid])
                ->find();
            $family_member = isset($data['family_member']) && $data['family_member'] ? json_decode($data['family_member'],true):json_decode('{"relat":["",""],"name":["",""],"company":["",""],"address":["",""],"telephone":["",""]}',true);
            $educationbackground = isset($data['educationbackground']) && $data['educationbackground'] ? json_decode($data['educationbackground'],true) : json_decode('{"school":[""],"start":[""],"end":[""],"major":[""],"certificate":[""]}',true);
            $work = isset($data['work']) && $data['work'] ? json_decode($data['work'],true) : json_decode('{"company":["","",""],"start":["","",""],"end":["","",""],"job":["","",""],"pay":["","",""],"reson":["","",""],"witness_phone":["","",""]}',true);
            $family_member = $this->json_de($family_member);
            $educationbackground = $this->json_de($educationbackground);
            $work = $this->json_de($work);
            $nation = PoolService::getNation();
            //var_dump($work);die;
            return $this->assign('family_member',$family_member)
                ->assign('nation',$nation)
                ->assign('educationbackground',$educationbackground)
                ->assign('work',$work)
                ->assign('vo',$data)->fetch();
        }
    }

    public function inviteEdit(){
        $iid    =   $this->request->param('iid');
        if($iid){
            if($this->request->isPost()){
                $para = $this->request->param();
                if($this->request->param('situation') == 2) {
                    $para['interview_time'] = $this->request->param('interview_time_retset');
                    $logtype='复试邀约';
                }else{
                    $para['interview_time'] = $this->request->param('interview_time_test');
                    $logtype='初试试邀约';
                }
                unset($para['interview_time_test']);
                unset($para['interview_time_retset']);
                $result = Db::name('personnel_invite')->where(['iid'=>$iid])->update($para);
                $pid    = Db::name('personnel_invite')->where(['iid'=>$iid])->value('pid');
                PoolService::operationLog($this->uid,'修改了'.$logtype,$pid);
                return $result ? $this->success('更新成功'): $this->error('更新失败，请联系管理员');
            }
            $data   =   Db::name('personnel_invite')
                ->alias('a')
                ->field('a.*,b.p_name,b.p_telphone,c.user_login,c.user_nickname')
                ->join('personnel b','a.pid=b.pid','left')
                ->join('user c','a.interviewer=c.id','left')
                ->where(['a.iid'=>$iid])
                ->find();
            $invite =   (new PersonnelInviteModel())
                ->alias('a')
                ->field('a.*,b.user_nickname as interview_name')
                ->join('user b','a.interviewer=b.id','left')
                ->where(['pid'=>$data['pid']])->select();
            return $this->assign('data',$data)->assign('invite',$invite)->fetch();
        }
    }

    /**
     * 入职跟进
     */
    public function followUp(){
        if($pid =   $this->request->param('pid')){
            if($content = $this->request->param('content')){
                $insert['content']  =   $content;
                $insert['uid']  =   $this->uid;
                $insert['pid']  =   $pid;
                if( Db::name('personnel_followup')->insert($insert)){
                    PoolService::operationLog($this->uid,"添加了入职跟进记录",$pid);
                    return $this->success('跟进成功！');
                }
                return $this->success('跟进失败！请联系管理员');
            }
            $data   =   Db::name('personnel_followup')
                ->field('a.fuid,a.create_date,a.content,b.user_nickname')
                ->alias('a')
                ->join('user b',"a.uid=b.id",'left')
                ->where(['a.pid'=>$pid])->select();
            return $this->assign('data',$data)->fetch();
        }
    }

    /**
     * 管理员设置可选择的培训期数
     * 设置培训时间
     */
    public function setTrainingTime(){
        if($this->request->isPost()){
            $data=$this->request->param();
            $data['uid']    =   $this->uid;
            ;
            if( Db::name('personnerl_train_time_set')->where(['periods'=>$data['periods']])->count() > 0){
                return $this->error('已经创建过相同的期数了！');
            }else{
               if( Db::name('personnerl_train_time_set')->insert($data)){
                   return $this->success('创建成功',url('pool/setTrainingTime'));
               }else{
                   return $this->error('创建失败！请联系管理员');
               }
            }
        }
        $setlist   = Db::name('personnerl_train_time_set')
            ->alias('a')
            ->field('a.*,b.user_nickname')
            ->join('user b','a.uid=b.id','left')
            ->order('id desc')
            ->paginate(10);
        $zone = Db::name('user_department')->field('zone')->group('zone')->select();
        return $this->assign('setlist',$setlist)->assign('zone',$zone)->fetch();
    }

    /**
     * 删除管理员设置可的培训期数
     */
    public function delTrainSet(){
        $id= $this->request->param('sid');
        if($id){
            $data   =   Db::name('personnerl_train_time_set')->where(['id'=>$id])->delete();
            if($data){
                return $this->success('success');
            }
            return $this->error('error');
        }
    }

    /**获取培训时间
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTrainSet(){
        if($zone = $this->request->param('zone')){
            $data   =   Db::name('personnerl_train_time_set')->order('id desc')->where(['zone'=>$zone])->select()->toArray();
            return $this->result($data);
        }
    }

    /**设置面试人员的培训时间
     * @throws \think\db\exception\DbException
     */
    public function setPeriods(){
        $tid = $this->request->param('tid');
        $periods  =   $this->request->param('periods');
        if( $tid &&  $periods){
            $start  =   Db::name('personnerl_train_time_set')->where(['id'=>$periods])->value('start_time');
            $now    =   time();
            $new_start  =   strtotime($start)-15*60*60;
            //echo $start.'###'.$new_start.'###'.$now;die;
            if($now>=$new_start){
                return $this->error('该时段操作时间已过！');
            }
            $data   =   Db::name('personnel_train')
               ->where(['tid'=>$tid])
                ->update(['periodsid'=>$periods]);
            $pid    =    Db::name('personnel_train')->where(['tid'=>$tid])->value('pid');
            PoolService::operationLog($this->uid,'设置了培训时间：'.$start,$pid);
            $this->success('修改成功');
        }
    }

    /**
     * 培训结果导入
     */
    public function exportTrain(){
        $type = $this->request->param('type');
        $info = "";
        if($type){
            if($this->request->isPost()){
                if ($_FILES["file"]["error"] == 0)
                {
                    if (!file_exists("/upload/" . $_FILES["file"]["name"])) {
                        $edn = explode('.', $_FILES["file"]["name"]);
                        $ext = end($edn);
                        $name = "upload/" .date("Ymdhis").'-'.$this->uid.'.'.$ext;
                        move_uploaded_file($_FILES["file"]["tmp_name"], $name);
                        $info = PoolService::exportTrain($name,$this->uid);

                    }
                }
            }
        }
        return $this->assign('info',$info)->fetch();
    }
    /**
     * 添加部门
     * @throws \think\db\exception\DbException
     */
    public function addDepartment(){
        $errormsg   =   '';
        if($this->request->isPost()){
            $post['port']   =   $this->request->param('port');
            $post['zone']   =   $this->request->param('zone');
            $post['company']   =   $this->request->param('company');
            $post['name']   =   $this->request->param('name');
            $post['center']   =   $this->request->param('center');
            $post['s_department']   =   $this->request->param('s_department');
            $post['s_group']   =   $this->request->param('s_group');
            if(trim($post['port'])
                && trim($post['name'])
                && trim($post['company'])
                && trim($post['zone'])
                && trim($post['center'])
                && trim($post['s_department'])
                && trim($post['s_group'])
            ){
                if(Db::name('user_department')->where($post)->count() < 1){
                    $post['uid']    =   $this->uid;
                    $re =   Db::name('user_department')->insert($post);
                    if($re){
                        $errormsg = '添加部门成功！';
                    }
                }else{
                    $re =   Db::name('user_department')->where(['s_group'=>$post['s_group']])->find();
                    $errormsg    =   $re['port'].'-'. $re['zone'].'-'.$re['company'].'-'.$re['name'].'-'.$re['center'].'-'.$re['s_department'].'-'.$re['s_group'];
                    $errormsg ='添加部门失败，已经存在该部门=>'.$errormsg;
                }
            }else{
                $errormsg   =   '添加部门失败，部门架构不能有空';
            }
        }
        $this->assign('errormsg',$errormsg);
        $departlist =   Db::name('user_department')->where(['uid'=>$this->uid])->paginate(10);
        return  $this->assign('departlist',$departlist)->fetch();
    }
    /**
     * 删除部门
     * @throws \think\db\exception\DbException
     */
    public function delDepartment(){
        $departmentid   =   $this->request->param('departmentid');
        if(trim($departmentid)){
            if(Db::name('personnel')->where(['current_departid'=>$departmentid])->count()>0){
                $this->error('删除部门失败，有人员已经入职该部门！');
            }
            if(Db::name('user_department')->where(['uid'=>$this->uid,'id'=>$departmentid])->delete()){
                $this->success('删除部门成功！');
            }else{
                $this->error('删除部门失败，你不是部门创建者！');
            }
        }
        $this->error('删除部门失败，参数数据不正确请联系开发人员！');
    }

    function returnArray($code,$key,$data){
        foreach ($data as $val){
            $company=   $val['company'];
            $code["$company"]["$key"]   =  $val['coun'];
        }
        return $code;
    }
    public function panel(){
        $start  =   $this->request->param('start_time');
        $end  =   $this->request->param('end_time') ? $this->request->param('end_time'). ' 23:59:59':'';
        $request    =   ['start'=>$start,'end'=>$end];
        //按分公司统计简历录入量
        $code   =   [];
        $data_t   =   PoolService::getPersonnelTotalStatisticsByCompany($request);
        $code   =   $this->returnArray($code,'total',$data_t);
        //维护简历数量
        $FirstTestTotal =   PoolService::getFirstTestTotal($request);
        $code   =   $this->returnArray($code,'FirstTestTotal',$FirstTestTotal);
        //初试通过简历
        $data_first =   PoolService::getFirstPassCount($request);
        $code   =   $this->returnArray($code,'FirstPassCount',$data_first);
        //var_dump($FirstTestTotal);die;

        $getRetestCount = PoolService::getRetestCount($request); //复试报名数量
        $code   =   $this->returnArray($code,'getRetestCount',$getRetestCount);
        $getNotRetestCount  =   PoolService::getNotRetestCount($request); //复试未到数量
        $code   =   $this->returnArray($code,'getNotRetestCount',$getNotRetestCount);
        $getPassRetestCount =   PoolService::getPassRetestCount($request); //复试通过数量
        $code   =   $this->returnArray($code,'getPassRetestCount',$getPassRetestCount);
        $getNotPassRetestCount  =   PoolService::getNotPassRetestCount($request); //复试淘汰数量
        $code   =   $this->returnArray($code,'getNotPassRetestCount',$getNotPassRetestCount);
        $getIsRetestCount  =   PoolService::getIsRetestCount($request); //还未复试数量
        $code   =   $this->returnArray($code,'getIsRetestCount',$getIsRetestCount);
        $getLostRetestCount  =   PoolService::getLostRetestCount($request); //邀约失败数量
        $code   =   $this->returnArray($code,'getLostRetestCount',$getLostRetestCount);
        //培训
        $getTrainCount = PoolService::getTrainCount($request);//培训报名
        $code   =   $this->returnArray($code,'getTrainCount',$getTrainCount);
        $getTrainLostCount = PoolService::getTrainLostCount($request);//培训淘汰
        $code   =   $this->returnArray($code,'getTrainLostCount',$getTrainLostCount);
        $getTrainNotArrivedCount = PoolService::getTrainNotArrivedCount($request);//培训未到场delay
        $code   =   $this->returnArray($code,'getTrainNotArrivedCount',$getTrainNotArrivedCount);
        $getTrainDelayCount = PoolService::getTrainDelayCount($request);//培训未到场
        $code   =   $this->returnArray($code,'getTrainDelayCount',$getTrainDelayCount);
        $getTrainSignOutCount = PoolService::getTrainSignOutCount($request);//培训主动退出
        $code   =   $this->returnArray($code,'getTrainSignOutCount',$getTrainSignOutCount);
        $getTrainPassCount = PoolService::getTrainPassCount($request);//培训通过
        $code   =   $this->returnArray($code,'getTrainPassCount',$getTrainPassCount);
        $getInductionPassCount = PoolService::getInductionPassCount($request);//入职人数
        $code   =   $this->returnArray($code,'getInductionPassCount',$getInductionPassCount);
        //echo  Db::name('personnel')->getLastSql();die;
        /*
         * SELECT c.company,count(DISTINCT d.pid) as coun FROM `tp_personnel` `a` LEFT JOIN `tp_user` `b` ON `a`.`importid`=`b`.`id` LEFT JOIN `tp_user_department` `c` ON `b`.`departmentid`=`c`.`id` LEFT JOIN `tp_personnel_invite` `d` ON `d`.`pid`=`a`.`pid` WHERE ( d.situation =2 and a.importid !=0 and a.importid is not NULL ) GROUP BY `c`.`company`
         * */
        //var_dump($code);die;
        foreach ($code as $key => $v){
            //if(isset($v['first_pass'])) {echo $v['company']."分公司";}else{echo $v['company'];}
            if(isset($v['FirstPassCount']) && isset($v['FirstTestTotal'])){
                $rand =   round(($v['FirstPassCount']/$v['FirstTestTotal'])*100,2);
                $code["$key"]['first_pass_lv']   =  $rand;
            }else{
                $code["$key"]['first_pass_lv']   =  0;
            }
            if(isset($v['getRetestCount']) && isset($v['getPassRetestCount']) ){
                $rand =   round(($v['getPassRetestCount']/$v['getRetestCount'])*100,2);
                $code["$key"]['retestLv']   =  $rand;
            }else{
                $code["$key"]['retestLv']   =  0;
            }

            if(isset($v['getTrainPassCount']) && isset($v['getTrainCount']) ){
                $rand =   round(($v['getTrainPassCount']/$v['getTrainCount'])*100,2);
                $code["$key"]['TrainLv']   =  $rand;
            }else{
                $code["$key"]['TrainLv']   =  0;
            }
            if(isset($v['getInductionPassCount']) && isset($v['getRetestCount']) ){
                $rand =   round(($v['getInductionPassCount']/$v['getRetestCount'])*100,2);
                $code["$key"]['InductionLv']   =  $rand;
            }else{
                $code["$key"]['InductionLv']   =  0;
            }
            //if(!$key) $key="Hr人员无所属分公司";

            //echo "分公司：".$key.' 简历量'.$v['total'].' 初试通过：'.$v['first_pass'].' 通过率：'.$rand.'%<br>';
        }
        //print_r($code);die;
        return  $this->assign('code',$code)->fetch();

    }

    public function retestInport(){
        $type = $this->request->param('type');
        $info = "";
        if($type){
            if($this->request->isPost()){
                if ($_FILES["file"]["error"] == 0)
                {
                    if (!file_exists("/upload/" . $_FILES["file"]["name"])) {
                        $edn = explode('.', $_FILES["file"]["name"]);
                        $ext = end($edn);
                        $name = "upload/" .date("Ymdhis").'-'.$this->uid.'.'.$ext;
                        move_uploaded_file($_FILES["file"]["tmp_name"], $name);
                        $info = PoolService::retestInport($name,$this->uid);

                    }
                }
            }
        }
        return $this->assign('info',$info)->fetch();
    }

    public function exportPersonnelTest(){
        //$condition  =   $this->request->param();
        //PoolService::exportPersonnelTest($condition);
        if($this->request->isPost()){
            $condition  =   $this->request->param();
            PoolService::exportPersonnelTest($condition);
        }
        return $this->fetch();
    }
}