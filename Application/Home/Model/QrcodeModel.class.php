<?php
namespace Home\Model;
class QrcodeModel{

	/**
	 * @DateTime 2017-01-08T21:29:13+0800
	 * @param    [array]                   $User      [表]
	 * @param    [int]                   $id        [主键]
	 * @param    [array]                   $condition [条件]
	 * @return   [type]                              [description]
	 */
	public function check_id($User,$id,$condition,$str='id'){
		$allid=$User->where($condition)->getField($str,true);
		if(is_array($id)){
			if(array_intersect($id, $allid)) return true;
		}
		else{
			if(in_array($id, $allid)) return true;
		}
	}

	/**
	 * @DateTime 2017-01-08T23:36:24+0800
	 * @param    [string]                   $url [链接]
	 * @return   [array]                        
	 */
	public function httpGet($url) {
	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    $res = curl_exec($curl);
	    curl_close($curl);
	    return $res;
    }
}