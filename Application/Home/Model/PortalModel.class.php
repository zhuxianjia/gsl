<?php
namespace Home\Model;
class PortalModel{//门户类

	/**
	 * @DateTime 2016-12-29
	 * @param    [string]  User
	 * @param    [int]     id    
	 * @param    [array]   condition
	 * @return   [bool]
	 */	
	public function check_id($User,$id,$condition){
		$allid=$User->where($condition)->getField('id',true);
		if(is_array($id)){
			if(array_intersect($id, $allid)==$id) return true;
		}
		else{
			if(in_array($id, $allid)) return true;
		}
	}

	/**
	 * @DateTime 2016-12-30
	 * @param    [string] User
	 * @param    [array]   condition
	 * @return   [bool]
	 */
	public function db_delete($table,$condition){
		if(is_array($table)){
			foreach ($table as $key => $value) {
				M($value)->where($condition)->delete();
			}
		}
		else M($table)->where($condition)->delete();
	}

	/**
	 * @DateTime 2016-12-30T15:32:01+0800
	 * @param    [array] array
	 * @return   [bool]
	 */
	public function check_order($array){
		$orders=array_column($array, 'orders');
		if(array_unique($orders)==$orders) return true;
	}

	/**
	 * @DateTime 2016-12-30T15:44:47+0800
	 * @param    [array] theme
	 * @return   [bool] 
	 */
	public function check_article_theme($theme,$article){
		$theme=array_column($theme, 'theme_id');
		$article=array_column($article, 'theme_id');
		if(array_intersect($article, $theme)==$article) return ture;
	}

	/**
	 * @DateTime 2016-12-30T16:43:54+0800
	 * @param    [int] user_id
	 * @return   [array]
	 */
	public function get_user_portal($user_id){
		if($user_id){
			$user_list=M('portal_push')->where('user_id=%d',$user_id)->getField('portal_id',true);
			if(!$user_list) $user_list=array();
			$section=M('user_section')->where('user_id=%d',$user_id)->getField('name',true);
			$push=M('portal')->select();
			for($j=0;$j<count($section);$j++){
				for($i=0;$i<count($push);$i++){
					if(strpos($section[$j],$push[$i]['push'])!==false) array_push($user_list,$push[$i]['id']);
				}
			}
		}
		
		$list=M('portal')->where('push="1"')->getField('id',true);
		if(!$list) $list=array('');
		if($user_list) $list=array_merge($list,$user_list);

		$list=array_unique(array_filter($list));

		if($list){
			$condition['id']=['in',$list];
			return M('portal')->where($condition)->field('name,id')->select();
		}
	}

	/**
	 * @DateTime 2017-01-04T10:31:37+0800
	 * @param    [int]                   $user_id [用户id]
	 * @return   [array]                          [管理的门户id一维数组]
	 */
	public function get_admin_portal($user_id){
		if(!D('backstage')->adminstrator_judge($user_id)){
			$main=M('portal')->where('user_id=%d',$user_id)->getField('id',true);
			if(!$main) $main=array();
			$assist=M('portal_distribute')->where('user_id=%d',$user_id)->getField('portal_id',true);
			if(!$assist) $assist=array();
			return array_merge($main,$assist);
		}
		else return M('portal')->getField('id',true);
	}

	/**
	 * @DateTime 2017-01-04T11:47:15+0800
	 * @param    [int]                   $id [门户id]
	 * @return   [array]                              [门户信息]
	 */
	public function get_portal_info($id){
    	$data=M('portal')->where('id=%d',$id)->find();
		if(!$data['push']||$data['push']=='0') $data['push']='';
		$data['theme_layout']=json_decode($data['theme_layout']);
        $data['carousel_layout']=json_decode($data['carousel_layout']);
        $data['article_layout']=json_decode($data['article_layout']);
        $data['admin_nick']=D('section')->get_admin_name($data['user_id']);
        $condition['portal_id']=$id;
        $assist_user=M('portal_distribute')->where($condition)->field('user_id as id')->select();
        $length=count($assist_user);
        for($i=0;$i<$length;$i++){
        	$assist_user[$i]['name']=D('section')->get_admin_name($assist_user[$i]['id']);
        }
        if($assist_user) $data['assist_user']=$assist_user;
 		// $condition['portal_id']=$id;
		// $theme=M('portal_theme')->where($condition)->order('orders')->field('theme_id,orders,push,isshow')->select();
		// if($theme) $data['theme']=$theme;
		// else $data['theme']=[];

		// $carousel=M('portal_carousel')->where($condition)->field('photo,link,orders')->order('orders')->select();
		// if($carousel) $data['carousel']=$carousel;
		// else $data['carousel']=[];

  //       $article_theme=M('portal_article')->where($condition)->field('theme_id')->select();
  //       if($article_theme) $data['article_theme']=$article_theme;
  //       else $data['article_theme']=[];

        return $data;
	}

	/**
	 * @DateTime 2017-01-04T16:12:33+0800
	 * @param    [int]                   $id [门户id]
	 * @return   [bool]                       [description]
	 */
	public function isdistributed($id){
		if(M('portal')->where('id=%d')->getField('user_id')) return true;
	}

	/**
	 * @DateTime 2017-01-04T16:26:15+0800
	 * @param    [id]                   $id [门户id]
	 * @return   [array]                       [门户轮播图信息]
	 */
	public function portal_carousel($id){
		$data['carousel_layout']=json_decode(M('portal')->where('id=%d',$id)->getField('carousel_layout'));
		//更新轮播图状态
		$condition=['state'=>2,'time'=>['elt',time()]];
		$list=M('portal_carousel')->where($condition)->select();
		$length=count($list);
		for($i=0;$i<$length;$i++){
			$save['state']=1;
			M('portal_carousel')->where('portal_id=%d and orders=%d',$list[$i]['portal_id'],$list[$i]['orders'])->save($save);
		}
		
		$conditionm=['state'=>1,'in_time'=>['egt',time()]];
		$mlist=M('portal_carousel')->where($conditionm)->select();
		$length=count($mlist);
		for($i=0;$i<$length;$i++){
			if(!$data[$i]['time']||($data[$i]['time']<time())){
				$save['state']=2;
				M('portal_carousel')->where('portal_id=%d and orders=%d',$mlist[$i]['portal_id'],$mlist[$i]['orders'])->save($save);
			}
		}

		$data['carousel']=M('portal_carousel')->where('portal_id=%d and state=2',$id)->order('orders')->select();
		$length=count($data['carousel']);
		for($i=0;$i<$length;$i++){
			if(!$data['carousel'][$i]['time']) $data['carousel'][$i]['time']='';
			if(!$data['carousel'][$i]['in_time']) $data['carousel'][$i]['in_time']='';
		}
		return $data;
	}

	/**
	 * @DateTime 2017-01-04T16:29:14+0800
	 * @param    [id]                   $id [门户id]
	 * @return   [array]                       [门户栏目信息]
	 */
	public function portal_theme($id,$user_id){
		$data['theme_layout']=json_decode(M('portal')->where('id=%d',$id)->getField('theme_layout'));
		$condition['portal_id']=$id;
		if($user_id) {
			$default_list=M('portal_theme')->where('push=1')->getField('theme_id',true);
			$user_list=M('portal_theme_push')->where('user_id=%d and portal_id=%d',$user_id,$id)->getField('theme_id',true);
			if(!$default_list) $default_list=[];
			if(!$user_list) $user_list=[];
			$theme_id=array_merge($default_list,$user_list);
			if($theme_id) $condition['theme_id']=['in',$theme_id];
			else return null;
		}
		$theme=M('portal_theme')->where($condition)->field('theme_id as id,isshow,orders,push')->order('orders')->select();
		$list=array_column($theme, 'id');
		$info=D('theme')->theme_list($list);
		if(!$theme) $data['theme']=[];
		else{
			$length=count($theme);
			for ($i=0; $i <$length ; $i++) { 
				if(!$theme[$i]['push']) $theme[$i]['push']='';
				if(!$info[$i]['link']) $info[$i]['link']='';
				else $info[$i]['link']=html_entity_decode($info[$i]['link']);
				$data['theme'][]=['id'=>$theme[$i]['id'],'isshow'=>$theme[$i]['isshow'],'orders'=>$theme[$i]['orders'],'push'=>$theme[$i]['push'],'theme'=>$info[$i]['theme'],'logo'=>$info[$i]['logo'],'link'=>$info[$i]['link'],'nick'=>$info[$i]['nick']];
			}
		}
		return $data;
	}

	/**
	 * @DateTime 2017-01-04T16:29:14+0800
	 * @param    [id]                   $id [门户id]
	 * @return   [array]                       [门户栏目信息]
	 */
	public function portal_article_theme($id){
		$list=M('portal_article')->where('portal_id=%d',$id)->getField('theme_id',true);
		$data['article_layout']=json_decode(M('portal')->where('id=%d',$id)->getField('article_layout'));
		if($list){
			$condition=['portal_id'=>$id,'theme_id'=>['in',$list]];
			$theme=M('portal_theme')->where($condition)->field('theme_id as id')->select();
			$info=D('theme')->theme_list($list);
			$length=count($theme);
			for ($i=0; $i <$length ; $i++) { 
				if(!$theme[$i]['push']) $theme[$i]['push']='';
				if(!$info[$i]['link']) $info[$i]['link']='';
				$data['article_theme'][]=['id'=>$theme[$i]['id'],'theme'=>$info[$i]['theme'],'logo'=>$info[$i]['logo'],'link'=>$info[$i]['link'],'nick'=>$info[$i]['nick']];
		
			}
			return $data;
		}
	}

	/**
	 * @DateTime 2017-01-04T19:24:46+0800
	 * @param    [int]                   $user_id  [用户id]
	 * @param    [array]                   $theme_id [栏目的一维数组]                     
	 */
	public function portal_user_theme($user_id,$theme_id){
		foreach ($theme_id as $key => $value) {
			$condition=['theme_id'=>$value,'user_id'=>$user_id];
			if(!M('portal_user_theme')->where($condition)->find()){
				$data=['theme_id'=>$value,'user_id'=>$user_id,'time'=>time()];
				M('portal_user_theme')->add($data);
			}
			else{
				$data=['time'=>time()];
				M('portal_user_theme')->where($condition)->save($data);
			}
		}
	}

	/**
	 * @DateTime 2017-01-04T20:50:20+0800
	 */
	public function article_update(){
		$condition=['state'=>2,'end_time'=>['lt',time()]];
		$data=M('article')->where($condition)->select();
		$length=count($data);
		for($i=0;$i<$length;$i++){
			$save['state']=1;
			M('article')->where('id=%d',$data[$i]['id'])->save($save);
		}
	}

	/**
	 * @DateTime 2017-01-09T00:53:13+0800
	 * @param    [int]                   $id [文章id]
	 * @return   [bool]                      
	 */
	public function isreceipt($id){
		if(M('article_receipt')->where('article_id=%d',$id)->find()) return true;
	}

	/**
	 * @DateTime 2017-01-14T11:05:51+0800
	 * @param    [int]                   $id [文章id]
	 * @return   [bool]                      
	 */
	public function isvote($id){
		if(M('vote')->where('article_id=%d',$id)->find()) return true;
	}

	/**
	 * @DateTime 2017-01-12T11:12:08+0800
	 * @param    [int]                   $user_id [用户id]
	 * @param    [int]                   $tag_id [标签id]
	 */
	public function user_tag_click($user_id,$tag_id){
    	if(!M('user_tag')->where('tag_id=%d and user_id=%d',$tag_id,$user_id)->find()){
    		$data=['user_id'=>$user_id,'in_time'=>time(),'tag_id'=>$tag_id];
	    	M('user_tag')->add($data);
	    }
	}

	/**
	 * @DateTime 2017-01-17T15:00:29+0800
	 * @param    [int]                   $portal_id [门户id]
	 * @param    [int]                   $main_user_id   [主管理员id]
	 * @return   [bool]                  确保门户栏目主管理员和门户主管理员一致
	 */
	public function portal_theme_management($portal_id,$main_user_id){
		$list=M('portal_theme')->where('portal_id=%d',$portal_id)->getField('theme_id',true);
		if($list){
			$save['user_id']=$main_user_id;
			$condition['theme_id']=['in',$list];
			M('admin_theme')->where($condition)->save($save);
		}
	}

	/**
	 * @DateTime 2017-01-19T10:03:26+0800
	 * @param    [array]                   $type [类型]
	 * @param    [int]                   $key [类型0-意见1-咨询]
	 * @return   [bool]                        
	 */
	public function check_comment_type($type,$key){
		if($key=='0') $arr=C('advice_type');
		else $arr=C('consult_type');
		if(array_intersect($type, $arr)==$type) return true;
	}


	    		


}
