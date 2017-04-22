<?php
namespace Home\Model;
class ContributeModel{//投稿类


	/*投稿栏目判断
		参数   theme_id  栏目id
		参数   user_id   用户id
	    返回   bool
	*/

	Public function theme_judge($user_id,$theme_id){
		$allid=A('Index')->get_user_blank_id($user_id);
		if(in_array($theme_id,$allid)) return true;
	}

	/*投稿统计
		参数   user_id   用户id
	    返回   bool
	*/
	public function save_contribute($user_id){
		$info=M('user')->where('id=%d',$user_id)->find();
		$save['count']=$info['count']+1;
		
		if(!M('section')->where('section="%s"',$info['section'])->find()){
			$data=array('section'=>$info['section']);
			M('section')->add($data);
		}
		$sinfo=M('section')->where('section="%s"',$info['section'])->find();
		$ssave['count']=$sinfo['count']+1;
		if(M('user')->where('id=%d',$user_id)->save($save)&&M('section')->where('section="%s"',$info['section'])->save($ssave)) return true;
	}

	/*投稿统计
		参数   user_id   用户id
	    返回   bool
	*/
	public function save_back_contribute($user_id){
		$info=M('admin')->where('id=%d',$user_id)->find();
		$sinfo=M('section')->where('section="%s"',$info['nick'])->find();
		$save['backcount']=$sinfo['backcount']+1;
		if(M('section')->where('section="%s"',$info['nick'])->find()) {
			M('section')->where('section="%s"',$info['nick'])->save($save);
			return true;
		}
		else return true;
	}

	/*投稿采纳统计
		参数   user_id   用户id
	    返回   bool
	*/
	public function save_incontribute($user_id){
		$info=M('user')->where('id=%d',$user_id)->find();
		$save['incount']=$info['incount']+1;
		
		$sinfo=M('section')->where('section="%s"',$info['section'])->find();
		$ssave['incount']=$sinfo['incount']+1;
		if(M('user')->where('id=%d',$user_id)->save($save)&&M('section')->where('section="%s"',$info['section'])->save($ssave)) return true;
	}


}