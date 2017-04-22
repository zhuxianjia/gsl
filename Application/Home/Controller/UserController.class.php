<?php
namespace Home\Controller;
use Think\Controller;
class UserController extends Controller {

	public function get_all_bloc(){
		$token=D('token')->get_admin_token(C('client_id'),C('clientsecret'));
	    $access_token=$token['access_token'];
	    if(!$access_token) $arr=array('state'=>'10056','detail'=>'秘钥获取失败！');
		else{
			$url=C('url').'/openapi/bloc/get_all?access_token='.$access_token;
			$code=R('Home/Index/httpGet',[$url]);
			$object=json_decode($code);
		    $ret=$object->ret;
		    $data=$object->data;
		    $blocs=$data->blocs;
		    foreach ($blocs as $key => $value) {
		    	$save[]=['id'=>$value->id,'name'=>$value->name];
		    }
		    M('bloc')->where(1)->delete();
		    $result=M('bloc')->addALL($save);
		    if($result) $arr=['state'=>'0','detail'=>'操作成功！'];
            else $arr=['state'=>'10001','detail'=>'操作失败！'];
		}
		$this->ajaxreturn($arr);
	}

	public function get_all_user(){
		$token=D('token')->get_admin_token(C('client_id'),C('clientsecret'));
	    $access_token=$token['access_token'];
	    if(!$access_token) $arr=array('state'=>'10056','detail'=>'秘钥获取失败！');
		else{
			$url=C('url').'/openapi/bloc/get_users';
			$bloc_list=M('bloc')->getField('id',true);
			foreach ($bloc_list as $key => $value) {
				$object=[];
				$post_data=['access_token'=>$access_token,'bloc_id'=>$value];
				$object=R('Home/News/httpPost',[$post_data,$url]);
				$data=json_decode($object)->data;
				$open_ids=$data->users;
				$allguid=M('user')->getField('guid',true);
				foreach ($open_ids as $u => $open_id) {
					$info=$this->get_info($access_token,$open_id);
					$user_data=['nick'=>$info->name,'guid'=>$info->open_id,'mobile'=>$info->mobile];
			    	if($info->photo) $user_data['avatar']=$info->photo;
			    	else $user_data['avatar']='/defaultlogo.png';
			    	$sex=$info->gender;
			    	if($sex==1) $user_data['gender']='女';
			    	else $user_data['gender']='男';
		    	
			    	if(!in_array($user_data['guid'], $allguid)) $user_id=M('user')->add($user_data);
			    	else{
			    		M('user')->where('guid="%s"',$user_data['guid'])->save($user_data);
			    		$user_id=M('user')->where('guid="%s"',$user_data['guid'])->getField('id');
			    	}

			    	$user_blocs=$this->get_blocs($access_token,$open_id);

			    	$blocs=$user_blocs->blocs;
					for($i=0;$i<count($blocs);$i++){
			    		$blocs_data=['bloc_name'=>$blocs[$i]->bloc_name,'user_id'=>$user_id,'bloc_id'=>$blocs[$i]->bloc_id];
			    		M('user_bloc')->where('user_id=%d',$user_id)->delete();
			    		$result=M('user_bloc')->add($blocs_data);
			    		if($result==false) break;
		    		}

			    	$orgs=$user_blocs->orgs;
		    		$section=$orgs[0]->name;
		    		if($section){
				    	for($i=0;$i<count($orgs);$i++){
				    		$section=$orgs[$i]->name;
				    		$bloc_id=$orgs[$i]->bloc_id;
				    		$section_data=['name'=>$section,'user_id'=>$user_id,'id'=>$orgs[$i]->id,'bloc_id'=>$value,'type'=>$orgs[$i]->type];
				    		M('user_section')->where('user_id=%d',$user_id)->delete();
				    		$result=M('user_section')->add($section_data);
				    		if($result==false) break;
				    		if(!M('section')->where('section="%s"',$section)->find()){
					   			$save=['section'=>$section];
					   			$result=$result&&M('section')->add($save);
					   		}

					   		$groups=$orgs[$i]->groups;
					   		for($j=0;$j<count($groups);$j++){
					   			$groups_data=['user_id'=>$user_id,'group_id'=>$groups[$j]->group_id,'section_id'=>$orgs[$i]->id,'section_name'=>$section,"bloc_id"=>$bloc_id,'bloc_name'=>M('bloc')->where('id=%d',$bloc_id)->getField('name'),'user_name'=>M('user')->where('id=%d',$user_id)->getField('nick')];
					   			if($groups[$j]->duty) $groups_data['duty']=$groups[$j]->duty;
					   			if($groups[$j]->group_name) $groups_data['group_name']=$groups[$j]->group_name;
					   			if($groups[$j]->path) $groups_data['path']=$groups[$j]->path;
					   			M('user_group')->where('user_id=%d',$user_id)->delete();
					    		$result=M('user_group')->add($groups_data);
					    		if($result==false) break;
					   		}
						}
				   	}
				}
			}
		    if($result) $arr=['state'=>'0','detail'=>'操作成功！'];
            else $arr=['state'=>'10001','detail'=>'操作失败！'];
		}
		$this->ajaxreturn($arr);
	}

	public function get_info($access_token,$open_id){
		$url=C('url')."/openapi/user/get_info?access_token=".$access_token."&open_id=".$open_id;
		$code=R('Home/Index/httpGet',[$url]);
	    $object=json_decode($code);
	    $ret=$object->ret;
	    $data=$object->data;
	    if($ret==0) return $data;
	}

	public function get_blocs($access_token,$open_id){
		$url=C('url')."/openapi/user/get_blocs?access_token=".$access_token."&open_id=".$open_id;
		$code=R('Home/Index/httpGet',[$url]);
	    $object=json_decode($code);
	    $ret=$object->ret;
	    $data=$object->data;
	    if($ret==0) return $data;
	}

	public function user_tree(){
		$article_id=I('id');
		$theme_id=I('theme_id');
		$portal_id=I('portal_id');
		$group_id=I('group_id');
		if($article_id&&!D('portal')->check_id(M('article'),$article_id,1)) $arr=['state'=>'10003','order'=>'参数错误！'];
        else if($theme_id&&!D('portal')->check_id(M('theme'),$theme_id,1)) $arr=['state'=>'10003','detail'=>'栏目id参数错误！'];
        else if($portal_id&&!D('portal')->check_id(M('portal'),$portal_id,1)) $arr=['state'=>'10003','detail'=>'栏目id参数错误！'];
        else if($group_id&&!D('portal')->check_id(M('admin_group'),$group_id,1)) $arr=['state'=>'10003','detail'=>'栏目id参数错误！'];
		else{
			$temp=M('user_group')->order('bloc_id')->field('user_id as id,user_name as name,bloc_id,bloc_name,section_id,section_name,group_id,group_name,path,duty')->select();
			$bloc=splite_array($temp,'bloc_id','children',['bloc_name']);
			if($article_id) $order_id=M('article_note')->where('article_id=%d',$article_id)->getField('user_id',true);
			if(!$theme_id&&$portal_id) $order_id=M('portal_push')->where('portal_id=%d',$portal_id)->getField('user_id',true);
			if($theme_id&&$portal_id) $order_id=M('portal_theme_push')->where('theme_id=%d and portal_id=%d',$theme_id,$portal_id)->getField('user_id',true);
			if($group_id) $order_id=M('admin_group_user')->where('group_id=%d',$group_id)->getField('user_id',true);
			foreach ($bloc as $key => $value){
				$order[]=['id'=>$bloc[$key]['bloc_id'],'name'=>$bloc[$key]['bloc_name']];
				$value['children']=array_sort($value['children'],'section_id');
				$value['children']=splite_array($value['children'],'section_id','children',['section_name']);
				
				foreach ($value['children'] as $gkey => $gvalue){
					$order[$key]['children'][]=['id'=>$value['children'][$gkey]['section_id'],'name'=>$value['children'][$gkey]['section_name']];
					$gvalue['children']=array_sort($gvalue['children'],'group_id');
					$gvalue['children']=splite_array($gvalue['children'],'group_id','children',['group_name']);
					$order[$key]['children'][$gkey]['children']=$gvalue['children'];
					for($i=0;$i<count($order[$key]['children'][$gkey]['children']);$i++){
						$order[$key]['children'][$gkey]['children'][$i]['id']=$order[$key]['children'][$gkey]['children'][$i]['group_id'];
						$order[$key]['children'][$gkey]['children'][$i]['name']=$order[$key]['children'][$gkey]['children'][$i]['group_name'];
						unset($order[$key]['children'][$gkey]['children'][$i]['group_id']);
						unset($order[$key]['children'][$gkey]['children'][$i]['group_name']);
						for($j=0;$j<count($order[$key]['children'][$gkey]['children'][$i]['children']);$j++){
							if(in_array($order[$key]['children'][$gkey]['children'][$i]['children'][$j]['id'], $order_id)) $order[$key]['children'][$gkey]['children'][$i]['children'][$j]['checked']=true;
							else $order[$key]['children'][$gkey]['children'][$i]['children'][$j]['checked']=false;
						}
					}
				}
			}
			$arr=['state'=>'0','order'=>$order];
		}
		$this->ajaxreturn($arr);
	}
	
	public function push_friend_openid(){
		$guid=session('guid');
		$token=D('token')->get_admin_token(C('client_id'),C('clientsecret'));
	    $access_token=$token['access_token'];
	    if(!$access_token) $arr=array('state'=>'10056','detail'=>'秘钥获取失败！');
		else if(!$guid) $arr=['state'=>'10000','detail'=>'不是绑定用户登录，无法获取好友信息！'];
		else{
			$url=C('url').'/openapi/user/foreach_friends';
			$post_data=['access_token'=>$access_token,'open_id'=>$guid,'url'=>'http://'.$_SERVER['HTTP_HOST'].'/home/user/get_friend?guid='.$guid];
			$object=R('Home/News/httpPost',[$post_data,$url]);
			$ret=json_decode($object)->ret;
			if($ret=='0')  $arr=['state'=>'0','detail'=>'操作成功！'];
			else $arr=['state'=>'10001','detail'=>'操作失败！'];
		} 
		$this->ajaxreturn($arr);
	}

	public function get_friend(){
		$object=I('open_id');
		$guid=I('guid');
		$file=fopen('test.txt', 'w');
		fwrite($file, $guid);
		fclose($file);
		if(I('open_id')){
			$save['friends']=json_encode($object);
			M('admin_bind')->where('guid="%s"',$guid)->save($save);
		}
	}

	public function send_sms($access_token,$mobile,$type=1,$text){
		$url=C('url')."/openapi/sms/send";
	    $post_data=['access_token'=>$access_token,'bloc_id'=>$value,'type'=>$type];
		$object=R('Home/News/httpPost',[$post_data,$url]);
		$data=json_decode($object)->data;
	    if($ret==0) return $data;
	}

    public function check_login(){
        $user_id=session('user_id');
        if($user_id)  $arr=['state'=>'0','detail'=>'已登录！'];    
        else   $arr=['state'=>'10000','detail'=>'未登录！'];
         $this->ajaxreturn($arr);
    }
}