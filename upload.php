<?php
session_start();
// if(is_uploaded_file($_FILES['file1']['tmp_name'])){
// 	//unlink($_FILES['file1']['tmp_name']);
// 	move_uploaded_file($_FILES['file1']['tmp_name'], "./{$_FILES['file1']['name']}");
// }
	if(is_uploaded_file($_FILES['file']['tmp_name'])){
   		$path_dir="Uploads/".date("Y-m-d")."/";//目录
        if (!is_dir($path_dir)) mkdir($path_dir, 0777);
        $ext= pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION);
        $new_file = $path_dir.uniqid("_").".".$ext;
        move_uploaded_file($_FILES['file']['tmp_name'], $new_file);
        // $info[]=array('file'=>"/".$new_file,'name'=>'file','filename'=>$_FILES["file"]["name"]);
        $info=['state'=>0,'order'=>[['file'=>"/".$new_file,'name'=>'file','filename'=>$_FILES["file"]["name"]]]];
        echo json_encode($info);
    }


    // var_dump($_FILES)  array(1) { ["file"]=> array(5) { ["name"]=> string(12) "Wildlife.wmv" ["type"]=> string(14) "video/x-ms-wmv" ["tmp_name"]=> string(14) "/tmp/phpODrxjP" ["error"]=> int(0) ["size"]=> int(26246026) } } 

?>
