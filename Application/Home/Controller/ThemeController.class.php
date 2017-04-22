<?php
namespace Home\Controller;
use Think\Controller;
class ThemeController extends Controller {

    public function theme_management(){
        $theme_id=I('id');
        $portal_id=I('portal_id');
        $user_id=session('adminuser_id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if($theme_id&&!D('portal')->check_id(M('theme'),$theme_id,1)) $arr=['state'=>'10003','detail'=>'栏目id参数错误！'];
        else if($portal_id&&!D('portal')->check_id(M('portal'),$portal_id,1)) $arr=['state'=>'10003','detail'=>'门户id参数错误！'];
        else{
            $temp=['logo','link','theme','assist_user','main_user','push'];
            if($theme_id){
                $condition['id']=$theme_id;
                $conditiont['theme_id']=$theme_id;
                $conditionp=['theme_id'=>$theme_id,'portal_id'=>$portal_id];
                $info=M('theme')->where($condition)->find();
                $main_user=M('admin_theme')->where($conditiont)->getField('user_id');
                $assist_user=M('theme_distribute')->where($conditiont)->getField('user_id',true);
                $push=M('portal_theme')->where($conditionp)->getField('push');
            }
            foreach ($temp as $key => $value) {
               $get=I($value,null);
               if(isset($get)) $data[$value]=$get;
               else if($value=='main_user') $data[$value]=$main_user;
               else if($value=='assist_user') $data[$value]=$assist_user;
               else if($value=='push') $data[$value]=$push;
               else $data[$value]=$info[$value];
            }
            if(!D('backstage')->adminstrator_judge($user_id)) $data['main_user']=M('portal')->where('id=%d',$portal_id)->getField('user_id');
            
            if($theme_id) {
                $result=(M('theme')->where($condition)->save($data)!==false);
                D('portal')->db_delete(['theme_distribute','admin_theme'],$conditiont);
            }
            else {
                $alltheme=M('get_admin_theme')->where('id=%d',$data['main_user'])->getField('theme',true);
                if(!$data['theme']||!$data['logo']) $arr=['state'=>'20004','detail'=>'请补全栏目名称和图标！'];
                else if(in_array($data['theme'], $alltheme)) $arr=['state'=>'20005','detail'=>'已存在名称相同的栏目！'];
                else{
                    $theme_id=M('theme')->add($data);
                    $result=$theme_id;
                }
            }

            if($data['main_user']){
                $msave=['user_id'=>$data['main_user'],'theme_id'=>$theme_id];
                $result=$result&&M('admin_theme')->add($msave); 
            }
            
            if($data['assist_user']){
                foreach ($data['assist_user']as $key => $value) {
                    $asave[]=['user_id'=>$value,'theme_id'=>$theme_id];
                }
                $result=$result&&M('theme_distribute')->addAll($asave);
            }
            
            $conditionp=['theme_id'=>$theme_id,'portal_id'=>$portal_id];
            $pdata=['theme_id'=>$theme_id,'portal_id'=>$portal_id,'push'=>$data['push']];
            if(!M('portal_theme')->where($conditionp)->find()) $result=$result&&M('portal_theme')->add($pdata);
            else $result=$result&&(M('portal_theme')->where($conditionp)->save($pdata)!==false);
            
            if($result){
                M()->commit();
                $arr=['state'=>'0','order'=>$theme_id];
            }
            else {
                M()->rollback();
                if(!$arr) $arr=['state'=>'10001','detail'=>'操作失败！'];
            }
        }
        $this->ajaxreturn($arr);  
    }

    public function ThemeLists(){
        $list=I('list');
        $user_id=session('adminuser_id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if(!D('portal')->check_id(M('theme'),$list,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            $data=D('theme')->theme_list($list);
            $length=count($data);
            for($i=0;$i<$length;$i++){
                if(!$data[$i]['link']) $data[$i]['link']='';
            }
            $arr=['state'=>'0','order'=>$data];
        }
        $this->ajaxreturn($arr);
    }

    public function MainAdminTheme(){
        $portal_id=I('id');
        $user_id=session('adminuser_id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            $muser_id=M('portal')->where('id=%d',$portal_id)->getField('user_id');
            $data['theme']=M('get_admin_theme')->where('id=%d',$muser_id)->field('theme_id as id,theme,logo,link')->select();
            $list=M('portal_article')->where('portal_id=%d',$portal_id)->getField('theme_id',true);
            $length=count($data);
            for($i=0;$i<$length;$i++){
                if(in_array($data['theme'][$i]['id'],$list)) $data['theme'][$i]['isarticletheme']=true;
                else $data['theme'][$i]['isarticletheme']=false;
            }
            $arr=['state'=>'0','order'=>$data];
        }
        $this->ajaxreturn($arr);
    }

    public function article_theme(){
        $user_id=session('adminuser_id');
        $main_user_id=I('main_user_id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if($main_user_id&&!D('portal')->check_id(M('admin'),$main_user_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            if(!$main_user_id){
                if(D('backstage')->adminstrator_judge($user_id)) $condition=1;
                else  $condition['main_user_id|user_id']=$user_id;
            }   
            else $condition['main_user_id']=$main_user_id;
            $data=M('get_theme_list')->where($condition)->field('admin_nick,theme,id,main_user_id')->order('id')->select();
            $order=splite_array($data,'main_user_id','children',['admin_nick']);
            if(!$order) $order=[];
            $arr=['state'=>'0','order'=>$order];
        }
        $this->ajaxreturn($arr);   
    }

    public function theme_management_show(){
        $theme_id=I('theme_id');
        $portal_id=I('portal_id');
        $user_id=session('adminuser_id');
        $data=M('portal_theme')->where('theme_id=%d and portal_id=%d',$theme_id,$portal_id)->find();
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if(!$data) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            if(!$data['push']) $data['push']='';
            $theme=M('theme')->where('id=%d',$theme_id)->find();
            if(!$theme['link']) $theme['link']='';
            else $theme['link']=html_entity_decode($theme['link']);
            $assist_user=M('theme_distribute')->where('theme_id=%d',$theme_id)->field('user_id as id')->select();
            $length=count($assist_user);
            for($i=0;$i<$length;$i++){
                $assist_user[$i]['name']=D('section')->get_admin_name($assist_user[$i]['id']);
            }
            if(!$assist_user) $assist_user=[];
            $main_user_id=M('admin_theme')->where('theme_id=%d',$theme_id)->getField('user_id');
            $order=['theme_id'=>$theme_id,'portal_id'=>$portal_id,'push'=>$data['push'],'orders'=>$data['orders'],'name'=>$theme['theme'],'logo'=>$theme['logo'],'link'=>$theme['link'],'assist_user'=>$assist_user,'user_id'=>$main_user_id,'admin_nick'=>D('section')->get_admin_name($main_user_id)];
            $arr=['state'=>'0','order'=>$order];
        }
        $this->ajaxreturn($arr);
    }




}