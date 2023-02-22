<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\service;

use app\admin\model\PersonnelInviteModel;
use app\admin\model\PersonnelMessageModel;
use app\admin\model\PersonnelJob;
use function PHPSTORM_META\type;
use think\facade\Db;
use tree\Tree;
use app\admin\model\PersonnelModel;
use cmf\model\UserModel;
use app\admin\model\RoleUserModel;

class PoolService
{
     public static function logType($type){
          $log = [
             '1'=>'初试',
             '2'=>'复试',
             '3'=>'三面',
             '4'=>'最终面试'
         ];
         return $log["$type"];
     }
    /**返回当前时间过期时间
     * @param int $day  默认7天
     * @return false|string
     *
     */
    public static function getMaintenanceTime($day = 10){
        return date('Y-m-d H:i:s',strtotime("+$day day", time()));
    }
    /**邀约面试服务
     * @param $array
     * @return bool
     */
        public static function addInvite($array){
            // 启动事务
            //var_dump($array);die;
            Db::startTrans();
            try{
                (new PersonnelModel())->where(['pid'=>$array['pid']])->update(['update_time'=>date("Y-m-d H:i:s",time()),'maintenance_time'=>self::getMaintenanceTime()]);
                PersonnelInviteModel::create($array);
                PersonnelMessageModel::create([
                    'uid'   =>  $array['interviewer'],
                    'msg_date'   =>  $array['interview_time'],
                    'message'   =>  $array['interview_time'].'您有一场面试！',
                ]);
                $name = self::logType($array['situation']);
                self::operationLog($array['uid'],'发起了'.$name.'邀约',$array['pid']);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return false;
            }
            return true;
        }

    /**收藏
     * @param $uid
     * @param $pid
     * @return bool|int|string
     */
        public static function doCollect($uid,$pid){
             if(DB::name('personnel_collection')->where(['uid'=>$uid,'pid'=>$pid])->count() > 0){
                 return false;
             }
            return DB::name('personnel_collection')->insertGetId(['uid'=>$uid,'pid'=>$pid]);
        }

    /**获取公司
     * @param null $companyid
     * @return array|\think\Collection|Db[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
        public static function getCompany($companyid=null){
            $where = [];
            if($companyid){
                $where['id']    =   $companyid;
            }
            return DB::name('user_company')->where($where)->order('company_name asc')->select();
        }

    /**获取部门
     * @param null $companyid
     * @param null $departmentid
     * @param null $company_name
     * @return array|\think\Collection|Db[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
        public static function getDepartment($companyid=null,$departmentid=null,$company_name = null){
            $where = [];
            if($departmentid){
                $where['id']    =   $departmentid;
            }
            if($company_name){
                $where['company']    =   $company_name;
            }
            if($companyid){
                return  Db::name('user_department')
                    ->alias('a')
                    ->field('a.*,b.id as companyid')
                    ->join('user_company b','a.company=b.company_name','left')
                    ->where(['b.id'=>$companyid])
                    ->select();
            }
            return DB::name('user_department')->where($where)->order('name asc')->select();
        }

    /**创建职位
     * @param $data
     * @return PersonnelJob|\think\Model
     */
        public static function createJob($data){
            if($data){
                return PersonnelJob::create($data);
            }
        }

    /**职位列表
     * @param $uid
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
        public static function getMyjobList($uid){
            return DB::name('personnel_job')
                ->alias('a')
                ->field('a.*,b.company_name,c.name')
                ->join('user_company b','a.companyid=b.id','left')
                ->join('user_department c','a.departid=c.id','left')
                ->order('name asc')->paginate(10);
        }

    /**x关闭职位
     * @param $jid
     * @return int
     * @throws \think\db\exception\DbException
     */
        public static function closeJob($jid){
            return  DB::name('personnel_job')->where(['jid'=>$jid])->update(['j_status'=>0]);
        }

    /**消息状态更改
     * @param $id
     * @return int
     * @throws \think\db\exception\DbException
     */
        public static function msgRead($id){
            return DB::name('personnel_message')->where(['mid'=>$id])->update(['m_status'=>1]);
        }

    /**
     * @param null $id
     */
        public static function getInterviewList($id = null){
            if($id){
                return DB::name('personnel_interview')->where(['id'=>$id])->order('id desc')->paginate(10);
            }
            return DB::name('personnel_interview')->order('id desc')->paginate(10);
        }

    /**添加合作记录
     * @param $uid
     * @param $pid
     * @return bool|int|string
     */
        public static function addCooperation($uid,$pid=false,$p_telphone = false,$belongid=0){

            if($p_telphone){
               $data    =   Db::name("personnel")->where(['p_telphone'=>$p_telphone])->field('pid,uid')->find();
               if(isset($personenl['uid']) && $personenl['uid'] ==$uid ){
                    return false;
                }
                $pid    =   $data['pid'];
            }

            if($pid){
                if(DB::name('personnel_cooperation')->where(['pid'=>$pid,'uid'=>$uid])->count() > 0){
                    return false;
                }
            }

            //不能自己申请和自己合作
            if($personenl    =   DB::name('personnel')->where(['pid'=>$pid])->find()){
               if(isset($personenl['uid']) || $personenl['uid']){
                    if($personenl['uid'] ==$uid ){
                        return false;
                    }
               }else{
                   return false;
               }
            }
            Db::startTrans();
            try{
                PersonnelMessageModel::create([
                    'uid'   =>  $personenl['uid'],
                    'msg_date'   =>  date('Y-m-d'),
                    'message'   =>  '您有请求合作的消息！',
                ]);
                DB::name('personnel_cooperation')->insert(['pid'=>$pid,'uid'=>$uid,'belongid'=>$belongid]);
               self::operationLog($uid,'请求合作消息',$pid);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return false;
            }
            return true;
        }

    /**获取合作的信息
     * @param $str
     * @return array|\think\Collection|Db[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
        public static function getCooperation($str){
            if($str){
                return DB::name('personnel_cooperation')
                    ->alias('a')
                    ->field('a.*,b.code,b.user_login,b.user_nickname')
                    ->join("user b",'a.uid=b.id','left')
                    ->where('a.pid in('.$str.')')->select();
            }
            return [];
        }

    /**合作确认
     * @param $cid
     * @param $pid
     * @return bool
     */
        public static function doCooperation($cid,$pid,$uid){
            Db::startTrans();
            try{
                Db::name('personnel_cooperation')->where(['pid'=>$pid,'c_status'=>1])->update(['c_status'=>0]);
                Db::name('personnel_cooperation')->where(['cid'=>$cid])->update(['c_status'=>1]);
                self::operationLog($uid,'同意合作消息',$pid);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $e->getMessage();
            }
            return true;
        }

    /**公开简历
     * @param $pid
     * @param $p_status
     * @return int
     * @throws \think\db\exception\DbException
     */
        public static function doPublic($pid,$p_status){
            return  Db::name('personnel')->where(['pid'=>$pid])->update(['p_status'=>$p_status,'uid'=>0]);
        }

    /**合作列表
     * @param $uid
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
        public static function cooperationList($uid){
            return  Db::name('personnel_cooperation')
                ->alias('a')
                ->field('a.*,b.p_name,b.p_telphone,c.user_login,c.user_nickname')
                ->join('personnel b','a.pid=b.pid','left')
                ->join('user c','b.uid=c.id','left')
                ->where("a.uid=$uid")
                ->order('a.cid desc')
                ->paginate(10);
        }
    /**返回excel对象
     * @param $filepath
     * @return PHPExcel
     * @throws \PHPExcel_Reader_Exception
     */
    public static function exportName($header_arr = false,$list,$indexKey,$filename = 'export_name',$startRow=2,$excel2007 = true){
        include_once dirname($_SERVER['DOCUMENT_ROOT'])."/extend/PHPExcel/PHPExcel.php";
        //初始化PHPExcel()
        $objPHPExcel = new \PHPExcel();
        //设置保存版本格式
        if($excel2007){
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
            $filename = $filename.'.xlsx';
        }else{
            $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
            $filename = $filename.'.xls';
        }
        //接下来就是写数据到表格里面去
        $objActSheet = $objPHPExcel->getActiveSheet();
        //设置标题
        foreach ($header_arr as $key => $val){
            $objActSheet->setCellValue($key.'1',$val);
        }
        $array_keys_s    =   array_keys($header_arr);
        //var_dump($header_arr);die;
        //$startRow = 1;
        foreach ($list as $row) {
            foreach ($indexKey as $key => $value){
                if($value == 'sex'){
                    switch ($row[$value]){
                        case 1:
                            $row[$value] = '男';break;
                        case 2:
                            $row[$value] = '女';break;
                    }
                }
                if($value == 'education'){
                    switch ($row[$value]){
                        case 1:
                            $row[$value] = '大专统招';break;
                        case 2:
                            $row[$value] = '本科统招';break;
                        case 3:
                            $row[$value] = '大专非统招';break;
                        case 4:
                            $row[$value] = '本科非统招';break;
                        case 5:
                            $row[$value] = '硕士及以上';break;
                        case 6:
                            $row[$value] = '高中';break;
                        case 7:
                            $row[$value] = '高中及以下';break;
                    }
                }
                //这里是设置单元格的内容
                $objActSheet->setCellValueExplicit($array_keys_s[$key].$startRow,$row[$value],\PHPExcel_Cell_DataType::TYPE_STRING);
            }
            $startRow++;
        }
        // 下载这个表格，在浏览器输出
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename='.$filename.'');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    /**简历拉私
     * @param $pid
     * @param $uid
     * @return int
     * @throws \think\db\exception\DbException
     */
    public static function pullPersonnel($pid,$uid){
        if(Db::name('personnel')->where(['uid'=>$uid,'p_status'=>0,'interview_status'=>0])->count() > 400){
            return false;
        }
        return  Db::name('personnel')->where(['pid'=>$pid,'uid'=>0])->update(['uid'=>$uid,'p_status'=>0,'maintenance_time'=>self::getMaintenanceTime()]);
    }

    public static function operationLog(int $uid,string $log,string $obj=null){
        return  Db::name('personnel_operation_log')->insert(['uid'=>$uid,'log'=>$log,'obj'=>$obj]);
    }

    public static function getAllPort(){
        return Db::name('user_department')->field('port')->group('port')->select();
    }
    public static function getCompanyByZone($zone = false,$port =false){
        if($zone && $port ){
            return Db::name('user_department')->field('company')->where(['zone'=>$zone,'port'=>$port])->group('company')->select();
        }
        return ;
    }
    public static function getCompanyByPort($port = false){
        if($port){
            return Db::name('user_department')->field('zone')->where(['port'=>$port])->group('zone')->select();
        }
        return ;
    }
    public static function getAllDepartment($company = false,$zone,$port){
        return Db::name('user_department')->field('name')->where(['company'=>$company,'zone'=>$zone,'port'=>$port])->group('name')->select();
    }
    public static function getAllCenter($department = false,$company,$zone,$port){
        return Db::name('user_department')->field('center')->where([
            'name'=>$department,
            'company'   =>$company,
            'zone'      =>$zone,
            'port'      =>$port
        ])->group('center')->select();
    }

    public static function getAllSdepartment($center = false,$department,$company,$zone,$port){
        return Db::name('user_department')->field('s_department')->where([
            'center'    =>$center,
            'name'      =>$department,
            'company'   =>$company,
            'zone'      =>$zone,
            'port'      =>$port
        ])->group('s_department')->select();
    }
    public static function getAllSgroup($s_department = false,$center,$department,$company,$zone,$port){
        return Db::name('user_department')->field('s_group')->where([
            's_department'=>$s_department,
            'center'    =>$center,
            'name'      =>$department,
            'company'   =>$company,
            'zone'      =>$zone,
            'port'      =>$port
        ])->group('s_group')->select();
    }

    /**导入部门
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public static function exportdepart($filepath){
        include_once dirname($_SERVER['DOCUMENT_ROOT'])."/extend/PHPExcel/PHPExcel.php";
        header("Content-type: text/html; charset=utf-8");
        error_reporting(E_ALL);
        date_default_timezone_set('Asia/ShangHai');
        /** PHPExcel_IOFactory */
        //$file_temp = dirname(dirname(dirname(__DIR__)));
        //$filename = "2000.xls";
        $file = $filepath;
        if (!file_exists($file)) {
            exit("not found $file.\n");
        }
        $ext_arr= explode(".",$filepath);
        $ext = end($ext_arr);
        if($ext == "xls"){
            $reader = PHPExcel_IOFactory::createReader('Excel5'); //设置以Excel5格式(Excel97-2003工作簿)
        }else if($ext == "xlsx"){
            $reader = new \PHPExcel_Reader_Excel2007();
        }
        //$reader  = new \PHPExcel_Reader_Excel2007();
        $PHPExcel = $reader->load($file); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        //echo $highestRow;die;
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        //ob_end_clean();//清除缓冲区,避免乱码
        /** 循环读取每个单元格的数据 */

        for ($row = 2; $row <= $highestRow; $row++){//行数是以第1行开始
            if(!$sheet->getCell("B".$row)->getValue()){
                continue;
            }
            $data["port"] = $sheet->getCell("A".$row)->getValue();
            $data["framework"] = $sheet->getCell("B".$row)->getValue();
            $data["zone"] = $sheet->getCell("C".$row)->getValue();
            $data["t_department"] = $sheet->getCell("D".$row)->getValue();
            $data["company"] = $sheet->getCell("E".$row)->getValue();
            $data["name"] = $sheet->getCell("F".$row)->getValue();
            $data["center"] = $sheet->getCell("G".$row)->getValue();
            $data["s_department"] = $sheet->getCell("H".$row)->getValue();
            $data["s_group"] = $sheet->getCell("I".$row)->getValue();
            $data["post"] = $sheet->getCell("J".$row)->getValue();
            if(DB::name('user_department')->where($data)->count() > 0){
                continue;
            }
            DB::name('user_department')->insert($data);
        }
    }

    /**导入用户
     * @param $filepath
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public static function exportuser($filepath){
        include_once dirname($_SERVER['DOCUMENT_ROOT'])."/extend/PHPExcel/PHPExcel.php";
        header("Content-type: text/html; charset=utf-8");
        error_reporting(E_ALL);
        date_default_timezone_set('Asia/ShangHai');
        /** PHPExcel_IOFactory */
        //$file_temp = dirname(dirname(dirname(__DIR__)));
        //$filename = "2000.xls";
        $file = $filepath;
        if (!file_exists($file)) {
            exit("not found $file.\n");
        }
        $ext_arr= explode(".",$filepath);
        $ext = end($ext_arr);
        if($ext == "xls"){
            $reader = PHPExcel_IOFactory::createReader('Excel5'); //设置以Excel5格式(Excel97-2003工作簿)
        }else if($ext == "xlsx"){
            $reader = new \PHPExcel_Reader_Excel2007();
        }
        //$reader  = new \PHPExcel_Reader_Excel2007();
        $PHPExcel = $reader->load($file); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        //echo $highestRow;die;
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        //ob_end_clean();//清除缓冲区,避免乱码
        /** 循环读取每个单元格的数据 */

        for ($row = 2; $row <= $highestRow; $row++){//行数是以第1行开始
            if(!$sheet->getCell("B".$row)->getValue()){
                continue;
            }
            $data["code"] = $sheet->getCell("A".$row)->getValue();
            $data["user_nickname"] = $sheet->getCell("B".$row)->getValue();
            $data["job"] = $sheet->getCell("M".$row)->getValue();
            $data['mobile'] =   $sheet->getCell("N".$row)->getValue();

            $con["zone"] = $sheet->getCell("F".$row)->getValue();
            $con["company"] = $sheet->getCell("H".$row)->getValue();
            $con["name"] = $sheet->getCell("I".$row)->getValue();
            $con["center"] = $sheet->getCell("J".$row)->getValue();
            $con["s_department"] = $sheet->getCell("K".$row)->getValue();
            $con["s_group"] = $sheet->getCell("L".$row)->getValue();
            $departid = Db::name('user_department')->where($con)->value('id');
            if(!$departid){
                $departid   =   Db::name('user_department')->insertGetId($con);
            }
            DB::name('user')->where(['code'=>$data["code"]])->update(['departmentid'=>$departid]);
            /*if(DB::name('user')->where(['code'=>$data["code"]])->count() > 0){
                continue;
            }*/
            $data['departmentid']   =   $departid ? $departid : 0;
            unset($departid);
            unset($con);
            //unset($data);
            /*$data['user_pass'] = cmf_password($data['mobile']);
            $data['user_login'] =  $data["code"];

            $role = $sheet->getCell("O".$row)->getValue();
            switch ($role){
                case '录入权限':
                    $roleId = 5;break;
                case '查看权限':
                    $roleId = 7;break;
                case '复试操作权限':
                    $roleId = 6;break;
                case '培训操作权限':
                    $roleId = 4;break;
                case '所有操作权限（除修改权限）':
                    $roleId = 3;break;
                case '所有操作权限':
                    $roleId = 2;break;
                default:
                    $roleId = false;break;
            }
            if(!$roleId){
                continue;
            }
            $userId            = UserModel::strict(false)->insertGetId($data);
            RoleUserModel::insert(["role_id" => $roleId, "user_id" => $userId]);*/
        }
    }

    public static function exportmypersonnel($data){
        $header_arr = [
            'A'=>'姓名',
            'B'=>'电话',
            'C'=>'战区',
            'D'=>'公司',
            'E'=>'事业部',
            'F'=>'职位',
            'G'=>'合作',
            'H'=>'状态',
            'I'=>'录入日期',
            'J'=>'跟进日期',
            'K'=>'归属人',
        ];
        if(gettype($data) != "array"){
            $data = $data->toarray();
        }
        foreach ($data as $key => $val){
            switch ($val['interview_status']){
                case 0:
                    $data[$key]['interview_status'] =   '录入';break;
                case 1:
                    $data[$key]['interview_status'] =   '初试通过';break;
                case 2:
                    $data[$key]['interview_status'] =   '初试淘汰';break;
                case 3:
                    $data[$key]['interview_status'] =   '复试通过';break;
                case 4:
                    $data[$key]['interview_status'] =   '复试淘汰';break;
                case 5:
                    $data[$key]['interview_status'] =   '培训通过';break;
                case 6:
                    $data[$key]['interview_status'] =   '培训淘汰';break;
                case 7:
                    $data[$key]['interview_status'] =   '已入职';break;
                default:
                    $data[$key]['interview_status'] =   '';break;
            }
        }
        $indexKey   =   ['p_name','p_telphone','zone','company','name','apply_job','user_nickname','interview_status','p_create_time','update_time','blang'];
        PoolService::exportName($header_arr,$data,$indexKey);
    }
    public static function exportindex($data){
        $header_arr = [
            'A'=>'姓名',
            'B'=>'电话',
        ];
        $indexKey   =   ['p_name','p_telphone'];
        PoolService::exportName($header_arr,$data,$indexKey);
    }

    public static function preliminaryTest($data,$type='preliminaryTest'){
        $header_arr = [
            'A'=>'姓名',
            'B'=>'电话',
            'C'=>'战区',
            'D'=>'公司',
            'E'=>'事业部',
            'F'=>'职位',
            'G'=>'跟进日期',
            'H'=>'面试日期',
            'I'=>'归属人',
            'J'=>'会议号',
            'K'=>'状态',
            'L'=>'面试人员',
            'M'=>'学历类型',
            'N'=>'生日',
            'O'=>'是否提供学历',
            'P'=>'性别',
            'Q'=>'归属人工号',
            'R'=>'身份证号',
        ];
        $indexKey   =   ['p_name','p_telphone','zone','company',
            'name','apply_job','invite_time','interview_time',
            'blang','number','i_status','interviewer'];
        if(gettype($data) != "array"){
            $data = $data->toarray();
        }
        if($type=='preliminaryTest'){
            $header_arr = array_merge($header_arr,['S'=>'应聘渠道','T'=>'是否在职员工推荐','U'=>'推荐人','V'=>'创建时间']);
            array_push($indexKey,  'education','birthday','is_graduation','sex','code','card','channels','is_recommend','recommend_user','pritime');
            foreach ($data as $key => $val){
                switch ($val['i_status']){
                    case 0:
                        $data[$key]['i_status'] =   '未初试';break;
                    case 1:
                        $data[$key]['i_status'] =   '初复试中';break;
                    case 2:
                        $data[$key]['i_status'] =   '初试淘汰';break;
                    case 3:
                        $data[$key]['i_status'] =   '初试通过';break;
                    case 4:
                        $data[$key]['i_status'] =   '爽约';break;
                }
                if($val['is_recommend'] == ''){
                    $val['is_recommend']    =   '否';
                }
                switch ($val['education']){//'1大专统招，2本科统招，3大专非统招，4本科非统招，5硕士及以上，6高中，7高中及一下',
                    case 1:
                        $data[$key]['education'] =   '大专统招';break;
                    case 2:
                        $data[$key]['education'] =   '本科统招';break;
                    case 3:
                        $data[$key]['education'] =   '大专非统招';break;
                    case 4:
                        $data[$key]['education'] =   '初试通过';break;
                    case 5:
                        $data[$key]['education'] =   '硕士及以上';break;
                    case 6:
                        $data[$key]['education'] =   '高中';break;
                    case 7:
                        $data[$key]['education'] =   '高中及以下';break;
                }
                switch ($val['sex']){//'1大专统招，2本科统招，3大专非统招，4本科非统招，5硕士及以上，6高中，7高中及一下',
                    case 1:
                        $data[$key]['sex'] =   '男';break;
                    case 2:
                        $data[$key]['sex'] =   '女';break;
                }
            }
        }else{
            $header_arr['M'] = '学历类型';
            $header_arr['N'] = '生日';
            $header_arr['O'] = '是否提供学历';
            $header_arr['P'] = '性别';
            $header_arr['Q'] = '归属人工号';
            $header_arr['R'] = '复试结果（复试通过/复试淘汰/未到场';
            array_push($indexKey,'education','birthday','is_graduation','sex','code');
            foreach ($data as $key => $val){
                switch ($val['i_status']){
                    case 0:
                        $data[$key]['i_status'] =   '待复试';break;
                    case 1:
                        $data[$key]['i_status'] =   '复试中';break;
                    case 2:
                        $data[$key]['i_status'] =   '复试淘汰';break;
                    case 3:
                        $data[$key]['i_status'] =   '复试通过';break;
                    case 4:
                        $data[$key]['i_status'] =   '未到场';break;
                }
                switch ($val['education']){
                    case '1':
                        $data[$key]['education'] =   '大专统招';break;
                    case '2':
                        $data[$key]['education'] =   '本科统招';break;
                    case '3':
                        $data[$key]['education'] =   '大专非统招';break;
                    case '4':
                        $data[$key]['education'] =   '本科非统招';break;
                    case '5':
                        $data[$key]['education'] =   '硕士及以上';break;
                    case '6':
                        $data[$key]['education'] =   '高中';break;
                    case '7':
                        $data[$key]['education'] =   '高中及以下';break;
                    default:
                        $data[$key]['education'] =   '';break;
                }
            }
        }

        PoolService::exportName($header_arr,$data,$indexKey);
    }
    public static function retest($data){
        $header_arr = [
            'A'=>'姓名',
            'B'=>'电话',
            'C'=>'战区',
            'D'=>'公司',
            'E'=>'事业部',
            'F'=>'职位',
            'G'=>'跟进日期',
            'H'=>'面试日期',
            'I'=>'归属人',
        ];
        $indexKey   =   ['p_name','p_telphone','zone','company','name','job','invite_time','interview_time','blang'];
        PoolService::exportName($header_arr,$data,$indexKey);
    }

    public static function train($data){
        //var_dump($data);die;
        $header_arr = [
            'A'=>'ID(不可更改)',
            'B'=>'姓名',
            'C'=>'电话',
            'D'=>'战区',
            'E'=>'公司',
            'F'=>'事业部',
            'G'=>'职位',
            'H'=>'跟进日期',
            'I'=>'面试日期',
            'J'=>'归属人',
            'K'=>'培训期数',
            'L'=>'培训结果(培训通过/培训未到场/培训淘汰/主动退出)',
            'M'=>'归属人工号',
            'N'=>'培训时间',
            'O'=>'培训通过(更新)时间'
        ];
        $indexKey   =   ['tid','p_name','p_telphone','zone','company','name','job','invite_time','interview_time','user_nickname','periods','is_pass','code','train_time','update_time'];
        foreach ($data as $key => $val){
            switch ($val['is_pass']){
                case 1:
                    $data[$key]['is_pass'] =   '培训通过';break;
                case 2:
                    $data[$key]['is_pass'] =   '培训淘汰';break;
                case 3:
                    $data[$key]['is_pass'] =   '培训未到场';break;
                case 4:
                    $data[$key]['is_pass'] =   '主动退出';break;
                case '0':
                    $data[$key]['is_pass'] =   '待培训';break;
                case 10:
                    $data[$key]['is_pass'] =   '无法识别';break;
                default:
                    $data[$key]['is_pass'] =   '未添加培训';break;
            }
            $data[$key]['train_time']   =   $data[$key]['start_time'].'-'. $data[$key]['end_time'];
        }
        PoolService::exportName($header_arr,$data,$indexKey);
    }
    public static function induction($data){
        $header_arr = [
            'A'=>'PID',
            'B'=>'跟进日期',
            'C'=>'是否通过',
            'D'=>'战区',
            'E'=>'分公司',
            'F'=>'事业部',
            'G'=>'中心',
            'H'=>'部门',
            'I'=>'组别',
            'J'=>'归属人',
            'K'=>'时间',
            'L'=>'员工号',
            'M'=>'名字',
            'N'=>'电话',
            'O'=>'身份证',
            'P'=>'邮箱',
            'Q'=>'归属人工号',
            'R'=>'应聘渠道',
            'S'=>'是否在职员工推荐',
            'T'=>'推荐人'
        ];
        foreach ($data as $key => $val){
            switch ($val['interview_status']){
                case 7:
                    $data[$key]['interview_status'] =   '已经入职';break;
                case 5:
                    $data[$key]['interview_status'] =   '培训通过';break;
                case 6:
                    $data[$key]['interview_status'] =   '培训淘汰';break;
                case 4:
                    $data[$key]['interview_status'] =   '复试淘汰';break;
                case 3:
                    $data[$key]['interview_status'] =   '复试通过';break;
                case 2:
                    $data[$key]['interview_status'] =   '面试淘汰';break;
                case 1:
                    $data[$key]['interview_status'] =   '面试通过';break;
                case 0:
                    $data[$key]['interview_status'] =   '面试中';break;
                default:
                    $data[$key]['interview_status'] =   '';break;

            }
            if($val['is_recommend'] !='是'){
                $data[$key]['is_recommend'] =  '否';
            }

        }
        $indexKey   =   ['pid','interview_time','interview_status','zone','company','name','center',
            's_department','s_group','user_nickname','induction_date','code','p_name','phone','card',
            'email','pcode','channels','is_recommend','recommend_user'];
        PoolService::exportName($header_arr,$data,$indexKey);
    }

    /**返回民族列表
     * @return array
     */
    public static function getNation(){
        return  [
            "汉族",
            "壮族",
            "满族",
            "回族",
            "苗族",
            "维吾尔族",
            "土家族",
            "彝族",
            "蒙古族",
            "藏族",
            "布依族",
            "侗族",
            "瑶族",
            "朝鲜族",
            "白族",
            "哈尼族",
            "哈萨克族",
            "黎族",
            "傣族",
            "畲族",
            "傈僳族",
            "仡佬族",
            "东乡族",
            "高山族",
            "拉祜族",
            "水族",
            "佤族",
            "纳西族",
            "羌族",
            "土族",
            "仫佬族",
            "锡伯族",
            "柯尔克孜族",
            "达斡尔族",
            "景颇族",
            "毛南族",
            "撒拉族",
            "布朗族",
            "塔吉克族",
            "阿昌族",
            "普米族",
            "鄂温克族",
            "怒族",
            "京族",
            "基诺族",
            "德昂族",
            "保安族",
            "俄罗斯族",
            "裕固族",
            "乌孜别克族",
            "门巴族",
            "鄂伦春族",
            "独龙族",
            "塔塔尔族",
            "赫哲族",
            "珞巴族"
        ];
    }

    public  static function getPersonnelStatus($key =   null){
        $array  =   [
                "0" =>'面试',
                "1" =>'初试通过',
                "2" =>'初试淘汰',
                "3" =>'复试通过',
                "4" =>'复试淘汰',
                "5" =>'培训通过',
                "6" =>'培训淘汰',
                "7" =>'已入职',
            ];
       return $key ? $array["$key"] : $array;
    }

    /**人员家庭 教育工作信息转遍历数组
     * @param $array
     * @return array|bool
     */
    public static function json_de($array){
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

    public static function getInterviewByPhone($phone){
        $data   =   Db::name('personnel')
            ->alias('a')
            ->join('personnel_interview b','a.p_telphone=b.phone','left')
            ->where(['b.phone'=>$phone])->find();
        if(!$data){
            return ;
        }
        $family_member = isset($data['family_member']) && $data['family_member'] ? json_decode($data['family_member'],true):json_decode('{"relat":["",""],"name":["",""],"company":["",""],"address":["",""],"telephone":["",""]}',true);
        $educationbackground = isset($data['educationbackground']) && $data['educationbackground'] ? json_decode($data['educationbackground'],true) : json_decode('{"school":[""],"start":[""],"end":[""],"major":[""],"certificate":[""]}',true);
        $work = isset($data['work']) && $data['work'] ? json_decode($data['work'],true) : json_decode('{"company":["","",""],"start":["","",""],"end":["","",""],"job":["","",""],"pay":["","",""],"reson":["","",""],"witness_phone":["","",""]}',true);
        $data['family_member'] = self::json_de($family_member);
        $data['educationbackground'] =  self::json_de($educationbackground);
        $data['work'] =  self::json_de($work);
        return $data;
    }

    public static function exportTrain($filepath,$uid){
        $PHPExcel = self::getExcelObj($filepath); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        //echo $highestRow;die;
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        $info='';
        /** 循环读取每个单元格的数据 */
        //Db::startTrans();
        try{
            for ($row = 2; $row <= $highestRow; $row++){//行数是以第1行开始
                if(!$sheet->getCell("A".$row)->getValue()){
                    continue;
                }
                $data["tid"] = $sheet->getCell("A".$row)->getValue();
                $personnel["p_name"] = $sheet->getCell("B".$row)->getValue();
                $personnel["p_telphone"] = $sheet->getCell("C".$row)->getValue();
                $data['pid'] = Db::name('personnel')->where($personnel)->value('pid');
                $resu = $sheet->getCell("L".$row)->getValue();
                switch (trim($resu)){
                    case '培训通过':
                        $up['is_pass'] = 1;break;
                    case '培训未到场':
                        $up['is_pass'] = 3;break;
                    case '培训淘汰':
                        $up['is_pass'] = 2;break;
                    case '主动退出':
                        $up['is_pass'] = 4;break;
                    default:
                        $up['is_pass'] = 10;break;
                }
                $up['update_time']  =   date('Y-m-d H:i:s',time());
                $up['actualperiods']  =   $sheet->getCell("K".$row)->getValue();
                if( Db::name("personnel_train")->where($data)->update($up)){
                    if($up['is_pass'] == 1) $interview_status = 5;
                    if($up['is_pass'] == 2) $interview_status = 6;
                    if(isset($interview_status)){
                        Db::name('personnel')->where($personnel)->update(['interview_status'=>$interview_status]);
                    }
                    self::operationLog($uid,'修改了培训结果:'.$resu,$data['pid']);
                    $info .=  '<span>'.$personnel["p_name"]."更新成功</span><br>";
                }else{
                    $info .= '<span style="color:red">'.$personnel["p_name"]."更新失败!</span><br>".'培训关联条件'.json_encode($data).'更新信息'.json_encode($up);
                }
                unset($data);
                unset($resu);
                unset($up);
               // unlink($filepath);
            }
           // Db::commit();
        }catch (\think\Exception $e){
            //Db::rollback();
            echo $e->getMessage();
            //echo $e->getMessage();
        }
        return $info;
    }

    public static function getExcelObj($filepath){
        include_once dirname($_SERVER['DOCUMENT_ROOT'])."/extend/PHPExcel/PHPExcel.php";
        header("Content-type: text/html; charset=utf-8");
        error_reporting(E_ALL);
        date_default_timezone_set('Asia/ShangHai');
        /** PHPExcel_IOFactory */
        $file_temp = $_SERVER['DOCUMENT_ROOT'] ;
        //$file = $file_temp."/public/".$filepath;
        $file = $file_temp."/".$filepath;
        if (!file_exists($file)) {
            exit("not found $file.\n");
        }
        $ext_arr= explode(".",$filepath);
        $ext = end($ext_arr);
        if($ext == "xls"){
            $reader = PHPExcel_IOFactory::createReader('Excel5'); //设置以Excel5格式(Excel97-2003工作簿)
        }else if($ext == "xlsx"){
            $reader = new \PHPExcel_Reader_Excel2007();
        }
        //$reader  = new \PHPExcel_Reader_Excel2007();
        $PHPExcel = $reader->load($file); // 载入excel文件
        return $PHPExcel;
    }

    /**验证简历信息
     * @param $pid
     */
    public static function authPersonnl($pid){
        $personnerl =  Db::name('personnel')
            ->alias('a')
            ->field('a.pid,b.*')
            ->join('personnel_interview b','a.p_telphone=b.phone')
            ->where(['a.pid'=>$pid])->find();
          //Db::name('personnel_interview')->where(['phone'=>$phone])->find();
        if(!$personnerl){
            return ['status'=>"fail",'msg'=>"无简历信息"];
        }
        if(!$personnerl['card']){
            return ['status'=>"fail",'msg'=>"无身份证信息"];
        }
        if(!$personnerl['email']){
            return ['status'=>"fail",'msg'=>"无邮箱信息"];
        }
        if(!$personnerl['education']){
            return ['status'=>"fail",'msg'=>"无学历类型"];
        }
        if(!$personnerl['birthday'] || $personnerl['birthday']   ==  '0000-00-00'){
            return ['status'=>"fail",'msg'=>"无生日时间"];
        }
        if(!$personnerl['huji']){
            return ['status'=>"fail",'msg'=>"无户籍"];
        }
        if(!$personnerl['school']){
            return ['status'=>"fail",'msg'=>"无学校信息"];
        }
        if(!$personnerl['contacts']){
            return ['status'=>"fail",'msg'=>"无紧急联系人"];
        }
        if(!$personnerl['contacts_phone']){
            return ['status'=>"fail",'msg'=>"无紧急联系人电话"];
        }
        if(!$personnerl['family_member']){
          return ['status'=>"fail",'msg'=>"无家庭信息"];
        }
       if(!$personnerl['educationbackground']){
          return ['status'=>"fail",'msg'=>"无教育信息"];
       }
        return ['status'=>"success",'msg'=>"更改信息成功！"];
    }
    public static function is_idcard( $id )
    {
        $id = strtoupper($id);
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arr_split = array();
        if(!preg_match($regx, $id))
        {
            return FALSE;
        }
        if(15==strlen($id)) //检查15位
        {
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";

            @preg_match($regx, $id, $arr_split);
            //检查生日日期是否正确
            $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
            if(!strtotime($dtm_birth))
            {
                return FALSE;
            } else {
                return TRUE;
            }
        }
        else      //检查18位
        {
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match($regx, $id, $arr_split);
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
            if(!strtotime($dtm_birth)) //检查生日日期是否正确
            {
                return FALSE;
            }
            else
            {
                //检验18位身份证的校验码是否正确。
                //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                $sign = 0;
                for ( $i = 0; $i < 17; $i++ )
                {
                    $b = (int) $id{$i};
                    $w = $arr_int[$i];
                    $sign += $b * $w;
                }
                $n = $sign % 11;
                $val_num = $arr_ch[$n];
                if ($val_num != substr($id,17, 1))
                {
                    return FALSE;
                } //phpfensi.com
                else
                {
                    return TRUE;
                }
            }
        }

    }
    public static function validation_filter_id_card($id_card){
        if(strlen($id_card)==18){
            return self::idcard_checksum18($id_card);
        }elseif((strlen($id_card)==15)){
            $id_card=self::idcard_15to18($id_card);
            return self::idcard_checksum18($id_card);
        }else{
            return false;
        }
    }
    public static function idcard_verify_number($idcard_base){
        if(strlen($idcard_base)!=17){
            return false;
        }
        //加权因子
        $factor=array(7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
        //校验码对应值
        $verify_number_list=array('1','0','X','9','8','7','6','5','4','3','2');
        $checksum=0;
        for($i=0;$i<strlen($idcard_base);$i++){
            $checksum += substr($idcard_base,$i,1) * $factor[$i];
        }
        $mod=$checksum % 11;
        $verify_number=$verify_number_list[$mod];
        return $verify_number;
    }
// 将15位身份证升级到18位
    public static function idcard_15to18($idcard){
        if(strlen($idcard)!=15){
            return false;
        }else{
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if(array_search(substr($idcard,12,3),array('996','997','998','999')) !== false){
                $idcard=substr($idcard,0,6).'18'.substr($idcard,6,9);
            }else{
                $idcard=substr($idcard,0,6).'19'.substr($idcard,6,9);
            }
        }
        $idcard=$idcard.idcard_verify_number($idcard);
        return $idcard;
    }
// 18位身份证校验码有效性检查
    public static function idcard_checksum18($idcard){
        if(strlen($idcard)!=18){
            return false;
        }
        $idcard_base=substr($idcard,0,17);
        if(self::idcard_verify_number($idcard_base)!=strtoupper(substr($idcard,17,1))){
            return false;
        }else{
            return true;
        }
    }

    //按公司分组统计录入简历总数
    public static function getPersonnelTotalStatisticsByCompany($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "a.create_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and a.create_time<='".$request['end']."'" : " a.create_time<='".$request['end']."'";
        }
        //echo $where;die;
     return   Db::name('personnel')
            ->alias('a')
            ->field('c.port,c.zone,c.company,count(DISTINCT a.pid) as coun')
            ->join('user b','a.importid=b.id','left')
            ->join('user_department c','b.departmentid=c.id','left')
            ->where('a.importid !=0 and a.importid is not NULL')
            ->where($where)
            ->group('c.company')
            ->select()->toArray();
    }
    //按公司分组统计维护简历总数
    public static function getFirstTestTotal($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "a.create_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and a.create_time<='".$request['end']."'" : " a.create_time<='".$request['end']."'";
        }
        return Db::name('personnel')
            ->alias('a')
            ->field('c.company,count(DISTINCT a.pid) as coun')
            ->join('user b','a.uid=b.id','left')
            ->join('user_department c','b.departmentid=c.id','left')
            ->where($where)
            ->group('c.company')
            ->select();
    }

    //按公司分组统计初试通过简历总数
    public static function getFirstPassCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "a.create_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and a.create_time<='".$request['end']."'" : " a.create_time<='".$request['end']."'";
        }
        return Db::name('personnel')
            ->alias('a')
            ->field('c.company,count(DISTINCT a.pid) as coun')
            ->join('user b','a.uid=b.id','left')
            ->join('user_department c','b.departmentid=c.id','left')
            ->where('a.interview_status!=0 and a.interview_status!=2')
            ->where($where)
            ->group('c.company')
            ->select();
    }
    //按公司分组统计复试简历总数
    public static function getRetestCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "a.interview_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and a.interview_time<='".$request['end']."'" : " a.interview_time<='".$request['end']."'";
        }
        return Db::name('personnel_invite')
            ->alias('a')
            ->field('c.company,count(a.pid) as coun')
            ->join('user_department c','a.departid=c.id','left')
            ->where('a.situation =2')
            ->where($where)
            ->group('c.company')
            ->select();
    }
    //按公司分组统计爽约简历总数
    public static function getNotRetestCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "a.interview_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and a.interview_time<='".$request['end']."'" : " a.interview_time<='".$request['end']."'";
        }
        return Db::name('personnel_invite')
            ->alias('a')
            ->field('c.company,count(a.pid) as coun')
            ->join('user_department c','a.departid=c.id','left')
            ->where('a.situation =2 and a.i_status=4')
            ->where($where)
            ->group('c.company')
            ->select();
    }
    //按公司分组统计复试简历通过总数
    public static function getPassRetestCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "a.interview_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and a.interview_time<='".$request['end']."'" : " a.interview_time<='".$request['end']."'";
        }
        return Db::name('personnel_invite')
            ->alias('a')
            ->field('c.company,count(a.pid) as coun')
            ->join('user_department c','a.departid=c.id','left')
            ->where('a.situation =2 and a.i_status=3')
            ->where($where)
            ->group('c.company')
            ->select();
    }
    //按公司分组统计复试简历未通过总数
    public static function getNotPassRetestCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "a.interview_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and a.interview_time<='".$request['end']."'" : " a.interview_time<='".$request['end']."'";
        }
        return Db::name('personnel_invite')
            ->alias('a')
            ->field('c.company,count(a.pid) as coun')
            ->join('user_department c','a.departid=c.id','left')
            ->where('a.situation =2 and a.i_status=2')
            ->where($where)
            ->group('c.company')
            ->select();
    }
    //按公司分组统计复试简历未面试总数
    public static function getIsRetestCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "a.interview_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and a.interview_time<='".$request['end']."'" : " a.interview_time<='".$request['end']."'";
        }
        return Db::name('personnel_invite')
            ->alias('a')
            ->field('c.company,count(a.pid) as coun')
            ->join('user_department c','a.departid=c.id','left')
            ->where('a.situation =2 and a.i_status=0')
            ->where($where)
            ->group('c.company')
            ->select();
    }
    //按公司分组统计复试简历邀约失败数量
    public static function getLostRetestCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "a.interview_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and a.interview_time<='".$request['end']."'" : " a.interview_time<='".$request['end']."'";
        }
        return Db::name('personnel_invite')
            ->alias('a')
            ->field('c.company,count(a.pid) as coun')
            ->join('user_department c','a.departid=c.id','left')
            ->where('a.situation =2 and a.i_status=5')
            ->where($where)
            ->group('c.company')
            ->select();
    }
    //按公司分组统计培训数量
    public static function getTrainCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "d.start_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and d.start_time<='".$request['end']."'" : " d.start_time<='".$request['end']."'";
        }
        return Db::name('personnel_train')
            ->alias('a')
            ->field('c.company,count(a.pid) as coun')
            ->join('personnel_invite b','a.iid=b.iid','left')
            ->join('user_department c','b.departid=c.id','left')
            ->join('personnerl_train_time_set d','a.periodsid=d.id','left')
            ->where($where)
            //->where('a.situation =2 and a.i_status=5')
            ->group('c.company')
            ->select();
    }
    //培训淘汰
    public static function getTrainLostCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "d.start_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and d.start_time<='".$request['end']."'" : " d.start_time<='".$request['end']."'";
        }
        return Db::name('personnel_train')
            ->alias('a')
            ->field('c.company,count(a.pid) as coun')
            ->join('personnel_invite b','a.iid=b.iid','left')
            ->join('user_department c','b.departid=c.id','left')
            ->join('personnerl_train_time_set d','a.periodsid=d.id','left')
            ->where($where)
            ->where('a.is_pass =2')
            ->group('c.company')
            ->select();
    }
    //培训未到场
    public static function getTrainNotArrivedCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "d.start_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and d.start_time<='".$request['end']."'" : " d.start_time<='".$request['end']."'";
        }
        return Db::name('personnel_train')
            ->alias('a')
            ->field('c.company,count(a.pid) as coun')
            ->join('personnel_invite b','a.iid=b.iid','left')
            ->join('user_department c','b.departid=c.id','left')
            ->join('personnerl_train_time_set d','a.periodsid=d.id','left')
            ->where($where)
            ->where('a.is_pass =3')
            ->group('c.company')
            ->select();
    }
    //培训延期
    public static function getTrainDelayCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "d.start_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and d.start_time<='".$request['end']."'" : " d.start_time<='".$request['end']."'";
        }
        return Db::name('personnel_train')
            ->alias('a')
            ->field('c.company,count(a.pid) as coun')
            ->join('personnel_invite b','a.iid=b.iid','left')
            ->join('user_department c','b.departid=c.id','left')
            ->join('personnerl_train_time_set d','a.periodsid=d.id','left')
            ->where($where)
            ->where('a.is_pass =5')
            ->group('c.company')
            ->select();
    }
    //培训主动退出
    public static function getTrainSignOutCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "d.start_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and d.start_time<='".$request['end']."'" : " d.start_time<='".$request['end']."'";
        }
        return Db::name('personnel_train')
            ->alias('a')
            ->field('c.company,count(a.pid) as coun')
            ->join('personnel_invite b','a.iid=b.iid','left')
            ->join('user_department c','b.departid=c.id','left')
            ->join('personnerl_train_time_set d','a.periodsid=d.id','left')
            ->where($where)
            ->where('a.is_pass =4')
            ->group('c.company')
            ->select();
    }
    //培训通过
    public static function getTrainPassCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "d.start_time>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and d.start_time<='".$request['end']."'" : " d.start_time<='".$request['end']."'";
        }
        return Db::name('personnel_train')
            ->alias('a')
            ->field('c.company,count(a.pid) as coun')
            ->join('personnel_invite b','a.iid=b.iid','left')
            ->join('user_department c','b.departid=c.id','left')
            ->join('personnerl_train_time_set d','a.periodsid=d.id','left')
            ->where($where)
            ->where('a.is_pass =1')
            ->group('c.company')
            ->select();
    }

    //入职人数
    public static function getInductionPassCount($request){
        $where  =   '';
        if(isset($request['start']) && $request['start']){
            $where .= "a.induction_date>='".$request['start']."'";
        }
        if(isset($request['end']) && $request['end']){
            $where .= $where ? " and a.induction_date<='".$request['end']."'" : " a.induction_date<='".$request['end']."'";
        }
        return Db::name('personnel_induction')
            ->alias('a')
            ->field('c.company,count(distinct(a.pid)) as coun')
            ->join('user_department c','a.departid=c.id','left')
            ->where($where)
            ->group('c.company')
            ->select();
    }

    public static function retestInport($filepath,$uid){
        $PHPExcel = self::getExcelObj($filepath); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        //echo $highestRow;die;
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        $info='';
        /** 循环读取每个单元格的数据 */
        //Db::startTrans();
        try{
            for ($row = 2; $row <= $highestRow; $row++) {//行数是以第1行开始
                if (!$sheet->getCell("A" . $row)->getValue()) {
                    continue;
                }
                $personnel["p_name"] = $sheet->getCell("A" . $row)->getValue();
                $personnel["p_telphone"] = $sheet->getCell("B" . $row)->getValue();
                $data['pid'] = Db::name('personnel')->where($personnel)->value('pid');
                $data['interview_time'] = $sheet->getCell("H" . $row)->getValue();
                $data['number'] = $sheet->getCell("J" . $row)->getValue();
                $resu = $sheet->getCell("R" . $row)->getValue();
                switch ($resu) {
                    case '复试通过':
                        $up['i_status'] = 3;
                        break;
                    case '复试淘汰':
                        $up['i_status'] = 2;
                        break;
                    case '未到场':
                        $up['i_status'] = 4;
                        break;
                    default:
                        $up['i_status'] = 10;
                        break;
                }
                $up['conclusion']   =    $sheet->getCell("S" . $row)->getValue();
                if (Db::name("personnel_invite")->where($data)->value('i_status') != $up['i_status']) {
                    if (Db::name("personnel_invite")->where($data)->update($up)) {
                        if ($up['i_status'] == 3) $interview_status = 3;
                        if ($up['i_status'] == 2) $interview_status = 4;
                        if (isset($interview_status)) {
                            Db::name('personnel')->where(['pid' => $data['pid']])->update(['interview_status' => $interview_status]);
                        }
                        unset($interview_status);
                        self::operationLog($uid, '修改了复试结果:' . $resu, $data['pid']);
                        $info .= '<span>' . $personnel["p_name"] . "更新成功</span><br>";
                    } else {
                        $info .= '<span style="color:red">' . $personnel["p_name"] . "更新失败!</span><br>" . '培训关联条件' . json_encode($data) . '更新信息' . json_encode($up);
                    }
                    unset($data);
                    unset($resu);
                    unset($up);
                    // unlink($filepath);
                }
            }
            // Db::commit();
        }catch (\think\Exception $e){
            //Db::rollback();
            echo $e->getMessage();
            //echo $e->getMessage();
        }
        return $info;
    }

    public static function getUsridByRole($role){
         $array   =   Db::name('role_user')
            ->field('user_id')
            ->where(['role_id'=>$role])
            ->select()->toArray();
         $temp  =   [];
        foreach ($array as $val){
            $temp[] =   $val['user_id'];
        }
        return  $temp;
    }

    /**
     * 验证其他身份证号，港澳台身份证
     * @param $IDCard
     * @return bool
     */
    public static function checkOtherIDCard($IDCard)
    {
        $IDCard = strtoupper($IDCard);
        $IDCard = str_replace(array('（', '）'), array('(', ')'), $IDCard);
        preg_match('/^([A-Z])([\d]{6})\(([A\d])\)$/', $IDCard, $hongkong);//香港
        if ($hongkong && count($hongkong) === 4) {
            $sum = (ord($hongkong[1]) - 64) * 8;
            $index = 7;
            for ($j = 0; $j < 6; $j++) {
                $sum += $hongkong[2]{$j} * $index;
                $index--;
            }
            $get_num = $sum % 11;
            if ($get_num === 1) {
                $get_num = 'A';
            } elseif ($get_num > 1) {
                $get_num = 11 - $get_num;
            }

            if ($hongkong[3] === $get_num) {
                return true;
            }
            return false;
        }
        preg_match('/^([A-Z])([\d]{9})$/', $IDCard, $taiwan);//中国台湾省
        if ($taiwan && count($taiwan) === 3)//首位数字代表性别，男性为1、女性为2
        {
            $area_code = array('A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14, 'F' => 15, 'G' => 16, 'H' => 17, 'I' => 34, 'J' => 18, 'K' => 19, 'L' => 20, 'M' => 21, 'N' => 22, 'O' => 35, 'P' => 23, 'Q' => 24, 'R' => 25, 'S' => 26, 'T' => 27, 'U' => 28, 'V' => 29, 'W' => 32, 'X' => 30, 'Y' => 31, 'Z' => 33);
            $code = $area_code[$taiwan[1]];
            $sum = $code{0} + $code{1} * 9;
            $index = 8;
            for ($k = 1; $k < 8; $k++) {
                $sum += $taiwan[2]{$k} * $index;
                $index--;
            }

            $get_num = $sum % 10;
            if ($get_num === $taiwan[2]{8}) {
                return true;
            }
            return false;
        }
        preg_match('/^[157][\d]{6}\([\d]\)$/', $IDCard, $aomen);//澳门
        if ($aomen) {
            return true;
        }
        return false;
    }

    public static function exportPersonnelTest($condition){
        if(isset($condition['start']) && $condition['start']){
            $where = 'a.create_time >="'.$condition['start'].'" and a.create_time <="'.$condition['end'].'"';
        }
        $data   =   Db::name('personnel_interview')
            ->alias('a')
            ->field('a.name,a.phone,a.card,a.job,d.zone,a.create_time,
            d.company,d.name as division,
            b.interview_status,b.p_name,
            f.user_login,f.user_nickname,
            c.interview_time,c.i_status,
            i.start_time,g.is_pass,
            h.induction_date')
            ->join('personnel b','a.phone=b.p_telphone','left')
            ->join('(select * from tp_personnel_invite where iid in (select max(iid) from tp_personnel_invite where situation=1 group by pid )) c','b.pid=c.pid','left')
            ->join('user_department d','b.current_departid=d.id','left')
            ->join('user f','b.uid=f.id','left')
            ->join('personnel_train g','b.pid=g.pid','left')
            ->join('personnerl_train_time_set i','g.periodsid=i.id','left')
            ->join('personnel_induction h','b.pid=h.pid','left');
        if(isset($where)){
            $data = $data  ->where($where);
        }
        $data =$data->select()->toarray();
            //->where("c.situation=1") //c.iid in (select max(iid) from tp_personnel_invite group by pid,situation ) and

        //echo  Db::name('personnel_interview')->getLastSql();die;
        $header_arr = [
            'A'=>'姓名',
            'B'=>'电话',
            'C'=>'身份证',
            'D'=>'战区',
            'E'=>'公司',
            'F'=>'事业部',
            'G'=>'职位',
            'H'=>'状态',
            'I'=>'录入日期',
            'J'=>'归属人',
            'K'=>'归属人工号',
            'L'=>'初试日期',
            'M'=>'初试状态',
            'N'=>'培训日期',
            'O'=>'培训状态',
            'P'=>'入职日期'
        ];

        foreach ($data as $key => $val){
            switch ($val['interview_status']){//
                case 0:
                    $data[$key]['interview_status'] =   '录入';break;
                case 1:
                    $data[$key]['interview_status'] =   '初试通过';break;
                case 2:
                    $data[$key]['interview_status'] =   '初试淘汰';break;
                case 3:
                    $data[$key]['interview_status'] =   '复试通过';break;
                case 4:
                    $data[$key]['interview_status'] =   '复试淘汰';break;
                case 5:
                    $data[$key]['interview_status'] =   '培训通过';break;
                case 6:
                    $data[$key]['interview_status'] =   '培训淘汰';break;
                case 7:
                    $data[$key]['interview_status'] =   '已入职';break;
                default:
                    $data[$key]['interview_status'] =   '';break;
            }

            switch ($val['i_status']){//0未面试，1面试中 2 不适合 3 通过 4爽约 5失败
                case 0:
                    $data[$key]['i_status'] =   '面试中';break;
                case 1:
                    $data[$key]['i_status'] =   '面试中';break;
                case 2:
                    $data[$key]['i_status'] =   '不适合';break;
                case 3:
                    $data[$key]['i_status'] =   '通过';break;
                case 4:
                    $data[$key]['i_status'] =   '爽约';break;
                case 5:
                    $data[$key]['i_status'] =   '失败';break;
                default:
                    $data[$key]['i_status'] =   '无状态';break;
            }
            switch ($val['is_pass']){// '0 培训中/1培训通过/3培训未到场/2培训淘汰/4主动退出/10无法识别',
                case 0:
                    $data[$key]['is_pass'] =   '培训中';break;
                case 1:
                    $data[$key]['is_pass'] =   '培训通过';break;
                case 2:
                    $data[$key]['is_pass'] =   '培训淘汰';break;
                case 3:
                    $data[$key]['is_pass'] =   '培训未到场';break;
                case 4:
                    $data[$key]['is_pass'] =   '主动退出/';break;
                case 10:
                    $data[$key]['is_pass'] =   '无法识别';break;
                default:
                    $data[$key]['is_pass'] =   '无数据';break;
            }
        }
        $indexKey   =   ['p_name','phone','card','zone','company','division','job',
            'i_status','create_time','user_nickname','user_login',
            'interview_time','i_status','start_time','is_pass','induction_date'];
        PoolService::exportName($header_arr,$data,$indexKey);
    }
}