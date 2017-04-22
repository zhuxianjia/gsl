<?php
namespace Home\Model;
class ConferenceModel{//会议签到类

	/*会议id判断
	  参数 int  id 会议id
	  返回 bool
	*/

	public function check_conference_id($id){
		if(M('conference')->where('id=%d',$id)->find()) return true;		
	}

	/*获得会议参与人员
	  参数 int  id 会议id
	  返回 bool
	*/

	public function get_conference_member($id){
		$list=M('conference_member')->where('conference_id=%d',$id)->getField('guid',true);
		return $list;		
	}

	/*获得是否为当前会议与会人员
	  参数 int  id 会议id
	  参数 string  open_id 用户的open_id值
	  返回 bool
	*/

	public function isconference_member($id,$open_id){
		$list=M('conference_member')->where('conference_id=%d',$id)->getField('open_id',true);
		if(in_array($open_id,$list)) return true;
	}

	/*获得是否签过到
	  参数 int  id 会议id
	  参数 string  open_id 用户的open_id值
	  返回 bool
	*/

	public function issign($id,$open_id){
		if(M('conference_sign_member')->where('conference_id=%d and open_id="%s"',$id,$open_id)->find()) return true;
	}








}