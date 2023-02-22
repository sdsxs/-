<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/6/3
 * Time: 13:58
 */

namespace app\dem\service;


define('DS', DIRECTORY_SEPARATOR);
defined('THINK_PATH') or define('THINK_PATH', __DIR__ . DS);
define('LIB_PATH', THINK_PATH . 'library' . DS);
define('CORE_PATH', LIB_PATH . 'think' . DS);
define('TRAIT_PATH', LIB_PATH . 'traits' . DS);
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DS);
defined('ROOT_PATH') or define('ROOT_PATH', dirname(realpath(APP_PATH)) . DS);

class Token
{
    /**
     * 生成用户的token,保持登陆
     * @param  $userid  int      用户id
     * @return $token   string   用户token
     */
    public function createToken($member_id)
    {
        $token=$member_id.'_'.MD5( $member_id.uniqid().rand( 00000000,99999999 ) );
        //设置key保存的文件目录
        $file_dir = ROOT_PATH.'public'.DS.'member_token'.DS.$member_id.DS;
        if( !file_exists( $file_dir ) )
        {
            mkdir($file_dir,0777,true);
        }
        //删除之前的token文件
        $file_token = scandir( $file_dir );
        foreach ( $file_token as $k => $v ) {
            if($v == '.' || $v == '..'){}else {
                unlink($file_dir.$v);
            }
        }
        $fh = fopen( $file_dir.$token,'w' );
        fwrite( $fh,serialize( array( 'time'=>time(),'token'=>$token,'member_id'=>$member_id ) ) );
        fclose( $fh );
        return $token;
    }
    /**
     * 用户登陆验证,利用key获取管理员id
     * 3天内有登陆再次更改为3天有效key
     * @param  $token   string  管理员key
     * @return [type]   user_id 管理员id
     */
    public function checkToken($token)
    {
        if( $token === NULL )
        {
            return json_encode(['code'=>500,"data"=>['msg'=>"token不存在,请重新登陆"]]);
        }
        $max_time=86400 * 3;  //3天过期时间,单位是秒
        $user_id=$this->get_user_id( $token );  //取到用户id
        $file_dir = ROOT_PATH.'public'.DS.'member_token'.DS.$user_id.DS;  //找到token所在目录
        $file=$file_dir.$token; //找到token文件夹
        //token存在的情况
        if( file_exists( $file ) )
        {
            $fh = @fopen($file,'r');
            //反序列化出token文件数据,等到time,key,user_id;
            $f_code = unserialize(@fread($fh,filesize($file)));
            @fclose($fh);
            if($f_code['time']+$max_time < time())
            {
                return json_encode(['code'=>500,"data"=>['msg'=>"token过期,请重新登陆"]]);
            }else
            {
                //没有过期,过期时间重新计算,避免用户每3天登陆一次
                $member_id=$f_code['member_id'];
                $fh1 = fopen( $file,'w' );
                //重新写入当前时间,确保3天以内登陆的用户一直不需要重新登陆
                fwrite( $fh1,serialize( array( 'time'=>time(),'token'=>$token,'member_id'=>$member_id ) ) );
                fclose( $fh1 );
                return $member_id;
            }
        }else
        {
            return json_encode(['code'=>500,"data"=>['msg'=>"token异常,请重新登陆"]]);
        }
    }

//通过token获取用户id
    function get_user_id($key)
    {
        return strstr( $key,"_",true );
    }
}