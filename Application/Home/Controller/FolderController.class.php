<?php
namespace Home\Controller;
use Think\Controller;
class FolderController extends Controller {

	public $url='http://yzz-server.780.cn/apps/api.jsmani';

	public function getfolder(){
		$newinfo="CACHE MANIFEST\n# 上一行标识此文件是一个清单文件，本行是注释\nCACHE:\n# 下面的内容都睡应用程序依赖的资源文件的URL\n";
		$data=[$this->url];
		$array=array('Index/source/js','Index/source/css','Uploads');
		foreach ($array as $key => $value) {
			$folder=list_files($value);
			if($folder) $data=array_merge($data,$folder);
		}
		foreach ($data as $key => $value) {
			// if($value!=$this->url) $value='http://'.$_SERVER['HTTP_HOST'].'/'.$value;
			if($value!=$this->url) $value='/'.$value;
			$newinfo.=$value."\n";
		}
		$newinfo.="\nNETWORK:\n*";
		$fh = fopen($newinfo, "r");
	   	$path_dir='Uploads/'.date("Y-m-d")."/";//目录
        $file = "list.php";
        $handle = fopen($file, 'w') or die("Unable to open file!");
        $oldinfo = fread($handle,filesize($file));
        if($oldinfo!=$newinfo) fwrite($handle, $newinfo);
        $arr=array('state'=>'0','order'=>'/'.$file);
		$this->ajaxreturn($arr);
	}
}