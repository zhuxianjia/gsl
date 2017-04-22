<?php
namespace Home\Controller;
use Think\Controller;
class QrcodeController extends Controller {

    public function user_theme_list(){
        $user_id=session('adminuser_id');
        $main_user_id=I('main_user_id');
        $keyword=I('keyword');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if(!D('qrcode')->check_id(M('admin'),$main_user_id,$condition)) $arr=['state'=>'10003','detail'=>'管理员id参数错误！'];
        else {
            if($keyword) $condition['nick|section']=['like',"%".$keyword."%"];
            $data=M('user')->field('section,id,nick as name,guid as openid')->order('section')->where($condition)->select();
            
            $order_id=M('admin_bind')->where('user_id=%d',$main_user_id)->getField('user_id',true);
            for($i=0;$i<count($data);$i++){
                if(in_array($data[$i]['id'],$order_id)) $data[$i]['checked']=true;
                else $data[$i]['checked']=false;
            }
            $order=splite_array($data,'section','children');
            for($i=0;$i<count($order);$i++){
                $order[$i]['name']=$order[$i]['section'];
                unset($order[$i]['section']);
            }
            $result=array('name'=>'所有部门','open'=>true,'children'=>$order);

            $arr['state']='0';
            $arr['order']=$result;
        }
        $this->ajaxreturn($arr);  
    }

    public function user_bind(){
        $user_id=session('adminuser_id');
        $list=I('list');
        $main_user_id=I('main_user_id');
        // $list=[10043,10022];
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if(!D('qrcode')->check_id(M('user'),$list,$condition)) $arr=['state'=>'10003','detail'=>'用户openid列表参数错误！'];
        else if(!D('qrcode')->check_id(M('admin'),$main_user_id,$condition)) $arr=['state'=>'10003','detail'=>'管理员id参数错误！'];
        else {
            foreach ($list as $key => $value) {
                $guid=M('user')->where('id=%d',$value)->getField('guid');
                $data[]=['muser_id'=>$main_user_id,'user_id'=>$value,'guid'=>$guid];
            }
            $result=M('admin_bind')->addAll($data);
            if($result!==false) $arr=['state'=>'0','detial'=>'操作成功！'];
            else $arr=['state'=>'10001','detial'=>'操作失败！'];
        }
        $this->ajaxreturn($arr);
    }

    public function qrlog(){
        $qr_tokey=I('qr_tokey');
        $token=D('token')->get_admin_token(C('client_ID'),C('clientsecret'));
        $access_token=$token['access_token'];
        if(!$access_token)  $arr=['state'=>'10056','detail'=>'秘钥获取失败！'];
        else{
            $url=C('url').'/openapi/qrcode/get_open_id_by_token?access_token='.$access_token.'&qr_tokey='.$qr_tokey;
            $object=D('qrcode')->httpGet($url);
            $guid=json_decode($object)->data;
            $muser_id=M('admin_bind')->where('guid="%s"',$guid)->getField('muser_id');
            if($muser_id) {
                session('adminuser_id',$muser_id);
                session('guid',$guid);
                $arr=['state'=>'0','detail'=>'登录成功！'];
            }
            else $arr=['state'=>'10001','detail'=>'登录失败！'];
        }
        $arr=['state'=>'0','detail'=>'登录成功！'];
        $this->ajaxreturn($arr);
    }

    public function qrphoto(){
        $size=I('size',200,'intval');
        $qr_url=I('url');
        $token=D('token')->get_admin_token(C('client_ID'),C('clientsecret'),C('url').'/openapi/oauth2/admin_token');
        $access_token=$token['access_token'];
        if(!$access_token)  $arr=['state'=>'10056','detail'=>'秘钥获取失败！'];
        else if(!is_int($size)) $arr=['state'=>'10003','detail'=>'尺寸参数错误！'];
        else if(!check_url($qr_url)) $arr=['state'=>'10003','detail'=>'链接参数错误！'];
        else{
            $post_data=['access_token'=>$access_token,'app_id'=>C('app_id'),'url'=>$qr_url,'size'=>$size];
            $url=C('url').'/openapi/app/get_qr';
            $object=R('Home/News/httpPost',[$post_data,$url]);
            $order=json_decode($object)->data;
            if($order) $arr=['state'=>'0','order'=>$order];
            else $arr=['state'=>'10001','detail'=>'获取失败！'];
        }
        $this->ajaxreturn($arr);
    }

}