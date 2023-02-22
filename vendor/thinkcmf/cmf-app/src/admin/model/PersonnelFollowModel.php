<?php
namespace app\admin\model;

use think\Model;
use app\admin\model\PersonnelModel;
use think\facade\Db;
use app\admin\service\PoolService;

class PersonnelFollowModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'personnel_follow';

    public function profile()
    {
        return $this->hasOne(PersonnelModel::class,'pid','iid');
    }

    /**新增跟进信息
     * @param $para
     * @return array
     */
    public function authUpdate($para){

        if(!$para['pid'] || !$para['uid']){
            return ['code'=>500,'msg'=>'信息不正确！'];
        }
        Db::startTrans();
        try{

            Db::name('personnel')->where(['pid'=>$para['pid']])->update(['maintenance_time'=>PoolService::getMaintenanceTime()]);
            $this->getModel()->insert($para);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ['code'=>500,'msg'=>'新增跟进信息失败!'];
        }
        return ['code'=>200,'msg'=>'新增跟进信息成功!'];

    }

    public function getI_statusAttr($value)
    {
        $status = [-1=>'删除',0=>'禁用',1=>'正常',2=>'待审核'];
        return $status[$value];
    }

    /**根据主键获取该简历的跟进信息
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getByPidUser($uid){
        return $this->alias('a')
            ->field('a.*,b.*,c.user_login')
        ->join('personnel b','a.pid=b.pid',"left")
        ->join('user c','a.uid=c.id','left')
        ->where(['a.uid'=>$uid])->select()->toArray();
    }

    /**获取个人的跟进信息
     * @param $uid
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getByUid($uid){
        return $this->where(['uid'=>$uid])->paginate(10);
    }

}