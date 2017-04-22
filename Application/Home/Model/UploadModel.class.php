<?php
namespace Home\Model;
class UploadModel{//文件上传类
	/*
		函数 upload_photo 上传图片
		参数 无
		返回 array 资源ID
	*/
	public function upload_photo(){
		$info=$this->get_base64();
		$cal=M("resources");
		$id_arr=array();
		foreach($info as $file){
			$data['url']=$file;
			$data['time']=time();
			$id=$cal->data($data)->add();
			if($id)$id_arr[]=$id;
		}
		return $id_arr;
	}
	
	/*
		函数 get_photo_resource 获取资源数组
		参数 int relevancetype 资源关联类型
			 int msgid 资源关联ID
		返回 array[string] 资源所在URL
	*/
	public function get_photo_resource($relevancetype,$msgid){
		$cal=M("resources");
		$where['relevancetype']=$relevancetype;
		$where['msgid']=$msgid;
		$where['type']=0;
		return $cal->field('url')->where($where)->select();
	}
	
	/*
		函数 set_resources 设置资源关联类型
		参数 array id 资源ID int 资源ID
			 int relevancetype 资源关联类型
			 int msgid 资源关联ID
		返回 int 状态码
	*/
	public function set_resources($id,$relevancetype,$msgid){
		$data['relevancetype']=$relevancetype;
		$data['msgid']=$msgid;
		if(is_array($id)){
			$where['id']=array('in',$id);
		}
		else{
			$where['id']=$id;
			M('resources')->where($data)->delete();
			if(M('resources')->where($where)->save($data))return 0;
		}
		if(M('resources')->where($where)->save($data))return 0;
		else return 10000;
	}

	/*
		函数 del_resources 删除资源
		参数 array id 资源ID int 资源ID
		返回 int 状态码
	*/
	public function del_resources($arr){
		if(is_array($arr)){
			$where['id']=array('in',$arr);
		}
		else{
			$where['id']=$arr;
		}
		if(M('resources')->where($where)->delete()){
			return 0;
		}
		else{
			return 10000;

		}
	}
	
	
	private function get_base64($path="Uploads/",$max_size=10485760){//获得base64文件
		$file=I('post.photo');
		if(!is_array($file)){
			$json=json_decode(htmlspecialchars_decode($file),true);
			if($json){
				$file=$json;
			}
			else{
				$json=$file;
				$file=array();
				$file[]=$json;
			}
		}
		$goal=array();
		foreach($file as $value){
			$path_dir=$path.date("Y-m-d")."/";//目录
			if (!is_dir($path_dir)) mkdir($path_dir, 0777);
			$new_file = $path_dir;
			$binary=$this->urlsafe_b64decode($value);
			if(strlen($binary)<=$max_size&&file_put_contents($new_file,$binary))
				$goal[]=$new_file;
			/*
			if(preg_match('/^(data:\s*image\/(\w+);base64,)/',$value, $result)){
				$type = $result[2];
				$path_dir=$path.date("Y-m-d")."/";//目录
				if (!is_dir($path_dir)) mkdir($path_dir, 0777);
				$new_file = $path_dir.uniqid(D('user')->unique()."_").".".$type;
				$binary=base64_decode(str_replace($result[1], '', $value));
				if($this->check_type($type)&&strlen($binary)<=$max_size&&file_put_contents($new_file,$binary))
					$goal[]=$new_file;
			}
			*/
		}
		return $goal;
	}
	
	private function check_type($type){//确定上传文件类型
		$type_arr=array('jpg', 'gif', 'png', 'jpeg');
		return in_array($type,$type_arr);
	}
	private function urlsafe_b64decode($string) {//url安全的base64解码
	   $data = str_replace(array('-','_'),array('+','/'),$string);
	   $mod4 = strlen($data) % 4;
	   if ($mod4) {
		   $data .= substr('====', $mod4);
	   }
	   return base64_decode($data);
	}
	
}