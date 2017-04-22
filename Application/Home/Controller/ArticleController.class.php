<?php
namespace Home\Controller;
use Think\Controller;
class ArticleController extends Controller {

    public function ArticleOff(){
        $id=I('id');
        $user_id=session('adminuser_id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if(!D('portal')->check_id(M('article'),$id,['state'=>2])) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            $save['state']=1;
            $result=M('article')->where('id=%d',$id)->save($save);
            if($result) {
                $condition['article_id']=$id;
                M('article_note')->where($condition)->delete();
                M('article_receipt_answer')->where($condition)->delete();
                $vote_id=M('vote')->where($condition)->getField('id',true);
                if($vote_id){
                    $question_id=M('vote_question')->where('vote_id=%d',$vote_id)->getField('id',true);
                    $conditionm['question_id']=['in',$question_id];
                    M('vote_select')->where($conditionm)->delete();
                }
                $arr=['state'=>'0','detail'=>'下架成功！'];
            }
            else $arr=['state'=>'10001','detail'=>'下架失败！'];
        }
        $this->ajaxReturn($arr);
    }

    public function lists(){
        $number=I('number',10,'intval');
        $page=I('page',1,'intval');
        $theme_id=I('theme_id');
        $portal_id=I('portal_id');
        $key=I('key',2,'intval');
        $user_id=session('user_id');
        $isthemeclick=I('isthemeclick',1,'intval');
        $tag_id=I('tag_id');
        $keyword=I('keyword');
        // $theme_id=[146];
        // if(!$user_id) $arr=['state'=>10000,'detail'=>'未登录！'];
        if($theme_id&&!D('portal')->check_id(M('theme'),$theme_id,$condition)) $arr=['state'=>10003,'detail'=>'栏目id参数错误！'];
        else if($portal_id&&!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>10003,'detail'=>'门户id参数错误！'];
        else{
            $User=M('article_order');
            $condition=['key'=>1,'state'=>2,'isshow'=>1];//除轮播图和草稿外文章
            if($user_id){
                $list=M('article')->where($condition)->getField('id',true);
                $note_list=M('article_note')->where('user_id=%d',$user_id)->getField('article_id',true);
                if($note_list){
                    $list=array_merge($list,$note_list);
                    $condition=['id'=>['in',$list]];
                }
            }

            if($user_id&&$isthemeclick==2) D('portal')->portal_user_theme($user_id,$theme_id);//记录用户点击栏目时间
            if($tag_id) {
                $condition['tag_id']=$tag_id;
                if($user_id) D('portal')->user_tag_click($user_id,$tag_id);
            }

            if($keyword) {//搜索需传门户id参数
                $User=M('get_article');
                $condition['title|author|theme']=['like','%'.$keyword.'%'];
            }
            
            if($portal_id) {
                $theme=D('portal')->portal_theme($portal_id,$user_id);
                $theme_id=array_column($theme['theme'],'id');
            }
            if($theme_id){
                $condition['theme_id']=['in',$theme_id];
                $order=D('theme')->article_list($User,$condition,$page,$number,$key,$user_id);
                if(!$order) $order=[];
            }
            else $order=[];
            $arr=['state'=>0,'order'=>$order];
        }
        $this->ajaxreturn($arr);
    }

    //文章分页输出
    public function ajaxarticle(){
        $user_id=session('adminuser_id');
        $page=I('page',1,'intval');
        $number=I('number',10,'intval');
        $state=I('state',2);
        $keyword=I('keyword');
        $theme_id=I('theme_id');
        if(!$user_id) $arr='未登录！';
        else if(!in_array($state,[1,2])) $arr=['state'=>'10003','detail'=>'state参数错误！'];
        else if($theme_id&&!D('portal')->check_id(M('theme'),$theme_id,1)) $arr=['state'=>'10003','detail'=>'栏目id参数错误！'];
        else{
            if(!D('backstage')->adminstrator_judge($user_id)){
                $where['main_user_id|user_id']=$user_id;
                $alltheme_id=M('get_theme_list')->where($where)->getField('id',true);
                if($alltheme_id) $condition['theme_id']=array('in',$alltheme_id);
            }
            $condition['state']=$state;
            if($theme_id) $condition['theme_id']=$theme_id;
            if($keyword) $condition['title|author']=array('like','%'.$keyword.'%');

            if($state==2) $data=M('article_order')->field('id,author,in_time,theme_id,type,title,function,key,cuser_id,time')->page($page,$number)->where($condition)->select();
            else $data=M('article_order')->field('id,author,in_time,theme_id,type,title,function,key,cuser_id,time')->page($page,$number)->where($condition)->select();
            $length=count($data);
            for($i=0;$i<$length;$i++){
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
            $arr['order']=$data;
            $arr['sum']=count(M('article')->where($condition)->select());
        }
        $this->ajaxreturn($arr);
    }


    public function ajaxcomment(){
        $user_id=session('adminuser_id');
        $page=I('page',1,'intval');
        $number=I('number',10,'intval');
        $article_id=I('id');
        $keyword=I('keyword');
        $type=I('type');
        $key=I('key');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if($article_id&&!D('portal')->check_id(M('article'),$article_id,1))  $arr=['state'=>'10003','detail'=>'文章id参数错误！'];
        else if(!D('portal')->check_comment_type($type,$key)) $arr=['state'=>'10003','detail'=>'类型参数错误！'];
        else{
            if($article_id) $condition['article_id']=$article_id;
            if($keyword) $condition['nick|text|type|title']=array('like','%'.$keyword.'%');
            if($type) $condition['type']=['in',$type];
             $data=M('user_comment_list')->field('id,nick,text,avatar,article_id,in_time,count,title,user_id,type')->where($condition)->page($page,$number)->select();
            for($i=0;$i<count($data);$i++){
                $data[$i]['text']=html_entity_decode($data[$i]['text']);
                $data[$i]['in_time']=date('m月d日 H:i',$data[$i]['in_time']);
                $data[$i]['section']=D('section')->get_user_section($data[$i]['user_id']);
                $data[$i]['dislike_count']=count(M('comment_dislike')->where('comment_id=%d',$data[$i]['id'])->select());
                $data[$i]['theme']=M('get_article')->where('id=%d',$data[$i]['article_id'])->getField('theme');
                if(M('comment_admin_reply')->where('comment_id=%d',$data[$i]['id'])->find()) $data[$i]['isreply']=true;
                else $data[$i]['isreply']=false;
            }
            $arr=['state'=>'0','order'=>$data,'sum'=>count(M('user_comment_list')->where($condition)->select())];
        }
        $this->ajaxreturn($arr);
    }

    public function CommentDisLike(){
        $comment_id=I('id');
        $user_id=session('user_id');
        $key=I('key');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if(!D('portal')->check_id(M('comment'),$comment_id,1))  $arr=['state'=>'10003','detail'=>'文章id参数错误！'];
        else if(!in_array($key, [-1,'0',1])) $arr=['state'=>'10003','detail'=>'key参数错误！'];
        else{
            $condition=['comment_id'=>$comment_id,'user_id'=>$user_id];
            $data=['user_id'=>$user_id,'comment_id'=>$comment_id,'in_time'=>time()];
            if($key==-1) $result=M('comment_dislike')->add($data)&&(M('comment_like')->where($condition)->delete()!==false);
            else if($key=='0') $result=(M('comment_dislike')->where($condition)->delete()!==false)&&(M('comment_like')->where($condition)->delete()!==false);
            else if($key==1) $result=M('comment_like')->add($data)&&(M('comment_dislike')->where($condition)->delete()!==false);
            if($result) $arr=['state'=>'0','detail'=>'操作成功！'];
            else $arr=['state'=>'10001','detail'=>'操作失败！'];
        }
        $this->ajaxreturn($arr);
    }

    public function ArticleLike(){
        $article_id=I('id');
        $user_id=session('user_id');
        $key=I('key');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if(!D('portal')->check_id(M('article'),$article_id,1))  $arr=['state'=>'10003','detail'=>'文章id参数错误！'];
        else if(!in_array($key, [-1,'0',1])) $arr=['state'=>'10003','detail'=>'key参数错误！'];
        else{
            $condition=['article_id'=>$article_id,'user_id'=>$user_id];
            $data=['user_id'=>$user_id,'article_id'=>$article_id,'in_time'=>time()];
            if($key==-1) $result=M('article_dislike')->add($data)&&(M('article_like')->where($condition)->delete()!==false);
            else if($key=='0') $result=(M('article_dislike')->where($condition)->delete()!==false)&&(M('article_like')->where($condition)->delete()!==false);
            else if($key==1) $result=M('article_like')->add($data)&&(M('article_dislike')->where($condition)->delete()!==false);
            if($result) $arr=['state'=>'0','detail'=>'操作成功！'];
            else $arr=['state'=>'10001','detail'=>'操作失败！'];
        }
        $this->ajaxreturn($arr);
    }

    public function CommentAdminReply(){
        $comment_id=I('id');
        $user_id=session('adminuser_id');
        $text=I('text');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if(!D('portal')->check_id(M('comment'),$comment_id,1))  $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            foreach ($comment_id as $key => $value) {
                $condition=['comment_id'=>$value];
                if(M('comment_admin_reply')->where($condition)->find())  {
                    $arr=['state'=>'20007','detail'=>'请勿重复回复！'];
                    $this->ajaxreturn($arr);
                }
                else $data[]=['comment_id'=>$value,'user_id'=>$user_id,'time'=>time(),'text'=>$text];
            }
            $result=M('comment_admin_reply')->addAll($data);
            if($result){
                $arr=['state'=>'0','detail'=>'回复成功！'];
                $admin_nick=M('admin')->where('id=%d',$user_id)->getField('nick');
                $post_data=array('comment_id'=>implode(',',$comment_id),'data'=>'inner','text'=>$text,'admin_nick'=>$admin_nick);
                $ret=R('Home/Admin/httpPost',[$post_data,'http://'.$_SERVER['HTTP_HOST'].'/Home/News/push_note_to_users']);
            }
            else $arr=['state'=>'10001','detail'=>'回复失败！'];
        }
        $this->ajaxreturn($arr);
    }

    public function ArticleReadInfo(){
        $article_id=I('id');
        $page=I('page');
        $number=I('number');
        if(!D('portal')->check_id(M('article'),$article_id,1))  $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            $info=M('article')->where('id=%d',$article_id)->find();
            if($info['isshow']=='0'){
                $list=M('article_note')->where('article_id=%d',$article_id)->getField('user_id',true);
                $condition=['user_id'=>['in',$list],'article_id'=>$article_id];
                $read=M('user_article')->where($condition)->getField('user_id',true);
                $conditionu=['user_id'=>['not in',$read],'article_id'=>$article_id];
                $unread=M('article_note')->where($conditionu)->getField('user_id',true);
            }
            else{
                $condition=['article_id'=>$article_id];
                $read=M('user_article')->where($condition)->getField('user_id',true);
            }
            if($read) $order['read']=D('theme')->user_info($read,$page,$number);
            else $order['read']=[];
            if($unread) $order['unread']=D('theme')->user_info($unread,$page,$number);
            else $order['unread']=[];
            $arr=['state'=>'0','order'=>$order];
        }
        $this->ajaxreturn($arr);
    }

    public function ArticleReceiptCount(){
        $article_id=I('id');
        if(!D('portal')->check_id(M('article'),$article_id,1))  $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            $info=M('article')->where('id=%d',$article_id)->find();
            $receipt=M('article_receipt')->where('article_id=%d',$article_id)->find();
            if($receipt){
                if($info['isshow']=='0'){
                    $list=M('article_note')->where('article_id=%d',$article_id)->getField('user_id',true);
                    $condition=['user_id'=>['in',$list],'article_id'=>$article_id];
                    $receipt_list=M('article_receipt_answer')->where($condition)->getField('user_id',true);
                    $order['count']=count($receipt_list);
                    $order['uncount']=count($list)-$order['count'];
                }
                else{
                    $condition=['article_id'=>$article_id];
                    $receipt_list=M('article_receipt_answer')->where($condition)->getField('user_id',true);
                    $order['count']=count($receipt_list);
                }
                $arr=['state'=>'0','order'=>$order];
            }
            else $arr=['state'=>'10003','detail'=>'参数错误！'];
        }
        $this->ajaxreturn($arr);
    }

    public function ArticleReceiptInfo(){
        $article_id=I('id');
        $page=I('page');
        $number=I('number');
        if(!D('portal')->check_id(M('article'),$article_id,1))  $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            $info=M('article')->where('id=%d',$article_id)->find();
            $receipt=M('article_receipt')->where('article_id=%d',$article_id)->find();
            if($receipt){
                if($info['isshow']=='0'){
                    $list=M('article_note')->where('article_id=%d',$article_id)->getField('user_id',true);
                    $condition=['user_id'=>['in',$list],'article_id'=>$article_id];
                    $receipt_list=M('article_receipt_answer')->where($condition)->getField('user_id',true);
                    $read=M('article_receipt_answer')->where($condition)->getField('user_id',true);
                    $conditionu=['article_id'=>$article_id];
                    if($read) $conditionu['user_id']=['not in',$read];
                    $unread=M('article_note')->where($conditionu)->getField('user_id',true);
                }
                else{
                    $condition=['article_id'=>$article_id];
                    $receipt_list=M('article_receipt_answer')->where($condition)->getField('user_id',true);
                    $read=M('article_receipt_answer')->where($condition)->getField('user_id',true);
                }
                if($read) $order['read']=D('theme')->user_info($read,$page,$number);
                else $order['read']=[];
                if($unread) $order['unread']=D('theme')->user_info($unread,$page,$number);
                else $order['unread']=[];
                $arr=['state'=>'0','order'=>$order];
            }
            else $arr=['state'=>'10003','detail'=>'参数错误！'];
        }
        $this->ajaxreturn($arr);
    }

    public function ArticleVoteCount(){
        $article_id=I('id');
        if(!D('portal')->check_id(M('article'),$article_id,1))  $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            $condition=['article_id'=>$article_id];
            $info=M('article')->where('id=%d',$article_id)->find();
            $vote=M('vote')->where($condition)->getField('id',true);
            if($vote){
                $condition=['vote_id'=>['in',$vote]];
                $question=M('vote_question')->where($condition)->getFIeld('id',true);
                if($info['isshow']=='0'){
                    $list=M('article_note')->where('article_id=%d',$article_id)->getField('user_id',true);
                    $condition=['user_id'=>['in',$list],'question_id'=>['in',$question]];
                    $vote_list=M('vote_select')->where($condition)->distinct(true)->getField('user_id',true);//排除多选重复机票情况
                    $order['count']=count($vote_list);
                    $order['uncount']=count($list)-$order['count'];
                }
                else{
                    $condition=['question_id'=>['in',$question]];
                    $vote_list=M('vote_select')->where($condition)->distinct(true)->getField('user_id',true);//排除多选重复机票情况
                    $order['count']=count($vote_list);
                }
                $arr=['state'=>'0','order'=>$order];
            }
            else $arr=['state'=>'10003','detail'=>'参数错误！'];
        }
        $this->ajaxreturn($arr);
    }

    public function ArticleVoteInfo(){
        $article_id=I('id');
        if(!D('portal')->check_id(M('article'),$article_id,1))  $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            $condition=['article_id'=>$article_id];
            $info=M('article')->where('id=%d',$article_id)->find();
            $vote=M('vote')->where($condition)->getField('id',true);
            if($vote){
                $condition=['vote_id'=>['in',$vote]];
                $question=M('vote_question')->where($condition)->getFIeld('id',true);
                if($info['isshow']=='0'){
                    $list=M('article_note')->where('article_id=%d',$article_id)->getField('user_id',true);
                    $condition=['user_id'=>['in',$list],'question_id'=>['in',$question]];
                    $read=M('vote_select')->where($condition)->getField('user_id',true);
                    $conditionu=['article_id'=>$article_id];
                    if($read) $conditionu['user_id']=['not in',$read];
                    $unread=M('article_note')->where($conditionu)->getField('user_id',true);
                }
                else{
                    $condition=['question_id'=>['in',$question]];
                    $vote_list=M('vote_select')->where($condition)->getField('user_id',true);
                    $read=M('vote_select')->where($condition)->getField('user_id',true);
                }
                if($read) $order['read']=D('theme')->user_info($read,$page,$number);
                else $order['read']=[];
                if($unread) $order['unread']=D('theme')->user_info($unread,$page,$number);
                else $order['unread']=[];
                $arr=['state'=>'0','order'=>$order];
            }
            else $arr=['state'=>'10003','detail'=>'参数错误！'];
        }
        $this->ajaxreturn($arr);
    }

     public function a(){
        $number=I('number',10,'intval');
        $page=I('page',1,'intval');
        $theme_id=I('theme_id');
        $portal_id=I('portal_id');
        $key=I('key',2,'intval');
        $user_id=session('user_id');
        $isthemeclick=I('isthemeclick',1,'intval');
        $tag_id=I('tag_id');
        $keyword=I('keyword');
        $theme_id=[146];
        // if(!$user_id) $arr=['state'=>10000,'detail'=>'未登录！'];
        if($theme_id&&!D('portal')->check_id(M('theme'),$theme_id,$condition)) $arr=['state'=>10003,'detail'=>'栏目id参数错误！'];
        else if($portal_id&&!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>10003,'detail'=>'门户id参数错误！'];
        else{
            $User=M('article_order');
            $condition=['`key`'=>1,'state'=>2];//除轮播图和草稿外文章
            if($user_id){
                $list=M('article')->where($condition)->getField('id',true);
                $note_list=M('article_note')->where('user_id',$user_id)->getField('article_id',true);
                if($note_list){
                    $list=array_merge($list,$note_list);
                    $condition['id']=['in',$list];
                }
            }
            else $condition['isshow']=1;

            if($user_id&&$isthemeclick==2) D('portal')->portal_user_theme($user_id,$theme_id);//记录用户点击栏目时间
            if($tag_id) {
                $tag_article_list=M('article_tag')->where('tag_id',$tag_id)->getField('article_id',true);
                if($list) $tag_article_list=array_intersect($list, $tag_article_list);
                if($tag_article_list) $condition['id']=['in',$tag_article_list];
                else return json(['state'=>0,'order'=>[]]);
                if($user_id) D('portal')->user_tag_click($user_id,$tag_id);
            }

            if($keyword) {
                $User=M('get_article');
                $condition['title|author|theme']=['like','%'.$keyword.'%'];
            }
            
            if($portal_id) {
                $theme=D('portal')->portal_theme($portal_id,$user_id);
                $theme_id=array_column($theme['theme'],'id');
            }
            if($theme_id){
                $condition['theme_id']=['in',$theme_id];
                $order=D('theme')->article_list($User,$condition,$page,$number,$key,$user_id);
                if(!$order) $order=[];
            }
            else $order=[];
            
            $arr=['state'=>0,'order'=>$order];
        }
        $this->ajaxreturn($arr);
    }

    public function b(){
         $number=I('number',10,'intval');
        $page=I('page',1,'intval');
        $theme_id=I('theme_id');
        $portal_id=I('portal_id');
        $key=I('key',2,'intval');
        $user_id=session('user_id');
        $isthemeclick=I('isthemeclick',1,'intval');
        $tag_id=I('tag_id');
        $keyword=I('keyword');
        // $theme_id=[146];
        // if(!$user_id) $arr=['state'=>10000,'detail'=>'未登录！'];
        if($theme_id&&!D('portal')->check_id(M('theme'),$theme_id,$condition)) $arr=['state'=>10003,'detail'=>'栏目id参数错误！'];
        else if($portal_id&&!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>10003,'detail'=>'门户id参数错误！'];
        else{
            $User=M('article_order');
            $condition=['key'=>1,'state'=>2,'isshow'=>1];//除轮播图和草稿外文章
            if($user_id){
                $list=M('article')->where($condition)->getField('id',true);
                $note_list=M('article_note')->where('user_id=%d',$user_id)->getField('article_id',true);
                if($note_list){
                    $list=array_merge($list,$note_list);
                    $condition=['id'=>['in',$list]];
                }
            }

            if($user_id&&$isthemeclick==2) D('portal')->portal_user_theme($user_id,$theme_id);//记录用户点击栏目时间
            if($tag_id) {
                $condition['tag_id']=$tag_id;
                if($user_id) D('portal')->user_tag_click($user_id,$tag_id);
            }

            
            
            if($portal_id) {
                $theme=D('portal')->portal_theme($portal_id,$user_id);
                $theme_id=array_column($theme['theme'],'id');
            }
            if($theme_id){
                $condition['theme_id']=['in',$theme_id];
                
                if(!$order) $order=[];
            }
            else $order=[];
            if($keyword) {
                $User=M('get_article');
                $condition['title|author|theme']=['like','%'.$keyword.'%'];
            }
            $order=D('theme')->article_list($User,$condition,$page,$number,$key,$user_id);
            for($i=0;$i<count($data);$i++){
            $data[$i]['photo_photo']=M('photo')->where('article_id=%d',$data[$i]['id'])->getField('photo');
            $data[$i]['photo_compress_photo']=M('photo')->where('article_id=%d',$data[$i]['id'])->getField('compress_photo');
            $data[$i]['theme']=M('theme')->where('id=%d',$data[$i]['theme_id'])->getField('theme');
            if($key==1){
                $g=$data[$i]['photo'];
                $g2=$data[$i]['photo2'];
                $g3=$data[$i]['photo3'];
                $t=$data[$i]['photo_photo'];
            }
            else{
                $g=$data[$i]['compress_photo'];
                if(!$data[$i]['compress_photo']&&$data[$i]['photo']) $g=$data[$i]['photo'];//处理错误文件导致无法压缩图片问题
                $g2=$data[$i]['compress_photo2'];
                $g3=$data[$i]['compress_photo3'];
                $t=$data[$i]['photo_compress_photo'];
            }
            $order[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'author'=>$data[$i]['author'],'photo'=>$g,'photo2'=>$g2,'photo3'=>$g3,'photo_photo'=>$t,'photo_text'=>html_entity_decode($data[$i]['photo_text']),'theme'=>$data[$i]['theme'],'function'=>$data[$i]['function'],'link'=>$data[$i]['link'],'in_time'=>date('m-d H:i',$data[$i]['in_time']));
            $order[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
                $order[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
                $order[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
            $order[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
            $data[$i]['text']=html_entity_decode($data[$i]['text']);
            preg_match('/<(iframe)/', $data[$i]['text'], $match);
            if($match[0]) $order[$i]['isvideo']=1;
            preg_match("/附件 <a href=/",$data[$i]['text'],$vmatch);
            if($vmatch[0]) $order[$i]['isattachment']=1;
            unset($data[$i]['text']);
            if($user_id){
                if(M('user_article')->where('article_id=%d and user_id=%d',$order[$i]['id'],$user_id)->find()) $order[$i]['isread']=1;
            }

            if(D('portal')->isreceipt($order[$i]['id'])) $order[$i]['isreceipt']=1;

            if(D('portal')->isvote($order[$i]['id'])) $order[$i]['isvote']=1;

            }
            dump($order);
            $arr=['state'=>0,'order'=>$order];
        }
        $this->ajaxreturn($arr);
    }
}