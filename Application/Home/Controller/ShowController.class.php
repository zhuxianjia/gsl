<?php
namespace Home\Controller;
use Think\Controller;
class ShowController extends Controller {

	public function PortalLists(){
		$user_id=session('user_id');
        // $user_id=10153;
		if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
		else{
			$order=D('portal')->get_user_portal($user_id);
			if(!$order) $order=[];
			$arr=['state'=>'0','order'=>$order];
		}
		$this->ajaxreturn($arr);
	}

	public function PublicPortal(){
        $condition['ispublic']=1;
        session('user_id',null);
		$order=M('portal')->where($condition)->field('id,name')->select();
		if(!$order) $order=[];
		$arr=['state'=>'0','order'=>$order];
		$this->ajaxreturn($arr);
	}

	public function PortalInfo(){
        $portal_id=I('id');
        $user_id=session('user_id');
        if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
        	$data=D('portal')->get_portal_info($portal_id);
            if(!$data) $data=[];
            $arr=['state'=>'0','order'=>$data];
        }
        $this->ajaxreturn($arr);
    }

	public function PortalCarousel(){
        $user_id=session('user_id');
        $portal_id=I('id');
        // if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else {
            $data=D('portal')->portal_carousel($portal_id);
            if(!$data) $data=[];
            $arr=['state'=>'0','order'=>$data];
        }
        $this->ajaxreturn($arr);
    }

    public function PortalTheme(){
        $user_id=session('user_id');
        $portal_id=I('id');
        // if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else {
            $data=D('portal')->portal_theme($portal_id,$user_id);
            if(!$data) $data=[];
            else{
            	$length=count($data['theme']);
            	for($i=0;$i<$length;$i++){
            		$info=M('portal_user_theme')->where('theme_id=%d and user_id=%d',$data['theme'][$i]['id'],$user_id)->find();
              		$time=max(M('article')->where('theme_id=%d',$data['theme'][$i]['id'])->getField('in_time',true));
            		if($info['time']<=$time) $data['theme'][$i]['isnew']=2;//避免两者都不存在的情况
            		else $data['theme'][$i]['isnew']=1;
            	}
            	
            }
            $arr=['state'=>'0','order'=>$data];
        }
        $this->ajaxreturn($arr);
    }

    public function PortalArticle(){
        $user_id=session('user_id');
        $portal_id=I('id');
        // if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        if(!D('portal')->check_id(M('portal'),$portal_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else {
            $data=D('portal')->portal_article_theme($portal_id);
            if(!$data) $data=[];
            $arr=['state'=>'0','order'=>$data];
        }
        $this->ajaxreturn($arr);
    }

    public function ReceiptAnswer(){
        $user_id=session('user_id');
        $article_id=I('id');
        $answer=I('answer');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录!'];
        else if(!D('portal')->check_id(M('article'),$article_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else {
            $info=M('article_receipt')->where('article_id=%d',$article_id)->select();
            if(!$info) $arr=['state'=>'10003','detail'=>'参数错误！'];
            $order=M('article_receipt_answer')->where('user_id=%d and article_id=%d',$user_id,$article_id)->find();
            if($order) $arr=['state'=>'20006','detail'=>'请勿重复填写！'];
            else{
                for($i=0;$i<count($answer);$i++){
                    $result[]=['id'=>$answer[$i]['id'],'name'=>M('article_receipt')->where('id=%d',$answer[$i]['id'])->getField('name'),'answer'=>$answer[$i]['answer']];
                }
                $data=['article_id'=>$article_id,'user_id'=>$user_id,'answer'=>json_encode($result)];
                M('article_receipt_answer')->add($data);
                $arr=['state'=>'0','detail'=>'回复成功！'];
            }
        }
        $this->ajaxreturn($arr);
    }

    public function a(){
        dump(D('portal')->portal_theme(I('portal_id'),I('user_id')));
    }






}