<?php
namespace app\admin\model;

use think\Model;
use think\facade\Db;

class PersonnelJob extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'personnel_job';

    protected $pk='jid';

    public function getByStatus($j_status = 1){
        return $this->getModel()->alias('a')->field('a.*,b.name,c.company_name')
            ->join('user_department b','a.departid=b.id','left')
            ->join('user_company c','a.companyid=c.id','left')
            ->where(['a.j_status'=>$j_status])->paginate(10,false,['query'=>['j_status'=>$j_status]]);
    }



}