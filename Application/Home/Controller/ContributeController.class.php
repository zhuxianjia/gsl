<?php
namespace Home\Controller;
use Think\Controller;
class ContributeController extends Controller {

	public function Contribute(){
        $title=I('title');
        $text=I('text');
        $user_id=session('user_id');
        // $user_id=20981;
        $theme_id=I('theme_id');
        $photo=I('photo');
        $function=3;
        // $title='test';
        // $text='testtest';
        // $theme_id=234;
        // $photo='/defaultlogo.png';
        if(!$user_id) $arr=array('state'=>'10000','detail'=>'未登录！');
        else if(!($title&&$text)) $arr=array('state'=>'10002','detail'=>'输入不能为空！');
        else if(!D('Contribute')->theme_judge($user_id,$theme_id))  $arr=array('state'=>'10003','detail'=>'参数错误！');
        else if($photo&&!img_exists($photo)) $arr=array('state'=>'10023','detail'=>'图片不存在！');
        else{
        	M()->startTrans();
            $data=array('title'=>$title,'text'=>$text,'cuser_id'=>$user_id,'photo'=>$photo,'time'=>time(),'theme_id'=>$theme_id,'function'=>$function,'state'=>1);
            if(M('article')->add($data)&&D('contribute')->save_contribute($user_id)) {
            	M()->commit();
            	$arr=array('state'=>'0','detail'=>'投稿成功！');
            }
            else{
            	M()->rollback();
            	$arr=array('state'=>'10001','detail'=>'系统异常！');
            } 
        }
        $this->ajaxreturn($arr);
    }

    public function UserTheme(){
		$user_id=session('user_id');
		$theme_id=A('Index')->get_user_blank_id($user_id);
		if(!$user_id) $arr=array('state'=>'10000','detail'=>'未登录！');
		else if(!$theme_id) $arr=array('state'=>'10065','detail'=>'请先订阅栏目！');
		else{
			$condition['theme_id']=array('in',$theme_id);
			$data=M('get_admin_theme')->where($condition)->select();
			if($data){
				$length=count($data);
				for($i=0;$i<$length;$i++){
		    		$order[]=array('id'=>$data[$i]['theme_id'],'name'=>$data[$i]['nick'].' '.$data[$i]['theme']);
		    	}
		    	$arr=array('state'=>'0','order'=>$order);
		    }
		    else $arr=array('state'=>'10064','detail'=>'暂无相应记录！');
	    }
		$this->ajaxreturn($order);
	}

	public function SectionCount(){
		$user_id=session('adminuser_id');
        $page=I('page',1,'intval');
        $number=I('number',10,'intval');
		if(!$user_id) $arr=array('state'=>'10000','detail'=>'未登录！');
		else{
			$data=M('section')->order('count desc')->page($page,$number)->select();
            if($data){
    			$length=count($data);
    			for($i=0;$i<$length;$i++){
    				$data[$i]['sum']=$data[$i]['backcount']+$data[$i]['count'];
    			}
                $arr['order']=$data;
                $arr['sum']=count(M('section')->select());
            }
            else $arr=array('state'=>'0','detail'=>'暂无相应记录！');
	    }
		$this->ajaxreturn($arr);
	}

    private function urlsafe_b64decode($string) {//url安全的base64解码
       $data = str_replace(array('-','_'),array('+','/'),$string);
       $mod4 = strlen($data) % 4;
       if ($mod4) {
           $data .= substr('====', $mod4);
       }
       return base64_decode($data);
    }

    public function photo_upload(){
		$upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     10485760 ;// 设置附件上传大x小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');//设置附件上传类型
        //$upload->savePath  =      'Uploads/'; // 设置附件上传目录// 上传文件
        $tempinfo   =   $upload->upload();
        if($tempinfo) {// 上传错误提示错误信息
            foreach($tempinfo as $key=>$file){
            	$file="/Uploads/".$file['savepath'].$file['savename'];
                $goal[]=array('file'=>$file,'name'=>$key);
            }
        }
        else{
	    	$file=I('post.file');
	    	$path="Uploads/";
	    	$max_size=10485760;
	    	if(!is_array($file)){
	            $json=json_decode(htmlspecialchars_decode($file),true);
	            if($json) $file=$json;
	            else{
	                $json=$file;
	                $file=array();
	                $file[]=$json;
	            }
	        }
	        $goal=array();
	        $i=0;
	        foreach($file as $value){
	            $path_dir=$path.date("Y-m-d")."/";//目录
	            if (!is_dir($path_dir)) mkdir($path_dir, 0777);
	            $new_file = $path_dir.uniqid("_").$i.".png";
	            $i++;
	            $binary=$this->urlsafe_b64decode($value);
	            if(file_put_contents($new_file,$binary)) $goal[]="/".$new_file;
	        }
        }
        if($goal){
        	$result['state']="0";
        	$result['order']=$goal;
        }
        else{
			$result['state']="10022";
        	$result['detail']="图片上传失败！";
        }
        $this->ajaxreturn($result);
    }

    public function AjaxTheme(){
        $user_id=session('user_id');
        if(!$user_id) $arr=array('state'=>'10000','detail'=>'未登录！');
        // else if(!$theme_id) $arr=array('state'=>'10065','detail'=>'请先订阅栏目！');
        else{
            if(!I('section_id')) {
                $theme_id=A('Index')->get_user_blank_id($user_id);
                $condition['theme_id']=array('in',$theme_id);
            }
            else {
                $section_id=I('section_id');
                $condition['id']=$section_id;
            }
            $data=M('get_admin_theme')->where($condition)->select();
            if($data){
                $length=count($data);
                for($i=0;$i<$length;$i++){
                    $order[]=array('id'=>$data[$i]['theme_id'],'name'=>$data[$i]['theme'],'section'=>$data[$i]['nick'],'section_id'=>$data[$i]['id']);
                }
                $order=splite_array($order,'section_id','children',array('section'));
                $arr=array('state'=>'0','order'=>$order);
            }
            else $arr=array('state'=>'10064','detail'=>'暂无相应记录！');
        }
        $this->ajaxreturn($arr);
    }




}