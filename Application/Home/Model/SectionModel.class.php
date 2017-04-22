<?php
namespace Home\Model;
class SectionModel{

	public function get_user_section($user_id) { 
        $data=M('user_section')->where('user_id=%d',$user_id)->select();
        for($i=0;$i<count($data);$i++){
        	$t.=' '.$data[$i]['name'];
        }
        if(!$t) $t='';
        return $t; 
     } 

     public function get_admin_name($id) {
     	return M('admin')->where('id=%d',$id)->getField('nick');
     }


}