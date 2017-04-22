<?php
namespace Home\Controller;
use Think\Controller;
class GroupController extends Controller {

	public function AdminGroupModify(){
        $user_id=session('adminuser_id');
        $name=I('name');
        $id=I('id');
        $list=I('user_id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if($id&&!D('portal')->check_id(M('admin_group'),$id,1)) $arr=['state'=>'10003','detail'=>'群组id参数错误！'];
        else if($list&&!D('portal')->check_id(M('user'),$list,1)) $arr=['state'=>'10003','detail'=>'用户id参数错误！'];
        else{
        	M()->startTrans();
        	$data=['user_id'=>$user_id,'name'=>$name];
        	if(!$id){
        		$id=M('admin_group')->add($data);
        		$result=$id;
        	}
        	else $result=(M('admin_group')->where('id=%d',$id)->save($data)!==false);

        	
    		foreach ($list as $key => $value) {
        		$group_user[]=['group_id'=>$id,'user_id'=>$value];
        	}
        	M('admin_group_user')->where('group_id=%d',$id)->delete();
        	if($group_user) $result=$result&&M('admin_group_user')->addALL($group_user);
        	
            if($result){
                M()->commit();
                $arr=['state'=>'0','detail'=>'推送成功！'];
            }
            else{
                M()->rollback();
                $arr=['state'=>'10001','detail'=>'推送失败！'];
            }
        }
        $this->ajaxReturn($arr);
	}

	public function AdminGroupShow(){
		$user_id=session('adminuser_id');
		$article_id=I('id');
		if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if($article_id&&!D('portal')->check_id(M('article'),$article_id,1)) $arr=['state'=>'10003','detail'=>'群组id参数错误！'];
        else{
        	$order_id=M('article_note')->where('article_id=%d',$article_id)->getField('user_id',true);
        	$data=M('admin_group')->where('user_id=%d',$user_id)->field('id,name')->select();
        	foreach ($data as $key => $value) {
        		$list=M('admin_group_user')->where('group_id=%d',$data[$key]['id'])->getField('user_id',true);
        		if(!$list) $data[$key]['children']=[];
        		foreach ($list as $ckey => $cvalue) {
        			$data[$key]['children'][]=M('user')->where('id=%d',$cvalue)->field('id,nick as name,avatar')->find();
        			if(in_array($cvalue, $order_id)) $data[$key]['children'][$ckey]['checked']=true;
        			else $data[$key]['children'][$ckey]['checked']=false;
        		}
        	}
        	if(!$data) $data=[];
        	$arr=['state'=>'0','order'=>$data];
        }
        $this->ajaxReturn($arr);
	}


}