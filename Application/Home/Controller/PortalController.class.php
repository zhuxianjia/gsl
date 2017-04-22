<?php
namespace Home\Controller;
use Think\Controller;
class PortalController extends Controller {

    public function PortalLists(){
        $user_id=session('adminuser_id');
        $page=I('page',1,'intval');
    	$number=I('number',10,'intval');
    	$key=I('key');//投票开关  1-开启分页
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else{
            $list=D('portal')->get_admin_portal($user_id);
            if(!$list) $arr=['state'=>'0','order'=>[]];
            else{
                $condition['id']=['in',$list];
            	if($key==1) $data=M('portal')->page($page,$number)->field('id,name,user_id')->where($condition)->select();
            	else  $data=M('portal')->field('id,name,user_id')->where($condition)->select();
            	if($data){
                    $length=count($data);
                    for($i=0;$i<$length;$i++){
                       $assist_user_id=M('portal_distribute')->where('portal_id=%d',$data[$i]['id'])->getField('user_id',true);
                       $assist_user='';
                       foreach ($assist_user_id as $key => $value) {
                           $assist_user.=' '.M('admin')->where('id=%d',$value)->getField('nick');
                       }
                       $data[$i]['assist_user']=$assist_user;

                        if($data[$i]['user_id']) $data[$i]['admin_nick']=M('admin')->where('id=%d',$data[$i]['user_id'])->getField('nick');
                        else $data[$i]['admin_nick']='';
                    }
                }
            	else $data=[];
                $arr=['state'=>'0','order'=>$data];
            }
        }
        $this->ajaxreturn($arr);
    }

    public function PortalInfo(){
        $user_id=session('adminuser_id');
        $portal_id=I('id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else {
            $data=D('portal')->get_portal_info($portal_id);
            if(!$data) $data=[];
            $arr=['state'=>'0','order'=>$data];
        }
        $this->ajaxreturn($arr);
    }

    public function PortalCarousel(){
        $user_id=session('adminuser_id');
        $portal_id=I('id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else {
            $data=D('portal')->portal_carousel($portal_id);
            if(!$data) $data=[];
            $arr=['state'=>'0','order'=>$data];
        }
        $this->ajaxreturn($arr);
    }

    public function PortalTheme(){
        $user_id=session('adminuser_id');
        $portal_id=I('id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else {
            $data=D('portal')->portal_theme($portal_id,' ');
            $list=M('portal_article')->where('portal_id=%d',$portal_id)->getField('theme_id',true);
            for($i=0;$i<count($data['theme']);$i++){
                if(in_array($data['theme'][$i]['id'],$list)) $data['theme'][$i]['isarticletheme']=true;
                else $data['theme'][$i]['isarticletheme']=false;
            }
            if(!$data) $data=[];
            $arr=['state'=>'0','order'=>$data];
        }
        $this->ajaxreturn($arr);
    }

    public function PortalArticle(){
        $user_id=session('adminuser_id');
        $portal_id=I('id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else {
            $data=D('portal')->portal_article_theme($portal_id);
            if(!$data) $data=[];
            $arr=['state'=>'0','order'=>$data];
        }
        $this->ajaxreturn($arr);
    }

    public function PortalPublish(){
        $user_id=session('adminuser_id');
        $portal_id=I('id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if($portal_id&&!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            $temp=['name','push','carousel','theme','article','ispublic','article_layout','theme_layout','article_layout','user_id','assist_user'];
            if($portal_id) {
                $condition['id']=$portal_id;
                $info=M('portal')->where($condition)->find();
                $conditiont['portal_id']=$portal_id;
                $theme=M('portal_theme')->where($conditiont)->select();
                $carousel=M('portal_carousel')->where($conditiont)->field('photo,link,orders')->select();
                $article=M('portal_article')->where($conditiont)->field('theme_id')->select();
                $assist_user=M('portal_distribute')->where('portal_id=%d',$portal_id)->getField('user_id',true);
            }
            foreach ($temp as $key => $value) {
               $get=I($value,null);
               if(isset($get)) $data[$value]=$get;
               else if($value=='carousel') $data[$value]=$carousel;
               else if($value=='ispublic') $data[$value]=0;
               else if($value=='theme') $data[$value]=$theme;
               else if($value=='article') $data[$value]=$article;
               else if($value=='assist_user') $data[$value]=$assist_user;
               else if($value=='theme_layout') $data[$value]=json_decode($info[$value]);
               else if($value=='carousel_layout') $data[$value]=json_decode($info[$value]);
               else if($value=='article_layout') $data[$value]=json_decode($info[$value]);
               else $data[$value]=$info[$value];
            }

            M()->startTrans();
            if($data['theme_layout']) $data['theme_layout']=json_encode($data['theme_layout']);
            if($data['carousel_layout']) $data['carousel_layout']=json_encode($data['carousel_layout']);
            if($data['article_layout']) $data['article_layout']=json_encode($data['article_layout']);
            if(!$portal_id&&!D('backstage')->adminstrator_judge($user_id)) $data['user_id']=$user_id;

            if($portal_id) {
                $result=(M('portal')->where($condition)->save($data)!==false);
                D('portal')->db_delete(['portal_carousel','portal_article'],$conditiont);
            }
            else {
                if(!$data['name']||!$data['user_id']) $arr=['state'=>'20004','detail'=>'请补全门户名称信息！'];
                else{
                    $portal_id=M('portal')->add($data);
                    $result=$portal_id;
                }
            }
            if($data['carousel']){
                if(!D('portal')->check_order($data['carousel'])) {
                    $arr=['state'=>'20001','detail'=>'轮播图排序错误！'];
                    $result=false;
                }
                else{
                    foreach ($data['carousel'] as $key => $value) {
                        $psave[]=['photo'=>$value['photo'],'link'=>$value['link'],'orders'=>$value['orders'],'portal_id'=>$portal_id,'in_time'=>$value['in_time'],'time'=>$value['time']];
                    }
                    $result=$result&&M('portal_carousel')->addAll($psave);
                }
            }
            
            if($data['theme']){
                foreach ($data['theme'] as $key => $value) {
                    $save['orders']=$value['orders'];
                    $condition=['theme_id'=>$value['theme_id'],'portal_id'=>$portal_id];

                    if(M('portal_theme')->where($condition)->find())  $result=$result&&(M('portal_theme')->where($condition)->save($save)!==false);
                    else $result=$result&&M('portal_theme')->where($condition)->add($save);
                    if($result===false) break;
                }
            }

            if($data['article']){
                if(!D('portal')->check_article_theme($data['theme'],$data['article'])) {
                    $arr=['state'=>'20003','detail'=>'文章栏目不在门户订阅栏目范围内！'];
                    $result=false;
                }
                else{
                    foreach ($data['article'] as $key => $value) {
                        $asave[]=['theme_id'=>$value['theme_id'],'portal_id'=>$portal_id];
                    }
                    $result=$result&&M('portal_article')->addAll($asave);
                }
            }

            if($data['assist_user']){
                if(!D('portal')->check_id(M('admin'),$data['assist_user'],1)) {
                    $arr=['state'=>'20003','detail'=>'辅助管理员参数错误！'];
                    $result=false;
                }
                else if(in_array($data['user_id'], $data['assist_user'])){
                    $arr=['state'=>'10039','detail'=>'无法将自己设置为栏目辅助管理员!'];
                    $result=false;
                }
                else{
                    M('portal_distribute')->where('portal_id=%d',$portal_id)->delete();
                    foreach ($data['assist_user'] as $key => $value) {
                        $usave[]=['user_id'=>$value,'portal_id'=>$portal_id];
                    }
                    $result=$result&&M('portal_distribute')->addAll($usave);
                }
            }

            if($result){
                M()->commit();
                $arr=['state'=>'0','order'=>$portal_id];
            }
            else M()->rollback();
        }
        $this->ajaxreturn($arr);
    }

    public function PortalPropel(){
        $user_id=session('adminuser_id');
        $portal_id=I('id');
        $propel_id=array_unique(I('propel_id'));
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else if(!D('portal')->check_id(M('user'),$propel_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            M()->startTrans();
            $where['portal_id']=$id;
            M('portal_push')->where($where)->delete();
            if($propel_id){
                foreach($propel_id as $value){
                    $data[]=['user_id'=>$value,'portal_id'=>$portal_id,'time'=>time()];
                }
                $result=M('portal_push')->addAll($data);
            }

            if($result){
                M()->commit();
                $arr=['state'=>'0','detail'=>'推送成功！'];
            }
            else{
                M()->rollback();
                $arr=['state'=>'10001','detail'=>'推送失败！'];
            }
        }
        $this->ajaxreturn($arr);  
    }

    public function PortalThemePropel(){
        $portal_id=I('id');
        $theme_id=I('theme_id');
        $propel_id=I('propel_id');
        $user_id=session('adminuser_id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else if($propel_id&&!D('portal')->check_id(M('user'),$propel_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else if(!D('portal')->check_id(M('theme'),$theme_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            M()->startTrans();
            $where=['portal_id'=>$portal_id,'theme_id'=>$theme_id];
            $result=(M('portal_theme_push')->where($where)->delete()!==false);
            if($propel_id){
                foreach($propel_id as $value){
                    $data[]=['user_id'=>$value,'portal_id'=>$portal_id,'theme_id'=>$theme_id,'time'=>time()];
                }
                $result=$result&&M('portal_theme_push')->addAll($data);
            }
            
            if($result){
                M()->commit();
                $arr=['state'=>'0','detail'=>'推送成功！'];
            }
            else{
                M()->rollback();
                $arr=['state'=>'10001','detail'=>'推送失败！'];
            }
        }
        $this->ajaxreturn($arr);  
    }

    public function PortalDistribute(){
        $user_id=session('adminuser_id'); 
        $main_user=I('main_user');
        $assist_user=I('assist_user');
        $portal_id=I('id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            if(D('backstage')->adminstrator_judge($user_id)){
                $admin_nick=$main_user;
                $admin_user_id=M('admin')->where('nick="%s"',$main_user)->getField('id');
            }
            else{
                $admin_user_id=M('admin')->where('id=%d',$user_id)->getField('id');
                $admin_nick=M('admin')->where('id=%d',$user_id)->getField('nick');
            }

            $data['user_id']=$admin_user_id;
            if(in_array($admin_nick,$assist_user)) $arr=['state'=>'10039','detail'=>'无法将栏目主管理员设置为同一栏目的辅助管理员！'];
            else{
                M()->startTrans();
                $result=(M('portal')->where('id=%d',$portal_id)->save($data)!==false);
                if($assist_user){
                    for($j=0;$j<count($assist_user);$j++){
                        $data5[$j]['portal_id']=$portal_id;
                        $data5[$j]['user_id']=M('admin')->where('nick="%s"',$assist_user[$j])->getField('id');
                    }
                    $result=$result&&M('portal_distribute')->addAll($data5);
                }
                if($result){
                    M()->commit();
                    $arr=['state'=>'0','detail'=>'操作成功！'];
                }
                else M()->rollback();
            }
        }
        $this->ajaxreturn($arr);  
    }

    public function PortalDelete(){
        $user_id=session('adminuser_id');
        $portal_id=I('id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else {
            $theme_id=M('portal_theme')->where('portal_id=%d',$portal_id)->getField('theme_id',true);
            if(M('portal')->where('id=%d',$portal_id)->delete()) {
                if($theme_id){
                    $condition['id']=['in',$theme_id];
                    M('theme')->where($condition)->delete();
                }
                $arr=['state'=>'0','detail'=>'操作成功！'];
            }
            else $arr=['state'=>'10001','detail'=>'系统异常！'];
        }
        $this->ajaxreturn($arr);
    }

    public function user_theme_list(){
        $user_id=session('adminuser_id');
        $portal_id=I('id');
        $theme_id=I('theme_id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'门户id参数错误！'];
        else {
            $data=M('user_section')->order('id')->field('name as section,user_id as id')->select();

            $allid=M('portal_theme')->where('portal_id=%d',$portal_id)->getField('theme_id',true);
            if(!in_array($theme_id, $allid)) $arr=['state'=>'10003','detail'=>'栏目id参数错误！'];
            else{
                $order_id=M('portal_theme_push')->where('theme_id=%d and portal_id=%d',$theme_id,$portal_id)->getField('user_id',true);
                $userlist=M('user')->select();
                $list=array_column($userlist, 'id');
                for($i=0;$i<count($data);$i++){
                    if(in_array($data[$i]['id'],$order_id)) $data[$i]['checked']=true;
                    else $data[$i]['checked']=false;
                    $key=array_search($data[$i]['id'], $list);
                    $data[$i]['name']=$userlist[$key]['nick'];
                }
                $order=splite_array($data,'section','children');
                for($i=0;$i<count($order);$i++){
                    $order[$i]['name']=$order[$i]['section'];
                    unset($order[$i]['section']);
                }
                $result=array('name'=>'所有部门','open'=>true,'children'=>$order);
            }
            
            $arr['state']='0';
            $arr['order']=$result;
        }
        $this->ajaxreturn($arr);  
    }

    public function ArticleReceipt(){
        $user_id=session('adminuser_id');
        $article_id=I('id');
        $page=I('page');
        $number=I('number');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if(!D('portal')->check_id(M('article'),$article_id,$condition)) $arr=['state'=>'10003','detail'=>'门户id参数错误！'];
        else {
            $data=M('article_receipt_answer')->where('article_id=%d',$article_id)->page($page,$number)->select();
            // $receipt=M('article_receipt')->where('article_id=%d',$article_id)->select();
            for($i=0;$i<count($data);$i++){
                $data[$i]['answer']=json_decode($data[$i]['answer']);
                
                $data[$i]['nick']=M('user')->where('id=%d',$data[$i]['user_id'])->getField('nick');
                $data[$i]['section']=M('user_section')->where('user_id=%d',$data[$i]['user_id'])->field('id,name')->select();
            }
            if($data){
                $order['Lists']=$data;
                $order['count']=count(M('article_receipt_answer')->where('article_id=%d',$article_id)->select());
            } 
            else $order=[];
            $arr=['state'=>0,'order'=>$order];
        }
        $this->ajaxreturn($arr);
    }

    public function admin_management(){
        $id=I('id');
        $user_id=session('adminuser_id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if(!D('backstage')->adminstrator_judge($user_id)) $arr=['state'=>'10010','detail'=>'用户无权限!'];
        else if($id&&!D('portal')->check_id(M('admin'),$id,['type'=>1]))  $arr=['state'=>'10003','detail'=>'用户id参数错误!'];
        else {
            $temp=['nick','account','password'];
            if($id) $info=M('admin')->where('id=%d',$id)->find();
            else{
                foreach ($temp as $key => $value) {
                   $get=I($value,null);
                   if(isset($get)) {
                        if($value=='password') $data[$value]=md5($get);
                        else $data[$value]=$get;
                    }
                   else $data[$value]=$info[$value];
                }
            }
            if($id){
                $result=(M('admin')->where('id=%d',$id)->save($data)!==false);
            }
            else{
                $allnick=M('admin')->getField('account',true);
                if(in_array($data['account'],$allnick)) $arr=['state'=>'10070','detail'=>'已存在名称相同的管理员！'];
                else{
                    $data['in_time']=time();
                    $id=M('admin')->add($data);
                    $result=$id;
                }
            }
            if($result===false){
                if(!$arr) $arr=['state'=>'10001','detail'=>'操作失败！']; 
            }
            else $arr=['state'=>'0','detail'=>'操作成功！']; 
        }
        $this->ajaxreturn($arr);
    }



}
