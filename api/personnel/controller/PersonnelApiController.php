<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace api\personnel\controller;

use think\App;
use think\Request;
use think\facade\Db;
use cmf\controller\RestBaseController;
use app\admin\service\PoolService;

class PersonnelApiController  extends RestBaseController
{
    public function __construct(?App $app = null)
    {
        parent::__construct($app);
        $request = $this->request->param();
        $sign = $request['sign'];
        $re_sign = $this->sign($request);
        //var_dump($re_sign);
        if($sign != $re_sign){
            $this->error('签名错误!');
        }
    }

    /**
     * 页面列表 用于导航选择
     * @return array
     */
    public function getPersonnel(Request $Request){
        $request    =   $Request->param();
       /* $re_sign = $Request->param('sign');
        if(isset($request['sign'])){
            unset($request['sign']);
        }*/
        $where  =   [];
        if($Request->isPost()){
            if(isset($Request['card'])){
                $where['card']  =   $Request['card'];
            }
            if(isset($Request['phone'])){
                $where['phone']  =   $Request['phone'];
            }
            $data   =   Db::name('personnel_interview')->where($where)->select()->toArray();
            foreach ($data as $key => $val){
                $data["$key"]['family_member']  =   PoolService::json_de(json_decode($data["$key"]['family_member'],true));
                $data["$key"]['educationbackground']  =   PoolService::json_de(json_decode($data["$key"]['educationbackground'],true));
                $data["$key"]['work']  =   PoolService::json_de(json_decode($data["$key"]['work'],true));
            }
            $this->success('请求成功!',$data);
        }else{
            $this->error('请求方式错误!');
        }

    }

    public function sign(&$array){
        if(isset($array['sign'])){
            unset($array['sign']);
        }
        ksort($array);
        $str    =   '';
       foreach ($array as $key => $val){
           $str .= $key.$val;
       }
        $str .= 'DGG962540';
       //echo $str;die;
       //echo md5($str);
       return md5($str);
    }

    /**根据时间返回入职信息 2022-01-04改返回培训通过的人员数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInductionInfo(){
        $data = $this->request->param();
        if((isset($data['induction_date']) && $data['induction_date']) || isset($data['card'])){
            $where[] = ['a.is_pass','=',1];
            if($data['induction_date']){
                $where[] =  ['a.update_time','like',$data['induction_date'].'%'];
            }
            if($data['card']){
                $where[] =  ['d.card','=',$data['card']];
            }
            $re  =  DB::name('personnel_train')
                ->alias('a')
                ->field('a.update_time as induction_date,a.pid,
                d.*,
                c.port,c.framework,c.t_department,c.name as department_name,c.zone,
                c.company,c.center,c.s_department,c.s_group')
                ->join('personnel b','a.pid=b.pid','left')
                ->join('user_department c','b.current_departid=c.id','left')
                ->join('personnel_interview d','d.phone=b.p_telphone','left')
                ->where($where)->select()->toArray();
            foreach ($re as $key => $val){
                if($val['sex'] == 1){
                    $re["$key"]['sex']      =   '男';
                }
                if($val['sex'] == 2){
                    $re["$key"]['sex']      =   '女';
                }
                $re["$key"]['arrivaldate']  =   ($re["$key"]['arrivaldate'] ==  '0000-00-00') ? '' : $re["$key"]['arrivaldate'];
                $re["$key"]['birthday']  =   ($re["$key"]['birthday'] ==  '0000-00-00') ? '' : $re["$key"]['birthday'];
                $re["$key"]['graduation_time']  =   ($re["$key"]['graduation_time'] ==  '0000-00-00') ? '' : $re["$key"]['graduation_time'];
                $re["$key"]['family_member']  =   $this->json_de(json_decode($re["$key"]['family_member'],true));
                $re["$key"]['educationbackground']  =   $this->json_de(json_decode($re["$key"]['educationbackground'],true));
                $re["$key"]['work']  =   $this->json_de(json_decode($re["$key"]['work'],true));
            }
            $this->success('请求成功!',$re);
        }
    }
    public  function json_de($array){
        if(!is_array($array)){
            return "";
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
        foreach ($tem as $k =>$v){
            if(is_array($v)){
               if(count(array_filter($v)) <1){
                   unset($tem[$k]);
               }
            }
            // unset($tem);
        }
        return $tem;
    }

    public function pushStaffCode(){
        $phone = $this->request->param('phone');
        $card = $this->request->param('card');
        $code = $this->request->param('code');
        $induction_date = $this->request->param('induction_date');
        if(!$code){
            $this->error(['code'=>101,'msg'=>'请求失败!无员工号']);
        }
        $info   =   Db::name('personnel')
            ->alias('a')
            ->field('a.pid,a.p_telphone,b.card')
            ->join('personnel_interview b','a.p_telphone=b.phone','left')
            ->where([
                ['a.p_telphone','=',$phone],
                ['b.card','=',$card],
            ])->find();
        //var_dump($info);
        if($info['pid']){
            $personnel_train = Db::name('personnel_train')->field('pid,iid,departid')->where(['pid'=>$info['pid']])->order('tid desc')->find();
            if($personnel_train){//有培训记录
                $data['pid']    =   $personnel_train['pid'];
                $data['iid']    =   $personnel_train['iid'];
                $data['departid']    =   $personnel_train['departid'];
                $data['induction_date']    =   $induction_date;
                //是否已经添加入职记录
                if(Db::name('personnel_induction')->where($data)->count()>0){
                    $data['code']    =   $code;
                    $re = Db::name('personnel_induction')->where($data)->update($data);
                }else{
                    $data['code']    =   $code;
                    $re = Db::name('personnel_induction')->insert($data);
                }
                if($re){
                    Db::name('personnel')->where(['pid'=>$personnel_train['pid']])->update(['interview_status'=>7,'p_status'=>2]);
                }
                $this->success('请求成功！数据更新成功');
            }else{//无培训记录
                $this->error(['code'=>103,'msg'=>'请求失败!无培训记录']);
            }
        }
        //无简历信息
        $this->error(['code'=>101,'msg'=>'请求失败!无该电话的简历记录']);
    }
}
