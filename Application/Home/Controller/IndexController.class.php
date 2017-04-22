<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {

     public function array_unique_fb($value) { 
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


    public function get_user_blank($user_id){ 
     	$user_list=M('get_user_blank')->where('id=%d',$user_id)->order('in_time')->getField('theme',true);
		if(!$user_list) $user_list=array();
		$default_list=M('theme')->where('push="1"')->getField('theme',true);
		if(!$default_list) $default_list=array('');
		$list=array_merge($default_list,$user_list);
		$section=M('user_section')->where('user_id=%d',$user_id)->getField('name',true);
		$push=M('theme')->where('isshow=2')->select();
		for($j=0;$j<count($section);$j++){
			for($i=0;$i<count($push);$i++){
				if(strpos($section,$push[$i]['push'])!==false) array_push($list,$push[$i]['theme']);
			}
		}
		return array_unique(array_filter($list));
	}

	public function get_user_blank_id($user_id){
		$user_list=M('user_theme')->where('user_id=%d',$user_id)->getField('theme_id',true);
		if(!$user_list) $user_list=array();
		$default_list=M('theme')->where('push="1"')->getField('id',true);
		if(!$default_list) $default_list=array();
		$list=array_merge($default_list,$user_list);
		$section=M('user_section')->where('user_id=%d',$user_id)->getField('name',true);
		$push=M('theme')->where('isshow=2')->select();
		for($j=0;$j<count($section);$j++){
			for($i=0;$i<count($push);$i++){
				if(strpos($section,$push[$i]['push'])!==false) array_push($list,$push[$i]['id']);
			}
		}
		return array_unique(array_filter($list));
	} 



//顶边栏  首页+各个用户的订阅
	public function blank(){
		$user_id=session('user_id');
		if($user_id){
			$theme_id=$this->get_user_blank_id($user_id);
			if($theme_id){
				$condition['id']=array('in',$theme_id);
				$tempdata=M('theme')->where($condition)->field('id as theme_id,theme,logo')->order('theme','orders')->select();
				$data=splite_array($tempdata,'theme','theme_id');
				for($i=0;$i<count($data);$i++){
					$result[$i]['theme']=$data[$i]['theme'];
					foreach($data[$i]['theme_id'] as $key => $value){
						$result[$i]['theme_id'][$key]=$value['theme_id'];
						$result[$i]['logo'][$key]=$value['logo'];
					}
				}
			}
			else $result='未订阅！';
		}
		else $result='未登录！';
		$this->ajaxreturn($result);
	}

//用户已定栏目
	public function userblank(){
		$user_id=session('user_id');
		if($user_id){
			$theme_id=$this->get_user_blank_id($user_id);
			if($theme_id){
				$condition['theme_id']=array('in',$theme_id);
				$data=M('get_admin_theme')->where($condition)->select();
				$unpush_list=M('order_theme')->getField('id',true);
				$order_list=array_intersect($unpush_list,$theme_id);
				for($i=0;$i<count($data);$i++){
		    		$order[]=array('id'=>$data[$i]['theme_id'],'main_nick'=>$data[$i]['nick'],'theme'=>$data[$i]['theme'],'logo'=>$data[$i]['logo'],'isshow'=>$data[$i]['isshow']);
		    		if(!in_array($order[$i]['id'],$order_list)) $order[$i]['state']='默认订阅';
		    	}
		    }
	    }
	    else $order='未登录！';
	    $this->ajaxreturn($order);
	}

	public function themeapi(){
		$user_id=session('user_id');
		if($user_id){
			$user_theme=$this->get_user_blank_id($user_id);
			$data=M('get_admin_theme')->field('id,theme,nick,logo,theme_id')->where('isshow=2')->order('id desc')->select();
			$unpush_list=M('order_theme')->getField('id',true);
			$order_list=array_intersect($unpush_list,$user_theme);
			$length=count($data);
			for($i=0;$i<$length;$i++){
				$title=M('article_order')->where('theme_id=%d',$data[$i]['theme_id'])->getField('title');
				if($title) $data[$i]['title']=$title;
		    	else $data[$i]['title']='';
				if(in_array($data[$i]['theme_id'],$user_theme)){
					if(!in_array($data[$i]['theme_id'],$order_list)) $data[$i]['state']='默认订阅';
		    		else $data[$i]['state']='已订阅';
				}
				else $data[$i]['state']='未订阅';
	    	}
	    	// //领导隐藏栏目
	    	// $leaderdata=M('get_admin_theme')->field('id,theme,nick,logo,theme_id')->where('isshow=1')->order('id desc')->select();
	    	$order=splite_array($data,'id','theme',array('nick'));
	    }
	    else $order='未登录！';
		$this->ajaxreturn($order);
	}

	public function blankapi(){
		$id=I('id');
		$key=I('key');
		$user_id=session('user_id');
		$allid=M('theme')->getField('id',true);
		$allorder_id=M('order_theme')->getField('id',true);
		if($user_id){
			if(in_array($id,$allid)&&in_array($key,array(1,2))){
				if($key==2){
					$user_theme=$this->get_user_blank_id($user_id);
					if(in_array($id,$voteuser_theme)) $arr=['state'=>'10040','detail'=>'重复订阅！'];
					else{
						$data['user_id']=$user_id;
						$data['theme_id']=$id;
						$data['in_time']=time();
						if(M('user_theme')->add($data)) $arr=['state'=>'0','detail'=>'订阅成功！'];
						else $arr=['state'=>'10001','detail'=>'系统异常！'];
					}
				}
				else{
					if(in_array($id,$allorder_id)){
						if(M('user_theme')->where('user_id=%d and theme_id=%d',$user_id,$id)->delete()) $arr=['state'=>'0','detail'=>'取消订阅成功！'];
						else $arr=['state'=>'10001','detail'=>'系统异常！'];
					}
					else $arr=['state'=>'10047','detail'=>'无法取消默认订阅栏目！'];
				}
			}
			else $arr=['state'=>'10003','detail'=>'参数错误！'];
		}
		else $arr=['state'=>'10000','detail'=>'未登录！'];
		$this->ajaxreturn($arr);
	}

	public function carousel(){
		$key=I('key',2,'intval');
		$user_id=session('user_id');
		if($user_id){
			$theme_id=$this->get_user_blank_id($user_id);
			if($theme_id){
				$where['theme_id']=array('in',$theme_id);
				$where['key']=2;
				$where['state']=2;
				$data=M('article')->where($where)->limit(10)->select();
				for($i=0;$i<count($data);$i++){
					$data[$i]['photo_photo']=M('photo')->where('article_id=%d',$data[$i]['id'])->getField('photo');
					$data[$i]['photo_compress_photo']=M('photo')->where('article_id=%d',$data[$i]['id'])->getField('compress_photo');
					if($key==1){
						$g=$data[$i]['photo'];
						$t=$data[$i]['photo_photo'];
					}
					else{
						$g=$data[$i]['compress_photo'];
						$t=$data[$i]['photo_compress_photo'];
					}
					$order[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'photo_photo'=>$t,'photo'=>$g,'function'=>$data[$i]['function']);
				}
			}
		}
		$this->ajaxreturn($order);
	}

	public function main(){
		$number=I('number',10,'intval');
		$user_id=session('user_id');
		$key=I('key',2,'intval');
		if($user_id){
			$theme_id=$this->get_user_blank_id($user_id);
			if($theme_id){
				$where['theme_id']=array('in',$theme_id);
				$where['key']=1;
				$where['state']=2;
				$data=M('article_order')->where($where)->limit($number)->select();
				
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
						$g2=$data[$i]['compress_photo2'];
						$g3=$data[$i]['compress_photo3'];
						$t=$data[$i]['photo_compress_photo'];
					}
			    	$order[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'author'=>$data[$i]['author'],'photo'=>$g,'photo2'=>$g2,'photo3'=>$g3,'photo_photo'=>$t,'photo_text'=>html_entity_decode($data[$i]['photo_text']),'theme'=>$data[$i]['theme'],'function'=>$data[$i]['function'],'link'=>$data[$i]['link'],'in_time'=>date('m-d H:i',$data[$i]['in_time']),'text'=>html_entity_decode($data[$i]['text']));
			    	$order[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
			    	$order[$i]['sign']=preg(html_entity_decode($data[$i]['text']));	
			    }
				$arr['state']='0';
	            $arr['order']=$order;
			}
		}
		else{
			$arr['state']='10000';
            $arr['detail']='未登录！';
		}
		$this->ajaxreturn($arr);
	}

	public function ajaxmain_list(){
		$number=I('number',10,'intval');
		$page=I('page',1,'intval');
		$key=I('key',2,'intval');
		$user_id=session('user_id');
		if($user_id){
			$theme_id=$this->get_user_blank_id($user_id);
			if($theme_id){
				$where['theme_id']=array('in',$theme_id);
				$where['key']=1;
				$where['state']=2;
				$data=M('article_order')->where($where)->page($page,$number)->select();
				
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
						$g2=$data[$i]['compress_photo2'];
						$g3=$data[$i]['compress_photo3'];
						$t=$data[$i]['photo_compress_photo'];
					}
			    	$order[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'author'=>$data[$i]['author'],'photo'=>$g,'photo2'=>$g2,'photo3'=>$g3,'photo_photo'=>$t,'photo_text'=>html_entity_decode($data[$i]['photo_text']),'theme'=>$data[$i]['theme'],'function'=>$data[$i]['function'],'link'=>$data[$i]['link'],'in_time'=>date('m-d H:i',$data[$i]['in_time']),'text'=>html_entity_decode($data[$i]['text']));
			    	$order[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
			    	$order[$i]['sign']=preg(html_entity_decode($data[$i]['text']));	
				}
			}
		}
		else{
			$order='未登录！';
		}
		$this->ajaxreturn($order);
	}

	public function photo_news(){
		$id=I('id');
		//$id=2;
		$allid=M('article')->where('function=2 and state=2')->getField('id',true);
		$key=I('key',2,'intval');
		if(in_array($id,$allid)){
			$data=M('get_photo')->where('id=%d',$id)->select();
			for($i=0;$i<count($data);$i++){
				if($key==1) $t=$data[$i]['photo_photo'];
				else $t=$data[$i]['photo_compress_photo'];
		    	$temporder[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'author'=>$data[$i]['author'],'photo_photo'=>$t,'photo_text'=>html_entity_decode($data[$i]['photo_text']),'theme'=>$data[$i]['theme'],'in_time'=>date('m-d H:i',$dataF[$i]['in_time']));
			}
			$order=splite_array($temporder,'id','detail',array('title','author','in_time','theme'));
		}
		$this->ajaxreturn($order);
	}

	public function lists(){
		$number=I('number',10,'intval');
		$theme_id=I('theme');
		$user_id=session('user_id');
		// $theme_id=[13,27];
		if(!$user_id) $order=['state'=>0,'detail'=>'未登录！'];
		else if(!D('portal')->check_id(M('theme'),$theme_id,$condition)) $order=['state'=>10003,'detail'=>'参数错误！'];
		else{
			D('portal')->portal_user_theme($user_id,$theme_id);
			$where['theme_id']=array('in',$theme_id);
			$where['state']=2;
			$data=M('article_order')->where($where)->limit($number)->select();
			
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
					$g2=$data[$i]['compress_photo2'];
					$g3=$data[$i]['compress_photo3'];
					$t=$data[$i]['photo_compress_photo'];
				}
		    	$order[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'author'=>$data[$i]['author'],'photo'=>$g,'photo2'=>$g2,'photo3'=>$g3,'photo_photo'=>$t,'photo_text'=>html_entity_decode($data[$i]['photo_text']),'theme'=>$data[$i]['theme'],'function'=>$data[$i]['function'],'link'=>$data[$i]['link'],'in_time'=>date('m-d H:i',$data[$i]['in_time']),'text'=>html_entity_decode($data[$i]['text']));
		    	$order[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
		    	$order[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
		    	$order[$i]['sign']=preg(html_entity_decode($data[$i]['text']));			
			}
		}
		$this->ajaxreturn($order);
	}

	public function ajaxlists(){
		$number=I('number',10,'intval');
		$page=I('page',1,'intval');
		$theme_id=I('theme');
		$user_id=session('user_id');
		if(!$user_id) $order=['state'=>0,'detail'=>'未登录！'];
		else if(!D('portal')->check_id(M('theme'),$theme_id,$condition)) $order=['state'=>10003,'detail'=>'参数错误！'];
		else{
			D('portal')->portal_user_theme($user_id,$theme_id);
			$where['theme_id']=array('in',$theme_id);
			$where['state']=2;
			$data=M('article_order')->where($where)->page($page,$number)->select();
			 
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
					$g2=$data[$i]['compress_photo2'];
					$g3=$data[$i]['compress_photo3'];
					$t=$data[$i]['photo_compress_photo'];
				}
		    	$order[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'author'=>$data[$i]['author'],'photo'=>$g,'photo2'=>$g2,'photo3'=>$g3,'photo_photo'=>$t,'photo_text'=>html_entity_decode($data[$i]['photo_text']),'theme'=>$data[$i]['theme'],'function'=>$data[$i]['function'],'link'=>$data[$i]['link'],'in_time'=>date('m-d H:i',$data[$i]['in_time']),'text'=>html_entity_decode($data[$i]['text']));
		    	$order[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
		    	$order[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
		    	$order[$i]['sign']=preg(html_entity_decode($data[$i]['text']));		
			}
		}
		$this->ajaxreturn($order);
	}


	Public function tag_list(){
		$number=I('number',10,'intval');
		$id=I('theme');
		$user_id=session('user_id');
	    if($user_id){
	    	if(!M('user_tag')->where('tag_id=%d and user_id=%d',$id,$user_id)->find()){
		    	$data1['user_id']=$user_id;
		    	$data1['in_time']=time();
		    	$data1['tag_id']=$id;
		    	M('user_tag')->add($data1);
		    }
	    }
		if($id){
			 
			$theme_id=$this->get_user_blank_id($user_id);
			D('portal')->portal_user_theme($user_id,$theme_id);
			$article_id=M('article_tag')->where('tag_id=%d',$id)->getField('article_id',true);
			$where['theme_id']=array('in',$theme_id);
			$where['id']=array('in',$article_id);
			$where['state']=2;
			$data=M('article_order')->where($where)->limit($number)->select();
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
					$g2=$data[$i]['compress_photo2'];
					$g3=$data[$i]['compress_photo3'];
					$t=$data[$i]['photo_compress_photo'];
				}
		    	$order[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'author'=>$data[$i]['author'],'photo'=>$g,'photo2'=>$g2,'photo3'=>$g3,'photo_photo'=>$t,'photo_text'=>html_entity_decode($data[$i]['photo_text']),'theme'=>$data[$i]['theme'],'function'=>$data[$i]['function'],'link'=>$data[$i]['link'],'in_time'=>date('m-d H:i',$data[$i]['in_time']),'text'=>html_entity_decode($data[$i]['text']));
		    	$order[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    $order[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    $order[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
		    	$order[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
		    	$order[$i]['sign']=preg(html_entity_decode($data[$i]['text']));		
			}
		}
		$this->ajaxreturn($order);
	}

	Public function ajaxtag_list(){
		$number=I('number',10,'intval');
		$id=I('theme');
		$page=I('page',1,'intval');
		$user_id=session('user_id');
		if($id&&$user_id){
			 
			$theme_id=$this->get_user_blank_id($user_id);
			D('portal')->portal_user_theme($user_id,$theme_id);
			$article_id=M('article_tag')->where('tag_id=%d',$id)->getField('article_id',true);
			$where['theme_id']=array('in',$theme_id);
			$where['id']=array('in',$article_id);
			$where['state']=2;
			$data=M('article_order')->where($where)->page($page,$number)->select();
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
					$g2=$data[$i]['compress_photo2'];
					$g3=$data[$i]['compress_photo3'];
					$t=$data[$i]['photo_compress_photo'];
				}
		    	$order[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'author'=>$data[$i]['author'],'photo'=>$g,'photo2'=>$g2,'photo3'=>$g3,'photo_photo'=>$t,'photo_text'=>html_entity_decode($data[$i]['photo_text']),'function'=>$data[$i]['function'],'link'=>$data[$i]['link'],'in_time'=>date('m-d H:i',$data[$i]['in_time']),'text'=>html_entity_decode($data[$i]['text']));
		    	$order[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    $order[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    $order[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
		    	$order[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
		    	$order[$i]['sign']=preg(html_entity_decode($data[$i]['text']));		
			}
		}
		$this->ajaxreturn($order);
	}

	public function searchapi(){
    	$key=I('key');
    	$number=I('number',10,'intval');
    	$user_id=session('user_id');
    	if($user_id){
    		 
    		$theme_id=$this->get_user_blank_id($user_id);
			D('portal')->portal_user_theme($user_id,$theme_id);
	    	$condition['title|author|theme']=array('like','%'.$key.'%');
	    	$condition['theme_id']=array('in',$theme_id);
	    	$condition['state']=2;
	    	$data=M('get_article')->where($condition)->limit($number)->select();
	    	if($key){
				for($i=0;$i<count($data);$i++){
					if($key==1){
						$g=$data[$i]['photo'];
						$g2=$data[$i]['photo2'];
						$g3=$data[$i]['photo3'];
						$t=$data[$i]['photo_photo'];
					}
					else{
						$g=$data[$i]['compress_photo'];
						$g2=$data[$i]['compress_photo2'];
						$g3=$data[$i]['compress_photo3'];
						$t=$data[$i]['photo_compress_photo'];
					}
			    	$order[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'author'=>$data[$i]['author'],'photo'=>$g,'photo2'=>$g2,'photo3'=>$g3,'photo_photo'=>$t,'photo_text'=>html_entity_decode($data[$i]['photo_text']),'theme'=>$data[$i]['theme'],'function'=>$data[$i]['function'],'link'=>$data[$i]['link'],'in_time'=>date('m-d H:i',$data[$i]['in_time']),'text'=>html_entity_decode($data[$i]['text']));
			    	$order[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
			    	$order[$i]['sign']=preg(html_entity_decode($data[$i]['text']));		
				}
				if($order){
					$arr['state']='0';
					$arr['order']=$order;
				}
				else{
					$arr['state']='10004';
					$arr['detail']='搜索内容不存在！';
				}
			}
			else{
				$arr['state']='10021';
				$arr['detail']='输入不能为空！';
			}
		}
		else $arr=['state'=>'10000','detail'=>'未登录！'];				
        $this->ajaxreturn($arr);
    }

    public function ajax_searchapi(){
    	$key=I('key');
    	$number=I('number',10,'intval');
		$page=I('page',1,'intval');
		$user_id=session('user_id');
    	if($user_id){
    		 
    		$theme_id=$this->get_user_blank_id($user_id);
			D('portal')->portal_user_theme($user_id,$theme_id);
	    	$condition['title|author|theme']=array('like','%'.$key.'%');
	    	$condition['theme_id']=array('in',$theme_id);
	    	$condition['state']=2;
       		$data=M('get_article')->where($condition)->page($page,$number)->select();
	       	if($key){
				for($i=0;$i<count($data);$i++){
					if($key==1){
						$g=$data[$i]['photo'];
						$g2=$data[$i]['photo2'];
						$g3=$data[$i]['photo3'];
						$t=$data[$i]['photo_photo'];
					}
					else{
						$g=$data[$i]['compress_photo'];
						$g2=$data[$i]['compress_photo2'];
						$g3=$data[$i]['compress_photo3'];
						$t=$data[$i]['photo_compress_photo'];
					}
			    	$order[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'author'=>$data[$i]['author'],'photo'=>$g,'photo2'=>$g2,'photo3'=>$g3,'photo_photo'=>$t,'photo_text'=>html_entity_decode($data[$i]['photo_text']),'theme'=>$data[$i]['theme'],'function'=>$data[$i]['function'],'link'=>$data[$i]['link'],'in_time'=>date('m-d H:i',$data[$i]['in_time']),'text'=>html_entity_decode($data[$i]['text']));
			    	$order[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			   	 	$order[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
			    	$order[$i]['sign']=preg(html_entity_decode($data[$i]['text']));			
				}
				if($order){
					$arr['state']='0';
					$arr['order']=$order;
				}
				else{
					$arr['state']='10004';
					$arr['detail']='搜索内容不存在！';
				}
			}
			else{
				$arr['state']='10021';
				$arr['detail']='输入不能为空！';
			}
		}	
		else $arr=['state'=>'10000','detail'=>'未登录！'];			
        $this->ajaxreturn($arr);
    }

    public function hotwords(){
    	$order=M('hotwords')->select();
    	$this->ajaxreturn($order);
    }

     public function show(){
	    $id=I('id');
	    $key=I('key');
	    $allid=M('article')->getField('id',true);
	    $user_id=session('user_id');
	    if($user_id){
	    	if(!M('user_article')->where('article_id=%d and user_id=%d',$id,$user_id)->find()){
		    	$data1['user_id']=$user_id;
		    	$data1['in_time']=time();
		    	$data1['article_id']=$id;
		    	M('user_article')->add($data1);
		    }
	    }
	    if(in_array($id,$allid)){
		    $data=M('article')->where('id=%d',$id)->select();
		    $like_user_id=M('article_like')->where('article_id=%d',$id)->getField('user_id',true);
		    $dislike_user_id=M('article_dislike')->where('article_id=%d',$id)->getField('user_id',true);
		    for($i=0;$i<count($data);$i++){
				if($key==1) $g=$data[$i]['photo'];
				else $g=$data[$i]['compress_photo'];
				$data[$i]['theme']=M('theme')->where('id=%d',$data[$i]['theme_id'])->getField('theme');
		    	$order[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'author'=>$data[$i]['author'],'photo'=>$g,'theme_id'=>$data[$i]['theme_id'],'theme'=>$data[$i]['theme'],'function'=>$data[$i]['function'],'link'=>$data[$i]['link'],'in_time'=>date('m-d H:i',$data[$i]['in_time']),'text'=>html_entity_decode($data[$i]['text']));
           		
           		if($data[$i]['end_time']) $order[$i]['end_time']=date('Y-m-d H:i:s',$data[$i]['end_time']);
				$order[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    $order[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    $order[$i]['dislike_count']=count(M('article_dislike')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    $order[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();

			    $receipt=M('article_receipt')->where('article_id=%d',$id)->field('id,name')->select();
            	if($receipt) $order[$i]['receipt']=$receipt;
            	if($data[$i]['receipt_title']) $order[$i]['receipt_title']=$data[$i]['receipt_title'];
            	if($user_id){
	            	 $condition=['article_id'=>$id,'user_id'=>$user_id];
	            	 if(M('article_receipt_answer')->where($condition)->find()) $order[$i]['isreceipt']=1;
	            }

			}

			if(in_array($user_id,$like_user_id)) $order[0]['article_like']=1;
			if(in_array($user_id,$dislike_user_id)) $order[0]['article_dislike']=1;
			if($order[0]['function']==2){
                if($key==1) $photo=M('get_photo')->where('id=%d',$id)->field('photo_photo,photo_text')->select();
                else $photo=M('get_photo')->where('id=%d',$id)->field('photo_compress_photo as photo_photo,photo_text')->select();
                for($i=0;$i<count($photo);$i++){
                    $order['photo_photo'][$i]['photo']=$photo[$i]['photo_photo'];
                    $order['photo_photo'][$i]['text']=$photo[$i]['photo_text'];
                }
            }

            $vote_id=M('vote')->where('article_id=%d',$id)->getField('id',true);
            if($vote_id){
            	 $order[0]['vote_id']=$vote_id;
            	 if($user_id){
	            	 $condition=['vote_id'=>['in',$vote_id]];
	            	 $question_id=M('vote_question')->where($condition)->getField('id',true);
	            	 $condition=['question_id'=>['in',$question_id],'user_id'=>$user_id];
	            	 if(M('vote_select')->where($condition)->find()) $order[0]['isvote']=1;
	            }
	        }

            $order[0]['read_count']=count(M('user_article')->where('article_id=%d',$id)->getField('id',true));
           	if($data[0]['isshow']=='0') {
           		$list=M('article_note')->where('article_id=%d',$id)->getField('user_id',true);
           		$condition=['user_id'=>['in',$list],'article_id'=>$id];
           		$order[0]['read_count']=count(M('user_article')->where($condition)->select());
           		$order[0]['unread_count']=count($list)-$order[0]['read_count'];
           	}
		}	
		$this->ajaxreturn($order);
    }

  	public function commentapi(){
        $id=I('id',0,'intval');
        $text=I('text');
        $type=I('type');
        $user_id=session('user_id');
        $allid=M('article')->getField('id',true);
        if($user_id){
            if(in_array($id,$allid)){
                if($text){
                    $data['article_id']=$id;
                    $data['text']=$text;
                    $data['user_id']=$user_id;
                    $data['in_time']=time();
                    $data['type']=$type;
                    $comment_id=M('comment')->add($data);
                    if($comment_id){
                        $arr['state']='0';
                        $arr['comment_id']=$comment_id;
                    }
                    else{
                        $arr['state']='10001';
                        $arr['detail']='系统异常';
                    }
                }
                else{
                    $arr['state']='10002';
                    $arr['detail']='输入不能为空';
                }
            }
            else{
                $arr['state']='10003';
                $arr['detail']='参数错误';
            }
        }
        else{
            $arr['state']='10000';
            $arr['detail']='未登录'; 
        }
        $this->ajaxreturn($arr);
    }

    public function comment(){
    	$id=I('id','intval');
    	$allid=M('article')->getField('id',true);
    	$number=I('number',5);
    	$user_id=session('user_id');
        if(in_array($id,$allid)){
    		$data=M('user_comment_list')->where('article_id=%d',$id)->order('in_time desc')->limit($number)->select();
    		for($i=0;$i<count($data);$i++){
    			$order[]=array('id'=>$data[$i]['id'],'count'=>$data[$i]['count'],'nick'=>$data[$i]['nick'],'section'=>D('section')->get_user_section($data[$i]['user_id']),'avatar'=>$data[$i]['avatar'],'in_time'=>$this->time_setting($data[$i]['in_time']),'text'=>html_entity_decode($data[$i]['text']),'dislike_count'=>count(M('comment_dislike')->where('comment_id=%d',$data[$i]['id'])->select()));

    			$condition=['comment_id'=>$data[$i]['id'],'user_id'=>$user_id];
    			if(M('comment_like')->where($condition)->find()) $order[$i]['comment_like']=1;
    			if(M('comment_dislike')->where($condition)->find()) $order[$i]['comment_dislike']=1;
    			if(M('comment_admin_reply')->where('comment_id=%d',$data[$i]['id'])->find()) $data[$i]['isreply']=1;
    		}
    	}
    	$this->ajaxreturn($order);
    }

    public function ajaxcommentlist(){
    	$id=I('id','intval');
    	$number=I('number',10,'intval');
		$page=I('page',1,'intval');
    	$allid=M('article')->getField('id',true);
    	$user_id=session('user_id');
        if(in_array($id,$allid)){
    		$data=M('user_comment_list')->where('article_id=%d',$id)->order('in_time desc')->select();
    		for($i=0;$i<count($data);$i++){
    			$temporder[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'count'=>$data[$i]['count'],'section'=>D('section')->get_user_section($data[$i]['user_id']),'nick'=>$data[$i]['nick'],'avatar'=>$data[$i]['avatar'],'in_time'=>$this->time_setting($data[$i]['in_time']),'text'=>html_entity_decode($data[$i]['text']),'dislike_count'=>count(M('comment_dislike')->where('comment_id=%d',$data[$i]['id'])->select()));
    			$condition=['comment_id'=>$data[$i]['id'],'user_id'=>$user_id];
    			if(M('comment_like')->where($condition)->find()) $order[$i]['comment_like']=1;
    			if(M('comment_dislike')->where($condition)->find()) $order[$i]['comment_dislike']=1;
    			if(M('comment_admin_reply')->where('comment_id=%d',$data[$i]['id'])->find()) $data[$i]['isreply']=1;
    		}
    		$order=array_slice($temporder,($page-1)*$number,$number);
    	}
    	$this->ajaxreturn($order);
    }
    
    private function time_setting($day){
		$rtime = getdate($day);  
		$month = $rtime["mon"];
		$days = $rtime["mday"];
		$year = $rtime["year"];                          
	  	$time=time()-$day;
	  	$todaytime = strtotime("today");                
  		$time1 = time() - $todaytime;                        
  		if($time < 60) $str = '刚刚'; 
  		else if($time < 60 * 60){                                  
  			$min = floor($time/60);                         
  			$str = $min.'分钟前';               
  		}
	  	else if($time < $time1) $str = '今天'; 
		else $str = $year.'年'.$month.'月'.$days.'日';    
		return $str;
	 } 

	public function commentcount(){
	 	$id=I('id','intval');
    	$allid=M('article')->getField('id',true);
        if(in_array($id,$allid)) $order=count(M('user_comment')->where('article_id=%d',$id)->select());
        $this->ajaxreturn($order);
	}

	public function article_like_count(){
	 	$id=I('id','intval');
    	$allid=M('article')->getField('id',true);
        if(in_array($id,$allid)) $order=count(M('article_like')->where('article_id=%d',$id)->select());
        $this->ajaxreturn($order);
	}


	public function article_likeapi(){
		$article_id=I('id');
		$user_id=session('user_id');
    	$allid=M('article')->getField('id',true);
        if(in_array($article_id,$allid)){
        	if($user_id){
				if(!M('article_like')->where("article_id=%d and user_id=%d",$article_id,$user_id)->find()){
					$data["user_id"]=$user_id;
					$data["article_id"]=$article_id;
					$data['in_time']=time();
					if(M("article_like")->add($data)){
						$arr['state']='0';
                        $arr['detail']='收藏成功！';
					}
					else{
						$arr['state']='10001';
                        $arr['detail']='系统异常！';
					}
				}
				else{
					if(M('article_like')->where("article_id=%d and user_id=%d",$article_id,$user_id)->delete()){
						$arr['state']='10005';
                        $arr['detail']='取消收藏成功！';
					}
					else{
						$arr['state']='10001';
                        $arr['detail']='系统异常！';
					}
				}
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

	public function comment_likeapi(){
		$comment_id=I('id');
		$user_id=session('user_id');
    	$allid=M('comment')->getField('id',true);
        if(in_array($comment_id,$allid)){
        	if($user_id){
				if(!M('comment_like')->where("comment_id=%d and user_id=%d",$comment_id,$user_id)->find()){
					$data["user_id"]=$user_id;
					$data["comment_id"]=$comment_id;
					$data['in_time']=time();
					if(M("comment_like")->add($data)){
						$arr['state']='0';
                        $arr['detail']='点赞成功！';
					}
					else{
						$arr['state']='10001';
                        $arr['detail']='系统异常！';
					}
				}
				else{
					if(M('comment_like')->where("comment_id=%d and user_id=%d",$comment_id,$user_id)->delete()){
						$arr['state']='10005';
                        $arr['detail']='取消点赞成功！';
					}
					else{
						$arr['state']='10001';
                        $arr['detail']='系统异常！';
					}
				}
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

	public function reply_likeapi(){
		$reply_id=I('id');
		$user_id=session('user_id');
    	$allid=M('comment_reply')->getField('id',true);
        if(in_array($reply_id,$allid)){
        	if($user_id){
				if(!M('comment_reply_like')->where("reply_id=%d and user_id=%d",$reply_id,$user_id)->find()){
					$data["user_id"]=$user_id;
					$data["reply_id"]=$reply_id;
					$data['in_time']=time();
					if(M("comment_reply_like")->add($data)){
						$arr['state']='0';
                        $arr['detail']='点赞成功！';
					}
					else{
						$arr['state']='10001';
                        $arr['detail']='系统异常！';
					}
				}
				else{
					if(M('comment_reply_like')->where("reply_id=%d and user_id=%d",$reply_id,$user_id)->delete()){
						$arr['state']='10005';
                        $arr['detail']='取消点赞成功！';
					}
					else{
						$arr['state']='10001';
                        $arr['detail']='系统异常！';
					}
				}
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


	public function vote(){
		$id=I('id');
		// $id=1;
		$user_id=session('user_id');
		// $user_id=10022;
		$allid=M('vote_choice')->getField('id',true);
		$time=time();
		if($user_id){
			if(in_array($id,$allid)){
				$end=M('get_vote_select')->where('choice_id=%d',$id)->getField('end');
				$vote_id=M('get_vote_select')->where('choice_id=%d',$id)->getField('id');
				$alluser_id=M('get_vote_select')->where('id=%d',$vote_id)->getField('user_id',true);
				if($time<$end){
					if($user_id){
						if(in_array($user_id,$alluser_id)){
							$arr['state']='10070';
				            $arr['detail']='您已投过票！';
						}
						else{
							$data['choice_id']=$id;
							$data['user_id']=$user_id;
							$data['in_time']=time();
							if(M('vote_select')->add($data)){
								$arr['state']='0';
					            $arr['detail']='投票成功！';
							}
							else{
								$arr['state']='10001';
					            $arr['detail']='系统异常！';
							}
						}
						
					}
					else{
						$arr['state']='10000';
			            $arr['detail']='用户未登录！';
					}
				}
				else{
					$newdata['state']=1;
					M('vote')->where('id=%d',$id)->save($newdata);
					$arr['state']='10060';
			        $arr['detail']='投票已截止！';
				}
				
			}
			else{
				$arr['state']='10003';
	            $arr['detail']='参数错误！';
			}
		}
		else{
			$arr['state']='10000';
	        $arr['detail']='未登录！';
		}
		$this->ajaxreturn($arr);
	}

	public function vote_detail(){
		$id=I('id');
		// $id=27;
		$user_id=session('user_id');
		$allid=M('vote')->getField('id',true);
		if(in_array($id,$allid)){
			if($user_id){
				$data=M('get_vote_count')->where('id=%d',$id)->field('id,vote,start,end,count,choice,choice_id,choice_photo')->select();
				$user_choice_id=M('get_vote_select')->where('id=%d and user_id=%d',$id,$user_id)->getField('choice_id');
				for($i=0;$i<count($data);$i++){
	    			$temporder[]=array('id'=>$data[$i]['id'],'vote'=>$data[$i]['vote'],'start'=>date('Y-m-d H:i:s',$data[$i]['start']),'end'=>date('Y-m-d H:i:s',$data[$i]['end']),'count'=>$data[$i]['count'],'choice'=>$data[$i]['choice'],'choice_id'=>$data[$i]['choice_id'],'choice_photo'=>$data[$i]['choice_photo']);
	    			if($data[$i]['choice_id']==$user_choice_id){
	    				$temporder[$i]['checked']=true;
	    			}
	    			if(!($data[$i]['end']>time())){
	    				$temporder[$i]['outdate']=true;
	    				$newdata['state']=1;
						M('vote')->where('id=%d',$id)->save($newdata);
	    			}
	    			else{
	    				$temporder[$i]['outdate']=false;
	    			}
	    		}
				$order=splite_array($temporder,'id','detail',array('vote','start','end','outdate'));
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

	public function AjaxPolicySearchapi(){
    	$key=I('key');
    	$number=I('number',10,'intval');
		$page=I('page',1,'intval');
		$user_id=session('user_id');
    	if($user_id){
    		$theme_id=216;
	    	$condition['title|author']=array('like','%'.$key.'%');
	    	$condition['theme_id']=$theme_id;
	    	$condition['state']=2;
       		$data=M('get_article')->where($condition)->page($page,$number)->select();
	       	if($key){
				for($i=0;$i<count($data);$i++){
					if($key==1){
						$g=$data[$i]['photo'];
						$g2=$data[$i]['photo2'];
						$g3=$data[$i]['photo3'];
						$t=$data[$i]['photo_photo'];
					}
					else{
						$g=$data[$i]['compress_photo'];
						$g2=$data[$i]['compress_photo2'];
						$g3=$data[$i]['compress_photo3'];
						$t=$data[$i]['photo_compress_photo'];
					}
			    	$order[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'author'=>$data[$i]['author'],'photo'=>$g,'photo2'=>$g2,'photo3'=>$g3,'photo_photo'=>$t,'photo_text'=>html_entity_decode($data[$i]['photo_text']),'theme'=>$data[$i]['theme'],'function'=>$data[$i]['function'],'link'=>$data[$i]['link'],'in_time'=>date('m-d H:i',$data[$i]['in_time']),'text'=>html_entity_decode($data[$i]['text']));
			    	$order[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			   	 	$order[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
			    	$order[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
			    	$order[$i]['sign']=preg(html_entity_decode($data[$i]['text']));		

				}
				if($order){
					$arr['state']='0';
					$arr['order']=$order;
				}
				else{
					$arr['state']='10004';
					$arr['detail']='搜索内容不存在！';
				}
			}
			else{
				$arr['state']='10021';
				$arr['detail']='输入不能为空！';
			}
		}	
		else $arr=['state'=>'10000','detail'=>'未登录！'];			
        $this->ajaxreturn($arr);
    }

	public function httpGet($url) {
	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    $res = curl_exec($curl);
	    curl_close($curl);
	    return $res;
    }

	public function userdata(){
		$token=D('token')->get_admin_token(C('client_id'),C('clientsecret'));
		$open_id=I('open_id');
		// $open_id='e7e5ead5411d0c063bbd01ce5b01e440';
	    $access_token=$token['access_token'];
	    if(!$access_token) $arr=array('state'=>'10056','detail'=>'秘钥获取失败！');
	    else if(!$open_id) $arr=array('state'=>'10055','detail'=>'用户open_id获取失败！');
		else{
			$url=C('url')."/openapi/user/get_info?access_token=".$access_token."&open_id=".$open_id;
			$code=$this->httpGet($url);
		    $object=json_decode($code);
		    $ret=$object->ret;
		    $data=$object->data;
		    $url=C('url')."/openapi/user/get_blocs?access_token=".$access_token."&open_id=".$open_id;
		    $code=$this->httpGet($url);
			$object=json_decode($code);
		    $ret1=$object->ret1;
		    $data1=$object->data;
		    $orgs=$data1->orgs;
		    $groups=$orgs[0]->name;
		    if($ret==0&&$ret1==0){
		    	M()->startTrans();
		    	$user_data['nick']=$data->name;
		    	$user_data['guid']=$data->open_id;
		    	$user_data['mobile']=$data->mobile;
		    	if($data->photo) $user_data['avatar']=$data->photo;
		    	else $user_data['avatar']='/defaultlogo.png';
		    	$sex=$data->gender;
		    	if($sex==1) $user_data['gender']='女';
		    	else $user_data['gender']='男';
		    	$allguid=M('user')->getField('guid',true);
		    	if(!in_array($user_data['guid'], $allguid)) $user_id=M('user')->add($user_data);
		    	else{
		    		M('user')->where('guid="%s"',$user_data['guid'])->save($user_data);
		    		$user_id=M('user')->where('guid="%s"',$user_data['guid'])->getField('id');
		    	}
		    	session('user_id',$user_id);
		    	if($groups){
			    	for($i=0;$i<count($orgs);$i++){
			    		$groups=$orgs[$i]->name;
			    		$group_data=['name'=>$groups,'user_id'=>$user_id,'id'=>$orgs[$i]->id];

			    		M('user_section')->where('user_id=%d',$user_id)->delete();
			    		$result=M('user_section')->add($group_data);
			    		if($result==false) break;
			    		if(!M('section')->where('section="%s"',$groups)->find()){
				   			$save=['section'=>$groups];
				   			$result=$result&&M('section')->add($save);
					   	}
					}
		    	}
		    	else $result=1;

		    	if($result&&$user_id) {
		    		M()->commit();
		    		$arr=array('state'=>'0','detail'=>'用户数据交互成功！');
		    	}
		    	else {
		    		M()->rollback();
		    		$arr=array('state'=>'10051','detail'=>'用户数据交互失败！');
		    	}
		    }
		    else $arr=array('state'=>'10055','detail'=>'用户信息获取失败！');
		}
	    $this->ajaxreturn($arr);
	}

	public function a(){
		$token=D('token')->get_admin_token(C('client_id'),C('clientsecret'));
		$open_id=I('open_id');
		// $open_id='e7e5ead5411d0c063bbd01ce5b01e440';
	    $access_token=$token['access_token'];
	    if(!$access_token) $arr=array('state'=>'10056','detail'=>'秘钥获取失败！');
	    else if(!$open_id) $arr=array('state'=>'10055','detail'=>'用户open_id获取失败！');
		else{
			$url=C('url')."/openapi/user/get_info?access_token=".$access_token."&open_id=".$open_id;
			$code=$this->httpGet($url);
		    $object=json_decode($code);
		    $ret=$object->ret;
		    $data=$object->data;
		    $url=C('url')."/openapi/user/get_blocs?access_token=".$access_token."&open_id=".$open_id;
		    $code=$this->httpGet($url);
			$object=json_decode($code);
		    $ret1=$object->ret1;
		    $data1=$object->data;
		    $orgs=$data1->orgs;
		    $groups=$orgs[0]->name;
		    if($ret==0&&$ret1==0){
		    	M()->startTrans();
		    	$user_data['nick']=$data->name;
		    	$user_data['guid']=$data->open_id;
		    	$user_data['mobile']=$data->mobile;
		    	if($data->photo) $user_data['avatar']=$data->photo;
		    	else $user_data['avatar']='/defaultlogo.png';
		    	$sex=$data->gender;
		    	if($sex==1) $user_data['gender']='女';
		    	else $user_data['gender']='男';
		    	$allguid=M('user')->getField('guid',true);
		    	if(!in_array($user_data['guid'], $allguid)) $user_id=M('user')->add($user_data);
		    	else{
		    		M('user')->where('guid="%s"',$user_data['guid'])->save($user_data);
		    		$user_id=M('user')->where('guid="%s"',$user_data['guid'])->getField('id');
		    	}
		    	session('user_id',$user_id);
		    	if($groups){
			    	for($i=0;$i<count($orgs);$i++){
			    		$groups=$orgs[$i]->name;
			    		$group_data=['name'=>$groups,'user_id'=>$user_id,'id'=>$orgs[$i]->id];

			    		M('user_section')->where('user_id=%d',$user_id)->delete();
			    		$result=M('user_section')->add($group_data);
			    		if($result==false) break;
			    		if(!M('section')->where('section="%s"',$groups)->find()){
				   			$save=['section'=>$groups];
				   			$result=$result&&M('section')->add($save);
					   	}
					}
		    	}
		    	else $result=1;
		    	if($result&&$user_id) {
		    		M()->commit();
		    		$arr=array('state'=>'0','detail'=>'用户数据交互成功！');
		    	}
		    	else {
		    		M()->rollback();
		    		$arr=array('state'=>'10051','detail'=>'用户数据交互失败！');
		    	}
		    }
		    else $arr=array('state'=>'10055','detail'=>'用户信息获取失败！');
		}
	    $this->ajaxreturn($arr);

	}
	








}
