<?php
namespace Home\Model;
class BackstageModel{//后台类

	/*管理员权限判定
	  返回   int 1为一般管理员  2为系统管理员
	  */

	Public function adminstrator_judge($user_id){
		$state=M('admin')->where('id=%d',$user_id)->getField('type');
		if($state==2) return 1;
	}
	





	/*
		函数 logout 登出
		参数 无
		返回 0为成功
	*/
	public function logout(){
		session("adminuser_id",null);
		return 0;
	}






	



	/*
		函数 login 登录
		参数 string email 邮箱
			 string password 密码
		返回 int 状态
	*/
	public function login($nick,$password){
        $user_id=M('admin')->where("account='%s' and password='%s'",$nick,$password)->getField('id');
        if($user_id){
        	ini_set('session.gc_maxlifetime', 3600*24*365);
        	session("adminuser_id",$user_id);
        	$type=M('admin')->where('id=%d',$user_id)->getField('type');
        	if($type==2){
        		return 10023;//登录成功，系统管理员
        	}
        	else{
        		// $where['main_user_id|user_id']=$user_id;
          //       $alltheme_id=M('get_theme_list')->where($where)->getField('id',true);
          //       if($alltheme_id){
                    return 10024;//登录成功，一般管理员
                // }
                // else{
                // 	return 10044;
                // }
        	}
			
		}
		else{
			return 10020;//邮箱或者密码错误登录失败
		}
	}

	 /*
		验证邮箱格式
	*/ 


     private function check_islegalemail($email){
        preg_match("/^[A-Z0-9a-z._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,4}$/",$email,$re);
        return $re[0]==$email&&$email!=""&&$email!=null;
    }


	/*
		函数 change_password 修改密码
		参数 int user_id 用户名ID
			 string password 密码
			 string repassword 新密码
		返回 int 状态
	*/
	public function change_password($user_id,$password,$repassword){
		if($user_id){
			if($password==(M('admin')->where('id=%d',$user_id)->getField('password'))){
				$data['password']=$repassword;
				if(!(M('admin')->where('id=%d',$user_id)->save($data)===false)){
					return 0;  //修改成功
				}
				else{
					return 10001;//系统异常
				}
			}
			else{
				return 10018;//密码不正确
			}
		}
		else{
			return 10000;//未登录
		}
	}

	public function check_article($id){
		$allid=M('article')->getField('id',true);
		if(in_array($id,$allid)){
			return 1;
		}
	}

	public function articlesearch($user_id,$key,$state){
		if(!$user_id) return 10000;//未登录
		// else if(!$key) return 10002;//输入不能为空
		else if(!in_array($state,array(1,2))) return 10003;
		else{
			if(D('backstage')->adminstrator_judge($user_id)) {
				if($key) $condition['title|author']=array('like','%'.$key.'%');
				else $condition=1;
			}
            else{
                $where['main_user_id|user_id']=$user_id;
                $alltheme_id=M('get_theme_list')->where($where)->getField('id',true);
                if($alltheme_id){
                    $condition['theme_id']=array('in',$alltheme_id);
                    if($key) $condition['title|author']=array('like','%'.$key.'%');
                }
            }
        	if($condition){
        		$condition['state']=$state;
				$data=M('article')->where($condition)->select();
				if($data) return 0;//搜索成功
    			else return 10021;//搜索内容不存在
    		}
    		else return 10038;
		}
	}

	public function article_theme($user_id,$id,$state){
		if($user_id){
			if($id){
				if($id==0) return 0;//搜索成功
				else{
					$allid=M('theme')->getField('id',true);
					if(in_array($id,$allid)&&in_array($state,array(1,2))){
						$condition['theme_id']=$id;
						$condition['state']=$state;
						$data=M('article')->where($condition)->select();
			            if($data) return 0;//搜索成功
		    			else return 10021;//搜索内容不存在
		    		}
					else return 10003;//参数错误
				}
			}
			else return 10002;//输入为空
		}
		else return 10000;//未登录
	}


	public function ispush($id){
		$condition['article_id']=$id;
		if(M('article_push')->where($condition)->find()) return true;
	}


    public function get_user_blank($user_id){ 
     	$user_list=M('get_user_blank')->where('id=%d',$user_id)->order('in_time')->getField('theme',true);
		if(!$user_list) $user_list=array();
		$default_list=M('theme')->where('push="1"')->getField('theme',true);
		if(!$default_list) $default_list=array();
		$list=array_merge($default_list,$user_list);
		$section=M('user')->where('id=%d',$user_id)->getField('section');
		$push=M('theme')->where('isshow=2')->select();
		for($i=0;$i<count($push);$i++){
			if(similar_text($section,$push[$i]['push'])>5) array_push($list,$push[$i]['theme']);
		}
		return array_unique($list);
	}

	public function get_user_blank_id($user_id){
		$user_list=M('user_theme')->where('user_id=%d',$user_id)->getField('theme_id',true);
		if(!$user_list) $user_list=array();
		$default_list=M('theme')->where('push="1"')->getField('id',true);
		if(!$default_list) $default_list=array();
		$list=array_merge($default_list,$user_list);
		$section=M('user')->where('id=%d',$user_id)->getField('section');
		$push=M('theme')->where('isshow=2')->select();
		for($i=0;$i<count($push);$i++){
			if(similar_text($section,$push[$i]['push'])>5) array_push($list,$push[$i]['id']);
		}
		// return array_unique(array_values(array_filter($list)));
		return array_unique($list);
	}

	public function get_theme_order($theme_id){
		$info=M('theme')->where('id=%d',$theme_id)->find();
		$userlist=M('user')->select();
		$list=array();
		if($info['push']==1) $list=array_column($userlist,'id');
		else if($info['push']){
			$length=count($userlist);
			for($i=0;$i<$length;$i++){
				if(similar_text($info['push'],$userlist[$i]['section'])>5) array_push($list,$userlist[$i]['id']);
			}
		}
		else $list=M('user_theme')->where('theme_id=%d',$theme_id)->getFIeld('user_id',true);
		return $list;
	}




	

}
