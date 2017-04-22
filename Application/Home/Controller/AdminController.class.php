<?php
namespace Home\Controller;
use Think\Controller;
class AdminController extends Controller {
//后台
    public $url='/home/news/push_msg_to_users';//推送url
    public $updateurl='/home/news/articleUpdate';//更新置顶文章url 

    public function logoutapi(){
        $code=D("backstage")->logout();
        switch ($code)
                         {
                            case 0:
                            $arr['state']='0';
                            $arr['detail']='登出成功';
                            break;
                            default:
                            $arr['state']='10009';
                            $arr['detail']='网络异常';
                          }
        $this->ajaxReturn($arr);
    }

    public function loginapi(){
        $nick=I("post.nick");
        $password=I("post.password");
        $code=D("backstage")->login($nick,md5($password));
            switch ($code)
                             {
                                case 0:
                                $arr['state']='0';
                                $arr['detail']='登录成功！';
                                break;
                                case 10020:
                                $arr['state']='10020';
                                $arr['detail']='账号或密码不正确！';
                                break;
                                case 10023:
                                $arr['state']='10023';
                                $arr['detail']='系统管理员！';
                                break;
                                case 10024:
                                $arr['state']='10024';
                                $arr['detail']='一般管理员！';
                                break;
                                case 10044:
                                $arr['state']='10044';
                                $arr['detail']='还未被分配栏目，请联系系统管理员！';
                                break;
                                default:
                                $arr['state']='10009';
                                $arr['detail']='网络异常！'; 
                              }
        $this->ajaxreturn($arr);
    }


    public function pswd_resetapi(){
        $user_id=session('adminuser_id');
        $password=I('post.password');
        $repassword=I('post.repassword');
        $code=D('backstage')->change_password($user_id,md5($password),md5($repassword));
            switch ($code)
                         {
                            case 0:
                            $arr['state']='0';
                            $arr['detail']='修改成功！';
                            break;
                            case 10018:
                            $arr['state']='10018';
                            $arr['detail']='原密码不正确！';
                            break;
                            case 10001:
                            $arr['state']='10001';
                            $arr['detail']='系统异常！';
                            break;
                            case 10000:
                            $arr['state']='10000';
                            $arr['detail']='用户未登录！';
                            break;
                            default:
                            $arr['state']='10009';
                            $arr['detail']='网络异常！';
                          }
          $this->ajaxreturn($arr);
    } 


    public function user_verify(){
        $user_id=session('adminuser_id');
        if($user_id) $arr=M('admin')->where('id=%d',$user_id)->getField('type');
        else $arr='未登录！';
        $this->ajaxreturn($arr);
    }

    public function log_verify(){
        $user_id=session('adminuser_id');
        if($user_id){
            $state=M('admin')->where('id=%d',$user_id)->getField('type');
            if($state==2){
                $arr['state']='10023';
                $arr['detail']='系统管理员！';
            }
            else{
                $arr['state']='10024';
                $arr['detail']='一般管理员！';
            }
        }
        else{
            $arr['state']='10000';
            $arr['detail']='登录失效！';
        }
         $this->ajaxreturn($arr);
    }

    public function ajaxclient(){
        $user_id=session('adminuser_id');
        $page=I('page',1,'intval');
        $number=I('number',10,'intval');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)){
                $order=M('user')->field('id,nick,avatar,guid,gender,count,incount')->order('count desc')->page($page,$number)->select();
                for($i=0;$i<count($order);$i++){
                    $order[$i]['section']=D('section')->get_user_section($order[$i]['id']);
                }
                $arr=['order'=>$order,'sum'=>count(M('user')->select())];
            }
        }
        else $arr='未登录！';
        $this->ajaxreturn($arr);
    }

    public function client_tag_scan(){
        $id=I('id');
        $user_id=session('adminuser_id');
        if($user_id) if(D('backstage')->adminstrator_judge($user_id)) $arr=M('get_user_tag')->where('id=%d',$id)->getField('tag',true);
        else $arr='未登录！';        
        $this->ajaxreturn($arr);
    }

    public function ajaxcomment(){
        $user_id=session('adminuser_id');
        $page=I('page',1,'intval');
        $number=I('number',10,'intval');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)){
                $data=M('user_comment_list')->field('id,nick,text,avatar,article_id,in_time,count,title,user_id,type')->page($page,$number)->select();
                for($i=0;$i<count($data);$i++){
                    $data[$i]['text']=html_entity_decode($data[$i]['text']);
                    $data[$i]['in_time']=date('m月d日 H:i',$data[$i]['in_time']);
                    $data[$i]['section']=D('section')->get_user_section($data[$i]['user_id']);
                    $data[$i]['theme']=M('get_article')->where('id=%d',$data[$i]['article_id'])->getField('theme');
                }
                $arr=['state'=>'0','order'=>$data,'sum'=>count(M('user_comment_list')->select())];
            }
        }
        else $arr='未登录！';
        $this->ajaxreturn($arr);
    }

    public function commentsearchapi(){
        $key=I('key');
        $user_id=session('adminuser_id');
        if($user_id){
            if($key){
                $condition['nick']=array('like','%'.$key.'%');
                $data=M('user_comment_list')->page($page,$number)->where($condition)->select();
                if($data){
                     for($i=0;$i<count($data);$i++){
                        $data[$i]['text']=html_entity_decode($data[$i]['text']);
                        $data[$i]['in_time']=date('m月d日 H:i',$data[$i]['in_time']);
                        $data[$i]['section']=D('section')->get_user_section($data[$i]['user_id']);
                    }
                    $arr=['state'=>'0','order'=>$data,'sum'=>count(M('user_comment_list')->select())];
                }
                else $arr=['state'=>'10021','detail'=>'搜索为空!'];
            }
            else $arr=['state'=>'10002','detail'=>'输入不能为空!'];
        }
        else $arr=['state'=>'10000','detail'=>'未登录!'];
        $this->ajaxReturn($arr);
    }

    public function ajaxclientsearchapi(){
        $key=I('key');
        $user_id=session('adminuser_id');
        $page=I('page',1,'intval');
        $number=I('number',10,'intval');
        if($user_id){
            if($key) $condition['nick|guid']=array('like','%'.$key.'%');
            $data=M('user')->where($condition)->page($page,$number)->select();
            if($data) $arr=['state'=>'0','order'=>$data,'sum'=>count(M('user')->where($condition)->select())];
            else $arr=['state'=>'10002','detail'=>'输入不能为空!'];
        }
        else $arr=['state'=>'10000','detail'=>'未登录!'];  
        $this->ajaxReturn($arr);
    } 

    public function commentapi(){
        $id=I('id');
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)){
                $allid=M('comment')->getField('id',true);
                if(in_array($id,$allid)){
                    if(M('comment')->where('id=%d',$id)->delete()) $arr=['state'=>'0','detail'=>'删除成功！'];
                    else $arr=['state'=>'10001','detail'=>'系统异常！'];
                }
                else $arr=['state'=>'10003','detail'=>'参数错误！'];
            }
            else $arr=['state'=>'10010','detail'=>'用户无权限！'];
        }
        else $arr=['state'=>'10000','detail'=>'用户未登录！'];
        $this->ajaxreturn($arr);
    }

    public function articlekeyapi(){
         $id=I('id');
         $key=I('key');
         $allid=M('article')->getField('id',true);
         $user_id=session('adminuser_id');
         if($user_id){
            if(in_array($id,$allid)&&in_array($key,array(1,2))){
                $oldkey=M('article')->where('id=%d',$id)->getField('`key`');
                if($oldkey==$key) $arr=['state'=>'10005','detail'=>'请勿重复操作！'];
                else{
                    $data['key']=$key;
                    if(!(M('article')->where('id=%d',$id)->save($data)===false)) $arr=['state'=>'0','detail'=>'修改成功！'];
                    else $arr=['state'=>'10001','detail'=>'系统异常！'];
                }
            }
            else$arr=['state'=>'10003','detail'=>'参数错误！'];
         }
         else $arr=['state'=>'10000','detail'=>'未登录！'];
         $this->ajaxReturn($arr);
     }

//文章分页输出
    public function ajaxarticle(){
        $user_id=session('adminuser_id');
        $page=I('page',1,'intval');
        $number=I('number',10,'intval');
        $state=I('state',2);
        if(!$user_id) $arr='未登录！';
        else if(!in_array($state,array(1,2))) $arr='参数错误！';
        else{
            if(!D('backstage')->adminstrator_judge($user_id)){
                $where['main_user_id|user_id']=$user_id;
                $alltheme_id=M('get_theme_list')->where($where)->getField('id',true);
                if($alltheme_id) $condition['theme_id']=array('in',$alltheme_id);
            }
            $condition['state']=$state;
            if($state==2) $data=M('article_order')->field('id,author,in_time,theme_id,type,title,function,key,cuser_id,time')->page($page,$number)->where($condition)->select();
            else $data=M('article_order')->field('id,author,in_time,theme_id,type,title,function,key,cuser_id,time')->page($page,$number)->where($condition)->select();
            for($i=0;$i<count($data);$i++){
                $data[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
                $data[$i]['theme']=M('theme')->where('id=%d',$data[$i]['theme_id'])->getField('theme');
                if($data[$i]['key']==2) $data[$i]['key']='是';
                else $data[$i]['key']='否';
                if(($data[$i]['type']&1)!=0) $data[$i]['type']='置顶';
                else $data[$i]['type']='';
                if(!$data[$i]['author']) $data[$i]['author']=M('user')->where('id=%d',$data[$i]['cuser_id'])->getField('nick');
                $t='';
                foreach($data[$i]['value'] as $value){
                    $t=$t.$value['tag'].' ';
                }
                $data[$i]['tag']=$t;
                $data[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
                $data[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
                $order[$i]['dislike_count']=count(M('article_dislike')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
                $data[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
                $data[$i]['count']=$data[$i]['like_count'].'/'.$data[$i]['read_count'].'/'.$data[$i]['comment_count'];
                if($data[$i]['time']) $data[$i]['in_time']=date('Y-m-d H:i:s',$data[$i]['time']);
                else $data[$i]['in_time']=date('Y-m-d H:i:s',$data[$i]['in_time']);

                if(D('portal')->isreceipt($data[$i]['id'])) $data[$i]['isreceipt']=true;
                else $data[$i]['isreceipt']=false;

                if(D('portal')->isvote($data[$i]['id'])) $data[$i]['isvote']=true;
                else $data[$i]['isvote']=false;
            }
            // if($state==1) $data=array_slice(array_sort($data,'in_time'),($page-1)*$number,$number);
            $arr['order']=$data;
            $arr['sum']=count(M('article')->where($condition)->select());
        }
        $this->ajaxreturn($arr);
    }

    public function article_theme(){
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)) $condition=1;
            else  $condition['main_user_id|user_id']=$user_id;
            $data=M('get_theme_list')->where($condition)->field('admin_nick,theme,id')->group('id')->select();
            for($i=0;$i<count($data);$i++){
                $theme[]=array('id'=>$data[$i]['id'],'theme'=>$data[$i]['admin_nick'].' '.$data[$i]['theme']);
            }
        }
        else  $theme='未登录！';
        $this->ajaxreturn($theme);   
    }

    public function ajaxarticle_themeapi(){
        $id=I('id');
        $page=I('page',1,'intval');
        $number=I('number',10,'intval');
        $user_id=session('adminuser_id');
        $state=I('state');
        $code=D("backstage")->article_theme($user_id,$id,$state);
            switch ($code)
                             {
                            case 0:
                            if($id!=0) $condition['theme_id']=$id;
                            $condition['state']=$state;
                            $data=M('article_order')->field('id,author,in_time,theme_id,type,title,function,key,cuser_id,time')->where($condition)->page($page,$number)->select();
                            for($i=0;$i<count($data);$i++){
                                $data[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
                                $data[$i]['theme']=M('theme')->where('id=%d',$data[$i]['theme_id'])->getField('theme');       
                                if($data[$i]['key']==2) $data[$i]['key']='是';
                                if($data[$i]['key']==1) $data[$i]['key']='否';
                                if(($data[$i]['type']&1)!=0) $data[$i]['type']='置顶';
                                else $data[$i]['type']='';
                                $t='';
                                foreach($data[$i]['value'] as $value){
                                    $t=$t.$value['tag'].' ';
                                } 
                                $data[$i]['tag']=$t;
                                $data[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
                                $data[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
                                $data[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
                                $data[$i]['count']=$data[$i]['like_count'].'/'.$data[$i]['read_count'].'/'.$data[$i]['comment_count'];
                                $data[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
                                if($data[$i]['time']&&!$data[$i]['in_time']) $data[$i]['in_time']='';
                                else $data[$i]['in_time']=date('Y-m-d H:i:s',$data[$i]['in_time']);
                                if(!$data[$i]['author']) $data[$i]['author']=M('user')->where('id=%d',$data[$i]['cuser_id'])->getField('nick');

                                if(D('portal')->isreceipt($data[$i]['id'])) $data[$i]['isreceipt']=true;
                                else $data[$i]['isreceipt']=false;
                            }
                            $arr['state']='0';
                            $arr['order']=$data;
                            $arr['sum']=count(M('article')->where($condition)->select());
                            break;
                            case 10002:
                            $arr['state']='10002';
                            $arr['detail']='输入为空';
                            break;
                            case 10003:
                            $arr['state']='10003';
                            $arr['detail']='参数错误';
                            break;
                            case 10021:
                            $arr['state']='10021';
                            $arr['detail']='搜索为空';
                            break;
                            case 10000:
                            $arr['state']='10000';
                            $arr['detail']='未登录';
                            break;
                            default:
                            $arr['state']='10009';
                            $arr['detail']='网络异常';
                          }
        $this->ajaxReturn($arr);
    }

   public function ajaxarticle_searchapi(){
        $key=I('key');
        $user_id=session('adminuser_id');
        $page=I('page',1,'intval');
        $number=I('number',10,'intval');
        $state=I('state');
        $code=D("backstage")->articlesearch($user_id,$key,$state);
            switch ($code)
                             {
                            case 0:
                            if(D('backstage')->adminstrator_judge($user_id)) {
                                if($key) $condition['title|author']=array('like','%'.$key.'%');
                                $condition['state']=$state;
                            }
                            else{
                                $where['main_user_id|user_id']=$user_id;
                                $alltheme_id=M('get_theme_list')->where($where)->getField('id',true);
                                if($alltheme_id){
                                    $condition['theme_id']=array('in',$alltheme_id);
                                    if($key) $condition['title|author']=array('like','%'.$key.'%');
                                    $condition['state']=$state;
                                }
                            }
                            if($condition){
                                $data=M('article_order')->field('id,author,in_time,theme_id,type,title,function,key,cuser_id,time')->where($condition)->page($page,$number)->select();
                                for($i=0;$i<count($data);$i++){
                                    $data[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
                                    $data[$i]['theme']=M('theme')->where('id=%d',$data[$i]['theme_id'])->getField('theme');       
                                    if($data[$i]['key']==2) $data[$i]['key']='是';
                                    if($data[$i]['key']==1) $data[$i]['key']='否';
                                    if(($data[$i]['type']&1)!=0) $data[$i]['type']='置顶';
                                    else $data[$i]['type']='';
                                    $t='';
                                    foreach($data[$i]['value'] as $value){
                                        $t=$t.$value['tag'].' ';
                                    } 
                                    $data[$i]['tag']=$t;
                                    $data[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
                                    $data[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
                                    $data[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
                                    $data[$i]['count']=$data[$i]['like_count'].'/'.$data[$i]['read_count'].'/'.$data[$i]['comment_count'];
                                    $data[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
                                    if($data[$i]['time']&&!$data[$i]['in_time']) $data[$i]['in_time']='';
                                    else $data[$i]['in_time']=date('Y-m-d H:i:s',$data[$i]['in_time']);
                                    if(!$data[$i]['author']) $data[$i]['author']=M('user')->where('id=%d',$data[$i]['cuser_id'])->getField('nick');

                                    if(D('portal')->isreceipt($data[$i]['id'])) $data[$i]['isreceipt']=true;
                                    else $data[$i]['isreceipt']=false;
                                }
                                $arr['state']='0';
                                $arr['order']=$data;
                                $arr['sum']=count(M('article')->where($condition)->select());
                            }
                            else{
                                $arr['state']='10038';
                                $arr['detail']='请先添加主题';
                            }
                            break;
                            case 10002:
                            $arr['state']='10002';
                            $arr['detail']='输入不能为空';
                            break;
                            case 10021:
                            $arr['state']='10021';
                            $arr['detail']='搜索为空';
                            break;
                            case 10038:
                            $arr['state']='10038';
                            $arr['detail']='请先添加主题';
                            break;
                            case 10000:
                            $arr['state']='10000';
                            $arr['detail']='未登录';
                            break;
                            default:
                            $arr['state']='10009';
                            $arr['detail']='网络异常';
                          }
        $this->ajaxReturn($arr);
    } 

    public function article_deleteapi(){
        $id=check_int(I('id'));
        $user_id=session('adminuser_id');
        if($user_id){
            $allid=M('article')->getField('id',true);
            if(in_array($id,$allid)){
                if(M('article')->where('id=%d',$id)->delete()){
                    $arr['state']='0';
                    $arr['detail']='文章删除成功';
                }
                else $arr=['state'=>'10001','detail'=>'系统异常！'];
            }
            else $arr=['state'=>'10003','detail'=>'参数错误！'];
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxReturn($arr);
    }

    public function ajaxarticle_readlist(){
        $id=I('id');
        $user_id=session('adminuser_id');
        $page=I('page',1,'intval');
        $number=I('number',10,'intval');
        if($user_id){
            if(D('backstage')->check_article($id)){
                $data=M('user_article')->where('article_id=%d',$id)->field('user_id,in_time')->order('in_time desc')->page($page,$number)->select();
                for($i=0;$i<count($data);$i++){
                    $data[$i]['read_time']=date('Y-m-d H:i:s',$data[$i]['in_time']);
                    $order=M('user')->where('id=%d',$data[$i]['user_id'])->find();
                    $data[$i]['nick']=$order['nick'];
                    $data[$i]['section']=D('section')->get_user_section($data[$i]['user_id']);
                    $data[$i]['avatar']=$order['avatar'];
                    $data[$i]['guid']=$order['guid'];
                    $data[$i]['gender']=$order['gender'];
                }
                if($data){
                    $arr['state']='0';
                    $arr['order']=$data;
                    $arr['sum']=count(M('user_article')->where('article_id=%d',$id)->select());
                }
                else $arr=['state'=>'10002','detail'=>'暂无相应记录！'];
            }
            else $arr=['state'=>'10003','detail'=>'参数错误！'];
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxReturn($arr);
    }

    public function theme_list(){
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)) $condition=1;
            else $condition['main_user_id|user_id']=$user_id;
            if($condition){
                $tempdata=M('get_theme_list')->field('admin_nick,theme,id,nick,logo,link')->order('id desc')->where($condition)->select();
                $data=splite_array($tempdata,'id','value',array('admin_nick','theme','logo','link'));
                for($i=0;$i<count($data);$i++){
                    $theme[]=array('id'=>$data[$i]['id'],'theme'=>$data[$i]['theme'],'admin_nick'=>$data[$i]['admin_nick'],'logo'=>$data[$i]['logo'],'link'=>$data[$i]['link']);
                    $t="";
                    foreach($data[$i]['value'] as $value){
                        $t=$t.$value['nick']." "; 
                    }
                    $theme[$i]['nick']=$t;
                    $g='';
                    $tags=M('get_theme_tag')->where('id=%d',$data[$i]['id'])->getField('tag',true);
                    foreach($tags as $tagvalue){
                        $g=$g.$tagvalue." "; 
                    }
                    $theme[$i]['tag']=$g;
                }
            }
        }
        else $theme='未登录！';
        $this->ajaxreturn($theme);   
    }

    public function theme_search(){
        $key=I('key');
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)) $condition['theme|admin_nick']=array('like','%'.$key.'%');
            else{
                $where['main_user_id|user_id']=$user_id;
                $alltheme_id=M('get_theme_list')->where($where)->getField('id',true);
                if($alltheme_id){
                    $condition['theme_id']=array('in',$alltheme_id);
                    $condition['theme|admin_nick']=array('like','%'.$key.'%');
                }
            }
            if($condition){
                if($key){
                    $tempdata=M('get_theme_list')->field('admin_nick,theme,id,nick,logo,link')->order('id desc')->where($condition)->select();
                    $data=splite_array($tempdata,'id','value',array('admin_nick','theme','logo','link'));
                    for($i=0;$i<count($data);$i++){
                        $theme[]=array('id'=>$data[$i]['id'],'theme'=>$data[$i]['theme'],'admin_nick'=>$data[$i]['admin_nick'],'logo'=>$data[$i]['logo'],'link'=>$data[$i]['link']);
                        $t="";
                        foreach($data[$i]['value'] as $value){
                            $t=$t.$value['nick']." "; 
                        }
                        $theme[$i]['nick']=$t;
                        $g='';
                        $tags=M('get_theme_tag')->where('id=%d',$data[$i]['id'])->getField('tag',true);
                        foreach($tags as $tagvalue){
                            $g=$g.$tagvalue." "; 
                        }
                        $theme[$i]['tag']=$g;
                    }
                    if($theme){  
                        $arr['state']='0';
                        $arr['order']=$theme;
                    }
                    else $arr=['state'=>'10021','detail'=>'搜索为空！'];
                }
                else $arr=['state'=>'10002','detail'=>'输入不能为空！'];
            }
            else $arr=['state'=>'10038','detail'=>'请先添加主题！'];
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr);   
    }


    public function dmin_management_theme_search(){
        $key=I('key');
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)){
                if($key){
                    $alltheme_id=M('admin_theme')->getField('theme_id',true);
                    $condition['theme']=array('like','%'.$key.'%');
                    $condition['id']=array('not in',$alltheme_id);
                    $data=M('theme')->where($condition)->select();
                    if($data) $arr=['state'=>'0','order'=>$data];
                    else $arr=['state'=>'10021','detail'=>'搜索为空,请添加新的主题！'];
                }
                else $arr=['state'=>'10002','detail'=>'输入不能为空！'];
            }
            else $arr=['state'=>'10010','detail'=>'用户无权限！'];
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr);   
    }

    public function theme_management(){
        $id=I('id');
        $file=I('logo');
        $theme=I('theme');
        $assist_user=I('assist_user');
        $main_user=I('main_user');
        // $tag=array_unique(I('tag'));
        $info=$this->get_base64($file);
        $key=I('key');
        $isshow=I('isshow',2);// 1-领导推送2-正常显示
        $user_id=session('adminuser_id');
        if($user_id){
            if($theme){
                $data1['theme']=$theme;
                if($key) $data1['push']=$key;
                if($isshow=='0') $data1['isshow']=2;
                else if($isshow==1) $data1['isshow']=1;
                if(D('backstage')->adminstrator_judge($user_id)){
                    $admin_nick=$main_user;
                    $admin_user_id=M('admin')->where('nick="%s"',$main_user)->getField('id');
                }
                else{
                    $admin_user_id=M('admin')->where('id=%d',$user_id)->getField('id');
                    $admin_nick=M('admin')->where('id=%d',$user_id)->getField('nick');
                }
                $alltheme=M('get_admin_theme')->where('id=%d',$admin_user_id)->getField('theme',true);
                $data2['user_id']=$admin_user_id;
                if(!in_array($admin_nick,$assist_user)){
                    if(!$id){
                        if(!$info){
                            $arr['state']='10022';
                            $arr['detail']='图片上传失败';
                        }
                        else{
                            $data1['logo']="/".$info[0];
                            if(in_array($theme,$alltheme)){
                                $arr['state']='10080';
                                $arr['detail']='该管理员已管理相同名称的栏目！';
                            }
                            else{
                                $id=M('theme')->add($data1);
                                $data2['theme_id']=$id;
                                if($id){
                                    if(M('admin_theme')->add($data2)){
                                        if($assist_user){
                                            for($j=0;$j<count($assist_user);$j++){
                                                $data5[$j]['theme_id']=$id;
                                                $data5[$j]['user_id']=M('admin')->where('nick="%s"',$assist_user[$j])->getField('id');
                                            }
                                            M('theme_distribute')->addAll($data5);
                                        }
                                        $arr['state']='0';
                                        $arr['detail']='新增成功！';
                                    }
                                    else{
                                        $arr['state']='10001';
                                        $arr['detail']='系统异常！';
                                    }
                                }
                            }
                        }                
                    }
                    else{
                        if($file){
                            if(!$info){
                                $arr['state']='10022';
                                $arr['detail']='图片上传失败';
                            }
                            else{
                                $data1['logo']="/".$info[0];   
                            }
                        }
                        if(!(M('theme')->where('id=%d',$id)->save($data1)===false)){
                            if($admin_nick){
                                $data2['theme_id']=$id;
                                M('admin_theme')->where('theme_id=%d',$id)->save($data2);
                            }
                            if($assist_user){
                                M('theme_distribute')->where('theme_id=%d',$id)->delete();
                                for($j=0;$j<count($assist_user);$j++){
                                    $data5[$j]['theme_id']=$id;
                                    $data5[$j]['user_id']=M('admin')->where('nick="%s"',$assist_user[$j])->getField('id');
                                }
                                M('theme_distribute')->addAll($data5);
                            }
                            $arr['state']='0';
                            $arr['detail']='修改成功！';
                        }
                        else{
                            $arr['state']='10001';
                            $arr['detail']='系统异常！';
                        }
                    }
                }
                else{
                    $arr['state']='10039';
                    $arr['detail']='无法将自己设置为栏目辅助管理员！';
                }
            }
            else{
                $arr['state']='10003';
                $arr['detail']='栏目必须要有名称和标签！';
            }
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr);  
    }

    public function theme_management_tag_search(){
        $key=I('key');
        if($key){
            $otherid=M('tag_theme')->getField('tag_id',true);
            $condition['id']=array('not in',$otherid);
            $condition['tag']=array('like','%'.$key.'%');
            $temparr=M('tag')->where($condition)->getField('tag',true);
            if($temparr) $arr=$temparr;
            else $arr=''; 
        }
        else $arr=''; 
        $this->ajaxreturn($arr); 
    }

    public function theme_management_show(){
        $id=I('id');
        $user_id=session('adminuser_id');
        if($user_id){
            if($id){
                $info=M('theme')->where('id=%d',$id)->find();
                $admin_nick=M('get_admin_theme')->where('theme_id=%d',$id)->getField('nick');
                $assist_nick=M('get_theme_list')->where('id=%d',$id)->getField('nick',true);
                $tag=M('get_theme_tag')->where('id=%d',$id)->getField('tag',true);
                $data=array('id'=>$id,'logo'=>$info['logo'],'tag'=>$tag,'theme'=>$info['theme'],'admin_nick'=>$admin_nick,'assist_nick'=>$assist_nick,'link'=>$info['link']);
                if($data['push']==1) $data['push']='';
                if(!$data['link']) $data['link']='';
                if($assist_nick) $data['assist_nick']=$assist_nick;
                else $data['assist_nick']='';
            }
        }
        $this->ajaxreturn($data); 
    }

    public function theme_propel(){
        $id=I('id');
        $propel_id=I('propel_id');
        $user_id=session('adminuser_id');
        if($user_id){
            if($id){
                $where['theme_id']=$id;
                M('user_theme')->where($where)->delete();
                if($propel_id){
                    foreach($propel_id as $value){
                        $data[]=array('user_id'=>$value,'theme_id'=>$id,'in_time'=>time());
                    }
                    if(M('user_theme')->addAll($data)) $arr=['state'=>'0','detail'=>'推送成功！'];
                }
                else $arr=['state'=>'0','detail'=>'推送成功！'];
            }
            else$arr=['state'=>'10003','detail'=>'参数错误！'];
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr);  
    }

    public function user_theme_list(){
        $user_id=session('adminuser_id');
        $theme_id=I('id');
        if($user_id){
            $allid=M('theme')->getField('id',true);
            if(in_array($theme_id,$allid)){
                $data=M('user_section')->order('id')->field('name as section,user_id as id')->select();
                $order_id=M('user_theme')->where('theme_id=%d',$theme_id)->getField('user_id',true);

                $userlist=M('user')->select();
                for($i=0;$i<count($data);$i++){
                    if(in_array($data[$i]['id'],$order_id)) $data[$i]['checked']=true;
                    else $data[$i]['checked']=false;
                    $key=array_search($data[$i]['id'], array_column($userlist, 'id'));
                    $data[$i]['name']=$userlist[$key]['nick'];
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
            else$arr=['state'=>'10003','detail'=>'参数错误！'];
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr);  
    }

    public function theme_delete(){
        $id=intval(I('id'));
        $user_id=session('adminuser_id');
        if($user_id){
            $allid=M('theme')->getField('id',true);
            if(in_array($id,$allid)){
                if(M('theme')->where('id=%d',$id)->delete()){
                    $tag_id=M('tag_theme')->where('theme_id=%d',$id)->getField('tag_id',true);
                    if($tag_id){
                        $where1['tag_id']=array('in',$tag_id);
                        M('user_tag')->where($where1)->delete();
                        $where2['id']=array('in',$tag_id);
                        M('tag')->where($where2)->delete();
                    }
                    $article_id=M('article')->where('theme_id=%d',$id)->getField('id',true);
                    M('admin_theme')->where('theme_id=%d',$id)->delete();
                    M('tag_theme')->where('theme_id=%d',$id)->delete();
                    M('user_theme')->where('theme_id=%d',$id)->delete();
                    M('theme_distribute')->where('theme_id=%d',$id)->delete();
                    if($article_id){
                        $where['id']=array('in',$article_id);
                        M('article')->where($where)->delete();
                        $condition['article_id']=array('in',$article_id);
                        $comment_id=M('comment')->where($condition)->getField('id',true);
                        if($comment_id){
                            $conditionc['comment_id']=array('in',$comment_id);
                            M('comment_like')->where($conditionc)->delete();
                        }
                        M('comment')->where($condition)->delete();
                        M('article_like')->where($condition)->delete();
                        M('article_tag')->where($condition)->delete();
                        M('user_article')->where($condition)->delete();
                        $vote_id=M('user_vote')->where('article_id=%d',$id)->getField('vote_id',true);
                        if($vote_id){
                            $conditionv['id']=array('in',$vote_id);
                            M('vote')->where($conditionv)->delete();
                            $conditionc['vote_id']=array('in',$vote_id);
                            $choice_id=M('vote_choice')->where($conditionc)->getField('id',true);
                            M('vote_choice')->where($conditionc)->delete();
                            if($choice_id){
                                $conditions['choice_id']=array('in',$choice_id);
                                M('vote_select')->where($conditions)->delete();
                            }
                        }
                        M('user_vote')->where($condition)->delete();
                        M('user_survey')->where($condition)->delete();
                        $arr['state']='0';
                        $arr['detail']='删除成功！';
                    }
                    else{
                        $arr['state']='0';
                        $arr['detail']='删除成功！';
                    }
                }
                else{
                    $arr['state']='10003';
                    $arr['detail']='参数错误！';
                }
            }
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr);  
    }

    public function get_base64($file,$path="Uploads/",$max_size=10485760){//获得base64文件
        if(!is_array($file)){
            $json=json_decode(htmlspecialchars_decode($file),true);
            if($json){
                $file=$json;
            }
            else{
                $json=$file;
                $file=array();
                $file[]=$json;
            }
        }
        $goal=array();
        $i=0;
        foreach($file as $value){
            $path_dir=$path.date("Y-m-d")."/";//目录
            if (!is_dir($path_dir)) mkdir($path_dir, 0777);
            $new_file = $path_dir.session("adminuser_id").uniqid("_").$i.".png";
            $i++;
            $binary=$this->urlsafe_b64decode($value);
            if(file_put_contents($new_file,$binary))
                $goal[]=$new_file;
        }
        return $goal;
    }
    
    private function check_type($type){//确定上传文件类型
        $type_arr=array('jpg', 'gif', 'png', 'jpeg');
        return in_array($type,$type_arr);
    }
    private function urlsafe_b64decode($string) {//url安全的base64解码
       $data = str_replace(array('-','_'),array('+','/'),$string);
       $mod4 = strlen($data) % 4;
       if ($mod4) {
           $data .= substr('====', $mod4);
       }
       return base64_decode($data);
    }


    public function base64(){//获得base64文件
        $file=I('file');
        $path="Uploads/";
        $max_size=10485760;
        if(!is_array($file)){
            $json=json_decode(htmlspecialchars_decode($file),true);
            if($json){
                $file=$json;
            }
            else{
                $json=$file;
                $file=array();
                $file[]=$json;
            }
        }
        $goal=array();
        $i=0;
        foreach($file as $value){
            $path_dir=$path.date("Y-m-d")."/";//目录
            if (!is_dir($path_dir)) mkdir($path_dir, 0777);
            $new_file = $path_dir.session("adminuser_id").uniqid("_").$i.".png";
            $i++;
            $binary=$this->urlsafe_b64decode($value);
            if(file_put_contents($new_file,$binary))
                $goal[]=$new_file;
        }
        $this->ajaxreturn($goal);
    }
    public function adminlist(){
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)){
                $tempdata=M('get_adminlist')->field('id,nick,theme as assist_nick,in_time')->order('in_time desc')->select();
                $data=splite_array($tempdata,'id','theme',array('nick','in_time'));
                for($i=0;$i<count($data);$i++){
                    $arr[]=array('id'=>$data[$i]['id'],'nick'=>$data[$i]['nick'],'in_time'=>date('Y-m-d H:i:s',$data[$i]['in_time']));
                    $t="";
                    foreach($data[$i]['theme'] as $value){
                        $t=$t.$value['assist_nick']." "; 
                    }
                    $arr[$i]['theme']=$t;
                }
            }
        }
        else{
            $arr='未登录！';
        }
        $this->ajaxreturn($arr);
    }

    public function admin_search(){
        $key=I('key');
        $user_id=session('adminuser_id');
        if($user_id){
            if($key) $condition['nick|theme']=array('like','%'.$key.'%');
            $tempdata=M('get_adminlist')->where($condition)->field('id,nick,theme as assist_nick,in_time')->select();
            $data=splite_array($tempdata,'id','theme',array('nick','in_time'));
            for($i=0;$i<count($data);$i++){
                $temparr[]=array('id'=>$data[$i]['id'],'nick'=>$data[$i]['nick'],'in_time'=>date('Y-m-d H:i:s',$data[$i]['in_time']));
                $t="";
                foreach($data[$i]['theme'] as $value){
                    $t=$t.$value['assist_nick']." "; 
                }
                $temparr[$i]['theme']=$t;
            }
            if($temparr){
                $arr['state']='0';
                $arr['order']=$temparr;
            }
            else{
                $arr['state']='10002';
                $arr['detail']='搜索为空！';
            }
        }
        else{
            $arr['state']='10000';
            $arr['detail']='未登录！';
        }
        $this->ajaxreturn($arr); 
    }


    public function admin_management(){
        $id=I('id');
        $nick=I('nick');
        $assist_user=array_unique(I('assist_user'));
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)){
                $allnick=M('admin')->getField('nick',true);
                if(in_array($nick,$allnick)){
                    $arr['state']='10070';
                    $arr['detail']='已存在名称相同的管理员！';
                }
                else{
                    if(!$id){
                        $data['nick']=$nick;
                        $data['type']=1;
                        $data['in_time']=time();
                        $id=M('admin')->add($data);
                        if($id){
                            if($assist_user){
                                for($i=0;$i<count($assist_user);$i++){
                                    $data1[$i]['user_id']=$id;
                                    $assist_user_id=M('theme')->where('theme="%s"',$assist_user[$i])->getField('id');
                                    $data1[$i]['theme_id']=$assist_user_id;
                                }
                                if(M('admin_theme')->addAll($data1)){
                                    $arr['state']='0';
                                    $arr['detail']='添加成功！';
                                }
                                else{
                                    $arr['state']='10001';
                                    $arr['detail']='系统异常！';
                                }
                            }
                            else{
                                $arr['state']='0';
                                $arr['detail']='添加成功！';
                            }
                        }
                        else{
                            $arr['state']='10001';
                            $arr['detail']='系统异常！';
                        }
                    }
                    else{
                        $allid=M('admin')->getField('id',true);
                        if(in_array($id,$allid)){
                            $admin_data['nick']=$nick;
                            if(M('admin')->where('id=%d',$id)->save($admin_data)){
                                if($assist_user){
                                    for($i=0;$i<count($assist_user);$i++){
                                        M('admin_theme')->where('user_id=%d',$id)->delete();
                                        $data1[$i]['user_id']=$id;
                                            $assist_user_id=M('theme')->where('theme="%s"',$assist_user[$i])->getField('id');
                                        $data1[$i]['theme_id']=$assist_user_id;
                                        if(M('admin_theme')->addAll($data)){
                                            $arr['state']='0';
                                            $arr['detail']='修改成功！';
                                        }
                                        else{
                                            $arr['state']='10001';
                                            $arr['detail']='系统异常！';
                                        }
                                    }
                                }
                                else{
                                    $arr['state']='0';
                                    $arr['detail']='修改成功！';
                                }
                            }
                            else{
                                $arr['state']='10001';
                                $arr['detail']='系统异常！';
                            }
                        }
                        else{
                            $arr['state']='10003';
                            $arr['detail']='参数错误！';
                        }
                    }
                }
            }
            else $arr=['state'=>'10010','detail'=>'用户无权限！'];
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr); 
    }

    
    public function admin_management_search(){
        $key=I('key');
        //$key="银监会";
        $user_id=session('adminuser_id');
        if($user_id){
            $condition['nick']=array('like','%'.$key.'%');
            $condition['type']=1;
            $data=M('admin')->where($condition)->getField('nick',true);
            if($data){
                $arr['state']='0';
                $arr['order']=$data;
            }
            else{
                $arr['state']='10002';
                $arr['detail']='搜索为空！';
            }
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr); 
    }

    public function admin_delete(){
        $id=I('id');
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)){
                $admin_nick=M('admin')->where('id=%d',$id)->getField('nick');
                $theme_id=M('admin_theme')->where('user_id=%d',$id)->getField('theme_id',true);
                $article_id=M('article')->where('author="%s"',$admin_nick)->getField('id',true);
                if(M('admin')->where('id=%d',$id)->delete()){
                    $where['theme_id']=array('in',$theme_id);
                    if($theme_id){
                        $tag_id=M('tag_theme')->where($where)->getField('tag_id',true);
                        if($tag_id){
                            $where1['tag_id']=array('in',$tag_id);
                            M('user_tag')->where($where1)->delete();
                            $where2['id']=array('in',$tag_id);
                            M('tag')->where($where2)->delete();
                        }
                        M('tag_theme')->where($where)->delete();
                        M('user_theme')->where($where)->delete();
                        M('theme_distribute')->where($where)->delete();
                    }
                    M('admin_theme')->where('user_id=%d',$id)->delete();
                    M('article')->where('author="%s"',$admin_nick)->delete();
                    if($article_id){
                        $condition['article_id']=array('in',$article_id);
                        $comment_id=M('comment')->where($condition)->getField('id',true);
                        if($comment_id){
                            $conditionc['comment_id']=array('in',$comment_id);
                            M('comment_like')->where($conditionc)->delete();
                        }
                        M('comment')->where($condition)->delete();
                        M('article_like')->where($condition)->delete();
                        M('article_tag')->where($condition)->delete();
                        M('user_article')->where($condition)->delete();
                        $vote_id=M('user_vote')->where('article_id=%d',$id)->getField('vote_id',true);
                        if($vote_id){
                            $conditionv['id']=array('in',$vote_id);
                            M('vote')->where($conditionv)->delete();
                            $conditionc['vote_id']=array('in',$vote_id);
                            $choice_id=M('vote_choice')->where($conditionc)->getField('id',true);
                            M('vote_choice')->where($conditionc)->delete();
                            if($choice_id){
                                $conditions['choice_id']=array('in',$choice_id);
                                M('vote_select')->where($conditions)->delete();
                            }
                        }
                        M('user_vote')->where($condition)->delete(); 
                        M('user_survey')->where($condition)->delete();
                    }
                    $arr['state']='0';
                    $arr['detail']='删除成功！';
                }
            }
            else{
                $arr['state']='10010';
                $arr['detail']='用户无权限！';
            }
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr);  
    }

    private function array_unique_fb($value) { 
         foreach ($value as $v) { 
             $v = join(",",$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串 
             $temp[] = $v; 
         } 
         $temp = array_unique($temp); //去掉重复的字符串,也就是重复的一维数组 
         foreach ($temp as $k => $v){ 
             $temp[$k] = explode(",",$v); //再将拆开的数组重新组装 
         } 
        return $temp; 
     } 


    public function modify_admin(){
        $id=I('id');
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)){
                $allid=M('admin')->where('type=1')->getField('id',true);
                if(in_array($id,$allid)){
                    $tempdata=M('get_adminlist')->field('id,nick,theme as assist_nick,in_time')->order('in_time desc')->where('id=%d',$id)->select();
                    $data=splite_array($tempdata,'id','theme',array('nick','in_time'));
                    for($i=0;$i<count($data);$i++){
                        $info=M('admin')->where('id=%d',$data[$i]['id'])->find();
                        $arr=array('id'=>$data[$i]['id'],'nick'=>$data[$i]['nick'],'in_time'=>date('Y-m-d H:i:s',$data[$i]['in_time']),'account'=>$info['account']);
                        $t="";
                        foreach($data[$i]['theme'] as $value){
                            $t=$t.$value['assist_nick']." "; 
                        }
                        $arr['theme']=$t;
                    }
                }
            }
        }
        else{
            $arr='未登录！';
        }  
        $this->ajaxreturn($arr);  
    }


    public function hotwords(){
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)){
                $arr=M('hotwords')->select();
            }
        }
        else{
            $arr='未登录！';
        }
        $this->ajaxreturn($arr); 
    }

    public function hotwords_management(){
        $id=I('id');
        $hotwords=I('hotwords');
        //$hotwords="测试";
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)){
                $allid=M('hotwords')->getField('id',true);
                if(!$id){
                    if($hotwords){
                        $allhotwords=M('hotwords')->getField('hotword',true);
                        $data['hotword']=$hotwords;
                        if(count($allid)>7){
                            $arr['state']='10035';
                            $arr['detail']='热词超过规定数量，请删除后在新增！';
                        }
                        else if(in_array($hotwords,$allhotwords)){
                            $arr['state']='10036';
                            $arr['detail']='热词已重复！';
                        }
                        else{
                            if(M('hotwords')->add($data)){
                                $arr['state']='0';
                                $arr['detail']='添加成功！';
                            }
                            else{
                                $arr['state']='10001';
                                $arr['detail']='系统异常！';
                            }
                        }
                        
                    }
                    else{
                        $arr['state']='10002';
                        $arr['detail']='输入不能为空！';
                    }
                }
                else{
                    if(M('hotwords')->where('id=%d',$id)->delete()){
                        $arr['state']='0';
                        $arr['detail']='删除成功！';
                    }
                    else{
                        $arr['state']='10001';
                        $arr['detail']='系统异常！';
                    }
                }
            }
             else{
                $arr['state']='10010';
                $arr['detail']='用户无权限！';
            }
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr);  
    }

    public function httpPost($post_data,$url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));//传递数组
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $post_data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);//设置过期时间
        $code=curl_exec($ch);
        curl_close($ch);
        return $code;
    }

    public function article_input(){ 
        $id=I('id');
        $title=I('title');
        $text=I('text');
        $user_id=session('adminuser_id');
        $type=I('type');
        $theme_id=I('theme_id');
        $tempinfo=I('info');
        $tag_id=array_unique(I('tag_id'));
        $function=I('function');//1-普通3-左标题右图片2-多图4-视频5-外链
        $width=I('width');
        $heighth=I('heighth');
        $link=I('link');
        $state=I('state',2,'intval');//文章是否存为草稿  1-草稿不显示  2-正常显示
        $push=I('push','0','intval');//推送按钮0-否1-app推送2-短信推送
        $author=I('author');
        $receipt=I('receipt');
        $end_time=I('end_time');
        $receipt_title=I('receipt_title');
        $question=I('question');
        $isnote=I('isnote');
        $user_list=I('user_list');
        // $user_list=[10153,10156];
        // $question=[[['text'=>'1.请输入项目标题','is_single_choice'=>1,'children'=>[['text'=>'方大同热特热特'],['text'=>'热特热 人'],['text'=>'他热特特']]],['text'=>'1.请输入项目标题','is_single_choice'=>1,'children'=>[['text'=>'方大同热特热特'],['text'=>'热特热 人'],['text'=>'他热特特']]]]];
        if(!$user_id) $arr=array('state'=>'10000','detail'=>'未登录！');
        else if(!($title&&in_array($type,array('0',1))&&$theme_id&&in_array($function,array(1,2,3,4,5))&&in_array($state,array(1,2))&&in_array($push,array('0',1,2,3)))) $arr=array('state'=>'10003','detail'=>'参数错误！');
        else if(count($tag_id)>3) $arr=array('state'=>'10025','detail'=>'标签数量须控制在3个以内！');
        else if($link&&!checkWebAddr($link)) $arr=array('state'=>'10100','detail'=>'请输入合法的外链的地址！');
        else if(!$tempinfo[0]&&in_array($function,array(1,2,4))) $arr=array('state'=>'10008','detail'=>'只有左文右图类新闻可以不上传图片！');
        else if($user_list&&!D('portal')->check_id(M('user'),$user_list,1)) $arr=array('state'=>'10003','detail'=>'参数错误！');
        else{
            $data1=array('title'=>$title,'type'=>$type,'text'=>$text,'theme_id'=>$theme_id,'function'=>$function,'state'=>$state,'push'=>$push,'end_time'=>$end_time,'isnote'=>$isnote);
            if(!$author) $data1['author']=M('get_admin_theme')->where('theme_id=%d',$theme_id)->getField('nick');
            else $data1['author']=$author;
            if($user_list) $data1['isshow']=0;
            else $data1['isshow']=1;
            if($link) $data1['link']=$link;
            if($receipt_title) $data1['receipt_title']=$receipt_title;
            $path_dir='/Uploads/'.date("Y-m-d")."/";//目录
            if (!is_dir($path_dir)) mkdir($path_dir, 0777);
            $maxwidth=$width;//设置图片的最大宽度
            $maxheight=$heighth;//设置图片的最大高度
            $filetype=".jpeg";//图片类型
            if($function==3){
                if($tempinfo){
                    if(strlen($tempinfo[0])>100){
                        $info=$this->get_base64($tempinfo);
                        $data1['photo']='/'.$info[0];
                    }
                    else $data1['photo']=$tempinfo[0];
                    $im=$this->convertPic('.'.$data1['photo']);
                    $filename=$path_dir.uniqid("_");
                    $name='.'.$filename;//图片的名称，随便取吧
                    if(resizeImage($im,$maxwidth,$maxheight,$name,$filetype)) $data1['compress_photo']=$filename.$filetype;
                }
            }
            else if($function==1){
                if(strlen($tempinfo[0])>100){
                    $info=$this->get_base64($tempinfo[0]);
                    $data1['photo']='/'.$info[0];
                }
                else $data1['photo']=$tempinfo[0];
                if(strlen($tempinfo[0])>100){
                    $info=$this->get_base64($tempinfo[0]);
                    $data1['photo']='/'.$info[0];
                }
                else $data1['photo']=$tempinfo[0];

                if(strlen($tempinfo[1])>100){
                    $info=$this->get_base64($tempinfo[1]);
                    $data1['photo2']='/'.$info[0];
                }
                else $data1['photo2']=$tempinfo[1];

                if(strlen($tempinfo[2])>100){
                    $info=$this->get_base64($tempinfo[2]);
                    $data1['photo3']='/'.$info[0];
                }
                else $data1['photo3']=$tempinfo[2];

                $im1=$this->convertPic('.'.$data1['photo']);
                $filename1=$path_dir.uniqid("_");
                $name1='.'.$filename1;
                if(resizeImage($im1,$maxwidth,$maxheight,$name1,$filetype)) $data1['compress_photo']=$filename1.$filetype;
                    
                $im2=$this->convertPic('.'.$data1['photo2']);
                $filename2=$path_dir.uniqid("_");
                $name2='.'.$filename2;
                if(resizeImage($im2,$maxwidth,$maxheight,$name2,$filetype))  $data1['compress_photo2']=$filename2.$filetype;
                
                $im3=$this->convertPic('.'.$data1['photo3']);
                $filename3=$path_dir.uniqid("_");
                $name3='.'.$filename3;
                if(resizeImage($im3,$maxwidth,$maxheight,$name3,$filetype)) $data1['compress_photo3']=$filename3.$filetype;
            }
            else{
                if(strlen($tempinfo[0])>100){
                    $info=$this->get_base64($tempinfo);
                    $data1['photo']='/'.$info[0];
                }
                else $data1['photo']=$tempinfo[0];
                $im=$this->convertPic('.'.$data1['photo']);
                $filename=$path_dir.uniqid("_");
                $name='.'.$filename;//图片的名称，随便取吧
                if(resizeImage($im,$maxwidth,$maxheight,$name,$filetype)) $data1['compress_photo']=$filename.$filetype;
            }
            M('')->startTrans();
            if(!D('backstage')->check_article($id)){
                $data1['in_time']=time();
                $id=M('article')->add($data1);
                if($tag_id){
                    for($i=0;$i<count($tag_id);$i++){
                        $data2[$i]['article_id']=$id;
                        // $data2[$i]['tag_id']=M('get_theme_tag')->where('id=%d and tag="%s"',$theme_id,trim($tag[$i]))->getField('tag_id');
                        $data2[$i]['tag_id']=$tag_id[$i];
                    }
                    $result=$id&&M('article_tag')->addAll($data2);
                }
                else $result=$id;

                if($receipt){
                    for($i=0;$i<count($receipt);$i++){
                        $data4[$i]['article_id']=$id;
                        $data4[$i]['name']=$receipt[$i];
                    }
                    $result=$result&&M('article_receipt')->addAll($data4);
                }

                //问卷
                if($question) $result=$result&&D('vote')->vote_publish($id,$question);

                //消息必达
                if($user_list){
                    foreach ($user_list as $key => $value) {
                        $note[]=['user_id'=>$value,'article_id'=>$id];
                    }
                    $result=$result&&M('article_note')->addAll($note);
                }

                if($function==2){
                    for($i=0;$i<count($tempinfo);$i++){
                        $data3[$i]['article_id']=$id;
                        $photo=$this->get_base64($tempinfo[$i][0]);
                        $data3[$i]['photo']='/'.$photo[0];
                        $data3[$i]['text']=$tempinfo[$i][1];
                        $im=$this->convertPic('.'.$data3[$i]['photo']);
                        $filename=$path_dir.session("adminuser_id").uniqid("_");
                        $name='.'.$filename;//图片的名称，随便取吧
                        if(resizeImage($im,$maxwidth,$maxheight,$name,$filetype)) $data3[$i]['compress_photo']=$filename.$filetype;
                    }
                    $result=$result&&M('photo')->addAll($data3);
                }
                if($state==2&&!D('backstage')->adminstrator_judge($user_id))  $result=$result&&D('contribute')->save_back_contribute($user_id);
            }
            else{
                $info=M('article')->where('id=%d',$id)->find();
                if($info['state']==1&&$state==2) $data1['in_time']=time();
                if(!(M('article')->where('id=%d',$id)->save($data1)===false)){
                    M('article_tag')->where('article_id=%d',$id)->delete();
                    if($tag_id){
                        for($j=0;$j<count($tag_id);$j++){
                            $data2[$j]['article_id']=$id;
                            // $data2[$j]['tag_id']=M('get_theme_tag')->where('id=%d and tag="%s"',$theme_id,trim($tag[$j]))->getField('tag_id');
                            $data2[$j]['tag_id']=$tag_id[$j];
                        }
                        $result=$id&&M('article_tag')->addAll($data2);
                    }
                    else $result=$id;
                    
                    M('article_receipt')->where('article_id=%d',$id)->delete();
                    if($receipt){
                        for($i=0;$i<count($receipt);$i++){
                            $data4[$i]['article_id']=$id;
                            $data4[$i]['name']=$receipt[$i];
                        }
                        $result=$result&&M('article_receipt')->addAll($data4);
                    }

                    //问卷
                    M('vote')->where('article_id=%d',$id)->delete();
                    if($question) $result=$result&&D('vote')->vote_publish($id,$question);

                    //消息必达
                    M('article_note')->where('article_id=%d',$id)->delete();
                    if($user_list){
                        foreach ($user_list as $key => $value) {
                            $note[]=['user_id'=>$value,'article_id'=>$id];
                        }
                        $result=$result&&M('article_note')->addAll($note);
                    }

                    if($function==2){
                        M('photo')->where('article_id=%d',$id)->delete();
                        for($i=0;$i<count($tempinfo);$i++){
                            $data3[$i]['article_id']=$id;
                            if(strlen($tempinfo[$i][0])>100){
                                $photo=$this->get_base64($tempinfo[$i][0]);
                                $data3[$i]['photo']='/'.$photo[0];
                            }
                            else $data3[$i]['photo']=$tempinfo[$i][0];
                            $data3[$i]['text']=$tempinfo[$i][1];
                            $im=$this->convertPic('.'.$data3[$i]['photo']);
                            $filename=$path_dir.session("adminuser_id").uniqid("_");
                            $name='.'.$filename;//图片的名称，随便取吧
                            if(resizeImage($im,$maxwidth,$maxheight,$name,$filetype)) $data3[$i]['compress_photo']=$filename.$filetype;
                        }
                        $result=$result&&M('photo')->addAll($data3);
                    }
                    if($state==2&&$info['cuser_id']&&$info['state']==1)  $result=$result&&D('contribute')->save_incontribute($info['cuser_id']);
                }
                else $arr=array('state'=>'10001','detail'=>'系统异常！');
            }
            //推送
            if(($push&1)!=0){
                $data=M('article')->where('id=%d',$id)->find();
                if($data['schedule']==100){
                    $article_data['schedule']=0;
                    M('article')->where('id=%d',$id)->save($article_data);//重置文章进度
                }
            }
            
            if($result!==false){
                M('')->commit();
                $arr=array('state'=>'0','detail'=>'提交成功！');
                if(!$user_list) $list=D('backstage')->get_theme_order($theme_id);
                else $list=$user_list;//自选配送范围
                $ispushlist=M('article_push')->where('article_id=%d',$id)->getField('user_id',true);
                if($ispushlist)  $list=array_diff($list,$ispushlist);
                if(!empty($list)&&($push&1)!=0){
                    $post_data=array('article_id'=>$id,'list'=>implode(',',$list),'data'=>'inner');
                    $ret=$this->httpPost($post_data,$_SERVER['HTTP_HOST'].$this->url);
                }
                if($type==1){
                    $post_data=array('data'=>'inner');
                    $ret=$this->httpPost($post_data,$_SERVER['HTTP_HOST'].$this->updateurl);
                }
            }
            else{
                M('')->rollback();
                $arr=array('state'=>'10001','detail'=>'系统异常！');
            }
        }
        $this->ajaxreturn($arr);
    }


    public function show(){
        $id=I('id');
        $allid=M('article')->getField('id',true);
        $user_id=session('adminuser_id');
        if(in_array($id,$allid)){
            $data=M('article')->where('id=%d',$id)->find();
            $admin=M('get_admin_theme')->where('theme_id=%d',$data['theme_id'])->find();
            $order['detail']=array('id'=>$data['id'],'title'=>$data['title'],'type'=>$data['type'],'photo'=>$data['photo'],'photo2'=>$data['photo2'],'photo3'=>$data['photo3'],'theme_id'=>$data['theme_id'],'function'=>$data['function'],'link'=>$data['link'],'text'=>html_entity_decode($data['text']),'state'=>$data['state'],'push'=>$data['push'],'admin_nick'=>$admin['nick'],'main_user_id'=>$admin['id']);
            if(!$data['in_time']) $order['detail']['in_time']=date('Y-m-d H:i:s',$data['time']);
            if($data['end_time']) $order['detail']['end_time']=date('Y-m-d H:i:s',$data['end_time']);
            else $order['detail']['in_time']=date('Y-m-d H:i:s',$data['in_time']);
            if($data['function']==2){
                $photo=M('get_photo')->where('id=%d',$id)->field('photo_photo,photo_text')->select();
                for($i=0;$i<count($photo);$i++){
                    $order['photo_photo'][$i]['photo']=$photo[$i]['photo_photo'];
                    $order['photo_photo'][$i]['text']=$photo[$i]['photo_text'];
                }
            }
            if($data['cuser_id']){
                $info=M('user')->where('id=%d',$data['cuser_id'])->find();
                $order['detail']['cuser']=$info['section'].' '.$info['nick'];
            }
            $tag=M('article_tag_name')->where('article_id=%d',$id)->field('tag_id,tag')->select();
            if($tag[0]['tag_id']) $order['tag']=$tag;

            $receipt=M('article_receipt')->where('article_id=%d',$id)->field('id,name')->select();
            if($receipt) $order['receipt']=$receipt;
            if($data['receipt_title']) $order['receipt_title']=$data['receipt_title'];

            if(M('vote')->where('article_id=%d',$id)->find()) $order['question']=D('vote')->vote_modify_info($id);
        } 
        $this->ajaxreturn($order);
    }

    public function article_input_tag_show(){
        $theme_id=I('theme_id');
        $condition['id']=$theme_id;
        $data=M('get_theme_tag')->where($condition)->select();
        if($data){
            $arr['state']='0';                
            $arr['order']=$data;
        }
        else{
            $arr['state']='10021';
            $arr['detail']='搜索为空！';
        }
        $this->ajaxreturn($arr);
    }

    public function article_input_tag_search(){
        $theme_id=I('theme_id');
        $key=I('key');
            if($key){
                $condition['tag']=array('like','%'.$key.'%');
                $condition['id']=$theme_id;
                $data=M('get_theme_tag')->where($condition)->select();
                if($data){
                    $arr['state']='0';
                    $arr['order']=$data;
                }
                else{
                    $arr['state']='10021';
                    $arr['detail']='搜索为空！';
                }
            }
        else{
            $arr['state']='10002';
            $arr['detail']='输入不能为空！';
        }
        $this->ajaxreturn($arr);
    }

    Public function upload(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     10485760 ;// 设置附件上传大x小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg','flv', 'swf', 'mkv', 'avi', 'rm', 'rmvb', 'mpeg', 'mpg','ogg', 'ogv', 'mov', 'wmv', 'mp4', 'webm', 'mp3', 'wav', 'mid');//设置附件上传类型
        //$upload->savePath  =      '/Uploads/'; // 设置附件上传目录// 上传文件
        $tempinfo   =   $upload->upload();
        if($tempinfo) {// 上传错误提示错误信息
            foreach($tempinfo as $key=>$file){ 
                $info[]=array('file'=>$file['savepath'].$file['savename'],'name'=>$key);}
            echo json_encode($info);
        }
    }

    public function tagslist(){
        $user_id=session('adminuser_id');
        if($user_id){
            $arr=M('tag')->order('id desc')->where($condition)->select();
        }
        else{
            $arr='';
        }
        $this->ajaxreturn($arr); 
    }

    public function tag_theme_search(){
        $key=I('key');
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)){
                $condition['theme|admin_nick']=array('like','%'.$key.'%');
            }
            else{
                $where['main_user_id|user_id']=$user_id;
                $alltheme_id=M('get_theme_list')->where($where)->getField('id',true);
                if($alltheme_id){
                    $condition['theme_id']=array('in',$alltheme_id);
                    $condition['theme|admin_nick']=array('like','%'.$key.'%');
                }
            }
            if($condition){
                if($key){
                    $tempdata=M('get_theme_list')->field('admin_nick,theme,id')->order('id desc')->where($condition)->select();
                    $data=array_values($this->array_unique_fb($tempdata));
                    for($i=0;$i<count($data);$i++){
                        $theme[]=array('id'=>$data[$i][2],'theme'=>$data[$i][0].$data[$i][1]);
                    }
                }
            }
        }
        else{
            $theme='未登录！';
        }
        $this->ajaxreturn($theme);   
    }
    
    public function taglist1(){
        $user_id=session('adminuser_id');
        if($user_id){
             if(D('backstage')->adminstrator_judge($user_id)){
                $condition=1;
            }
            else{
                $where['main_user_id|user_id']=$user_id;
                $list=array_unique(M('get_tag_list')->where($where)->getField('tag_id',true));
                $alllist=M('tag')->getField('id',true);
                $array=array_diff($alllist,$list);
                $condition['tag_id']=array('not in',$array);
            }
            $tempdata=M('get_tag_list')->where($condition)->order('id')->field('tag,tag_id,theme,admin_nick')->group('tag_id')->select();
            for($i=0;$i<count($tempdata);$i++){
                $newdata[$i]['name']=$tempdata[$i]['admin_nick'].$tempdata[$i]['theme'];
                $newdata[$i]['tag']=$tempdata[$i]['tag'];
                $newdata[$i]['tag_id']=$tempdata[$i]['tag_id'];
                $newdata[$i]['count']=count(M('article_tag_name')->where('tag="%s"',$tempdata[$i]['tag'])->getField('id',true));
            }
            $data=splite_array($newdata,'name','children');
            if($data){
                $arr['state']='0';
                $arr['order']=$data;
            }
            else{
                $arr['state']='10021';
                $arr['detail']='没有相关内容！';
            }
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr);
    }

    public function tagsmanagement(){
        $tag=filterEmoji(I('tag'));
        // dump(emoji_test($tag));
        $theme_id=I('id');
        $user_id=session('adminuser_id');
        if($user_id){
            if($tag&&$theme_id){
                $alltag=M('get_theme_tag')->where('id=%d',$theme_id)->getField('tag',true);
                if(!in_array($tag,$alltag)){
                    $data['tag']=$tag;
                    $id=M('tag')->add($data);
                    if($id){
                        $data1['tag_id']=$id;
                        $data1['theme_id']=$theme_id;
                        if(M('tag_theme')->add($data1)){
                            $arr['state']='0';
                            $arr['order']=$id;
                        }
                        else{
                            $arr['state']='10001';
                            $arr['detail']='系统异常！';
                        }
                    }
                    else{
                        $arr['state']='10001';
                        $arr['detail']='系统异常！';
                    }
                }
                else{
                    $arr['state']='10005';
                    $arr['detail']='该栏目下已有相同名称标签！';
                }
            }
            else{
                $arr['state']='10021';
                $arr['detail']='输入不能为空！';
            }
        }          
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr); 
    }

    public function tagsdelete(){
        $tag_id=I('id');
        $user_id=session('adminuser_id');
        $allid=M('tag')->getField('id',true);
        if($user_id){
            if(in_array($tag_id,$allid)){
                if(M('tag')->where('id=%d',$tag_id)->delete()){
                    $article_id=M('article_tag')->where('tag_id=%d',$tag_id)->getField('article_id',true);
                    if($article_id){
                        $condition['id']=array('in',$article_id);
                        M('article')->where($condition)->delete();
                    }
                    M('user_tag')->where('tag_id=%d',$tag_id)->delete();
                    $arr['state']='0';
                    $arr['detail']='删除成功！';
                }
                else{
                    $arr['state']='10001';
                    $arr['detail']='系统异常！';
                }
            }
            else$arr=['state'=>'10003','detail'=>'参数错误！'];
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr); 
    }

    public function tagssearch(){
        $key=I('key');
        $user_id=session('adminuser_id');
        if($user_id){
            if($key){
                $condition['tag']=array('like','%'.$key.'%');
                $data=M('tag')->where($condition)->select();
                if($data){
                    $arr['state']='0';
                    $arr['detail']=$data;
                }
                else{
                    $arr['state']='10021';
                    $arr['detail']='搜索为空！';
                }
            }
            else{
                $arr['state']='10002';
                $arr['detail']='输入为空！';
            }
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr); 
    }

    Public function vote(){
        $vote=I('vote');
        $end=strtotime(I('end'));
        $choice=I('choice');
        $user_id=session('adminuser_id');
        if($user_id){
            if($vote&&$end&&$choice){
                $data1['vote']=$vote;
                $data1['end']=$end;
                $data1['start']=time();
                $vote_id=M('vote')->add($data1);
                if($vote_id){
                    for($j=0;$j<count($choice);$j++){
                        $data3[$j]['vote_id']=$vote_id;
                        $data3[$j]['choice']=$choice[$j][0];
                        if($choice[$j][1]){
                            $photo=$this->get_base64($choice[$j][1]);
                            $data3[$j]['choice_photo']='/'.$photo[0];
                        }
                        else{
                            $data3[$j]['choice_photo']='';
                        }
                    }
                    if(M('vote_choice')->addAll($data3)){
                        $arr['state']='0';
                        $arr['detail']=$vote_id;
                    }
                }
                else{
                    $arr['state']='10001';
                    $arr['detail']='系统异常！';
                }
            }
            else$arr=['state'=>'10003','detail'=>'参数错误！'];
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr); 
    }

    Public function vote_list(){
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)) $condition=1;
            else{
                $where['main_user_id|user_id']=$user_id;
                $alltheme_id=M('get_theme_list')->where($where)->getField('id',true);
                $condition['theme_id']=array('in',$alltheme_id);
            }
            $data=M('get_vote_select')->where($condition)->order('id desc')->field('id,vote,start,end,author,title,state,user_id,article_id,function')->select();
            for($i=0;$i<count($data);$i++){
                if($data[$i]['end']<time()&&$data[$i]['state']==2){
                    $vote_data['state']=1;
                    M('vote')->where('id=%d',$data[$i]['id'])->save($vote_data);
                }
                if($data[$i]['state']==1) $data[$i]['state']='已结束';
                else $data[$i]['state']='进行中';
            }
            $order=splite_array($data,'id','detail',array('id','vote','start','end','state','author','title','article_id','function'));
            for($j=0;$j<count($order);$j++){

                $list[]=array('id'=>$order[$j]['id'],'vote'=>$order[$j]['vote'],'count'=>count($order[$j]['detail']),'author'=>$order[$j]['author'],'state'=>$order[$j]['state'],'start'=>date('Y-m-d H:i:s',$order[$j]['start']),'end'=>date('Y-m-d H:i:s',$order[$j]['end']),'title'=>$order[$j]['title'],'article_id'=>$order[$j]['article_id'],'function'=>$order[$j]['function']);
            }
            $arr['state']='0';
            $arr['detail']=$list;
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxreturn($arr); 
    }

    public function vote_search(){
        $key=I('key');
        $user_id=session('adminuser_id');
        if($key){
            if($user_id){
                if(D('backstage')->adminstrator_judge($user_id)){
                    $condition['vote|title|author']=array('like','%'.$key.'%');
                }
                else{
                    $where['main_user_id|user_id']=$user_id;
                    $alltheme_id=M('get_theme_list')->where($where)->getField('id',true);
                    $condition['theme_id']=array('in',$alltheme_id);
                    $condition['vote|title|author']=array('like','%'.$key.'%');
                }

                $data=M('get_vote_select')->where($condition)->order('id desc')->field('id,vote,start,end,author,title,state,user_id,article_id,function')->select();
                for($i=0;$i<count($data);$i++){
                    if($data[$i]['end']<time()){
                        $vote_data['state']=1;
                        M('vote')->where('id=%d',$data[$i]['id'])->save($vote_data);
                    }
                    if($data[$i]['state']==1){
                        $data[$i]['state']='已结束';
                    }
                    else{
                        $data[$i]['state']='进行中';
                    }
                }
                $order=splite_array($data,'id','detail',array('id','vote','start','end','state','author','title','article_id','function'));
                for($j=0;$j<count($order);$j++){
                    $list[]=array('id'=>$order[$j]['id'],'vote'=>$order[$j]['vote'],'count'=>count($order[$j]['detail']),'author'=>$order[$j]['author'],'state'=>$order[$j]['state'],'start'=>date('Y-m-d H:i:s',$order[$j]['start']),'end'=>date('Y-m-d H:i:s',$order[$j]['end']),'title'=>$order[$j]['title'],'article_id'=>$order[$j]['article_id'],'function'=>$order[$j]['function']);
                }
                $arr['state']='0';
                $arr['detail']=$list;
            }
            else{
                $arr['state']='10000';
                $arr['detail']='未登录！';
            }
        }
        else{
            $arr['state']='10002';
            $arr['detail']='输入不能为空！';
        }
        $this->ajaxreturn($arr); 
    }

    public function vote_detail(){
        $id=I('id');
        $user_id=session('adminuser_id');
        $allid=M('vote')->getField('id',true);
        if(in_array($id,$allid)){
            if($user_id){
                $data=M('get_vote_count')->where('id=%d',$id)->field('id,vote,start,end,count,choice,choice_id,choice_photo')->select();
                for($i=0;$i<count($data);$i++){
                    $data[$i]['start']=date('Y-m-d H:i:s',$data[$i]['start']);
                    $data[$i]['end']=date('Y-m-d H:i:s',$data[$i]['end']);
                }
                $order=splite_array($data,'id','detail',array('vote','start','end'));
                $arr['state']='0';
                $arr['order']=$order;
            }
            else{
                $arr['state']='10000';
                $arr['detail']='用户未登录！';
            }
        }
        else{
            $arr['state']='10003';
            $arr['detail']='参数错误！';
        }
        $this->ajaxreturn($arr);
    }

    public function user_vote_list(){
        $choice_id=I('id');
        $user_id=session('adminuser_id');
        if($user_id){
            $allchoice_id=M('vote_choice')->getField('id',true);
            if(in_array($choice_id,$allchoice_id)){
                $data=M('get_vote_select')->where('choice_id=%d',$choice_id)->getField('nick',true);
                if($data[0]){
                    $arr['state']='0';
                    $arr['order']=$data;
                }
                else{
                    $arr['state']='10021';
                    $arr['order']='结果为空！';
                }
            }
            else$arr=['state'=>'10003','detail'=>'参数错误！'];
        }
        else{
            $arr['state']='10000';
            $arr['detail']='用户未登录！';
        }
        $this->ajaxreturn($arr);
    }

    public function vote_delete(){
        $id=I('id');
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)){
                $condition=1;
            }
            else{
                $where['main_user_id|user_id']=$user_id;
                $alltheme_id=M('get_theme_list')->where($where)->getField('id',true);
                $condition['theme_id']=array('in',$alltheme_id);
            }
            $allid=M('get_vote_select')->where($condition)->getField('id',true);
            if(in_array($id,$allid)){
                if(M('vote')->where('id=%d',$id)->delete()){
                    $vote_choice_id=M('vote_choice')->getField('id',true);
                    $conditionc['choice_id']=array('in',$vote_choice_id);
                    M('vote_choice')->where('vote_id=%d',$id)->delete();
                    M('vote_select')->where($conditionc)->delete();
                    $conditions['vote_id']=$id;
                    M('user_vote')->where($conditions)->delete();
                    $arr['state']='0';
                    $arr['detail']='删除成功！';
                }
            }
            else$arr=['state'=>'10003','detail'=>'参数错误！'];
        }
        else{
            $arr['state']='10000';
            $arr['detail']='用户未登录！';
        }
        $this->ajaxreturn($arr);
    }


    public function vote_modify(){
        $id=I('id');
        $vote=I('vote');
        $end=strtotime(I('end'));
        $start=strtotime(I('start'));
        $choice=I('choice');
        $state=I('state');
        $user_id=session('adminuser_id');
        if($user_id){
            if(D('backstage')->adminstrator_judge($user_id)){
                $condition=1;
            }
            else{
                $where['main_user_id|user_id']=$user_id;
                $alltheme_id=M('get_theme_list')->where($where)->getField('id',true);
                $condition['theme_id']=array('in',$alltheme_id);
            }
            $allid=M('get_vote_select')->where($condition)->getField('id',true);
            if(in_array($id,$allid)){
                $data1['vote']=$vote;
                $data1['start']=$start;
                $data1['end']=$end;
                $data1['state']=$state;
                if(M('vote')->where('id=$d',$id)->save($data1)){
                    if($choice){
                        M('vote_choice')->where('vote_id=%d',$id)->delete();
                        for($j=0;$j<count($choice);$j++){
                            $data3[$j]['vote_id']=$vote_id;
                            $data3[$j]['choice']=$choice[$j][1];
                            $photo=$this->get_base64($choice[$j][0]);
                            $data3[$j]['choice_photo']='/'.$photo[0];
                        }
                        if(M('vote_choice')->addAll($data3)){
                            $arr['state']='0';
                            $arr['detail']='修改成功！';
                        }
                    }
                    else{
                        $arr['state']='0';
                        $arr['detail']='修改成功！';
                    }
                }
            }
            else$arr=['state'=>'10003','detail'=>'参数错误！'];
        }
        else{
            $arr['state']='10000';
            $arr['detail']='用户未登录！';
        }
        $this->ajaxreturn($arr);
    }

    private function convertPic($img_base){
        // $img_base="./Uploads/2016-07-20/3_578f17aedf1c00.png";
        ini_set('memory_limit', '1024M'); // handle large images
        list($w_src, $h_src, $type) = getimagesize($img_base); // create new dimensions, keeping aspect ratio
        switch ($type){
        case 1: // gif -> jpg
        $img_src = imagecreatefromgif($img_base);
        break;
        case 2: // jpeg -> jpg
        $img_src = imagecreatefromjpeg($img_base);
        break;
        case 3: // png -> jpg
        $img_src = imagecreatefrompng($img_base);
        break;
        }
        return $img_src;
    }


    private function photo_base64($oldfile){
        // $file="/Uploads/2016-07-20/3_578f2881194af0.png";  
        $file='.'.$oldfile;
        $base64_image = '';
        $image_info = getimagesize($file);
        $image_data = fread(fopen($file, 'r'), filesize($file));
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        return $base64_image;
    }

    public function sectionlist(){
        $user_id=session('adminuser_id');
        if($user_id){
            $tempdata = M('admin')->where('type=1')->field('id,nick')->select();
            for($i=0;$i<count($tempdata);$i++){
                $data[$i]['word'] = substr(ucfirst(pinyin2($tempdata[$i]['nick'])),0,1);
                $data[$i]['name'] =$tempdata[$i]['nick'];
                $data[$i]['id']=$tempdata[$i]['id'];
            }
            $newArr=array();
            for($j=0;$j<count($data);$j++){
                $newArr[]=$data[$j]['word'];
            }
            array_multisort($newArr,$data);
            $order=splite_array($data,'word','parent');
            if($order){
                $arr['state']='0';
                $arr['order']=$order;
            }
            else{
                $arr['state']='10021';
                $arr['detail']='结果为空！';
            }
        }
        else  $arr=['state'=>'10000','detail'=>'未登录！'];
        $this->ajaxReturn($arr);
    }


    private function get_all_city($city){
        if (is_array($city)){
            for ($i=A;$i<=Z;$i++){ 
                foreach ($city as $k=>$vo){ 
                    if ($i == $vo['word']) $array[$i][$k] = $vo;
                }
                if($i == Z) break;
            }
        } 
        return $array;
    }
}






