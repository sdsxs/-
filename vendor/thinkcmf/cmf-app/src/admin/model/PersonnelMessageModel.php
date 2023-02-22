<?php
namespace app\admin\model;

use think\Model;

class PersonnelMessageModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'personnel_message';

    protected $pk='mid';

    public function getByUid($uid,$para =   null){
        if(!$para){
            $para   =   ['uid'=>$uid];
        }else{
            $para   =   array_merge(['uid'=>$uid],$para);
        }
        return $this->where($para)->paginate(10,false,['query'=>$para]);
    }



}