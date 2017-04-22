<?php
namespace Home\Model;
class PhoneModel{//电话短信相关函数类
	/*
		函数 check_phone_exist 检查手机号码是否存在
		参数 string phone 手机号
		返回 bool 是否存在，存在则输出true
	*/
	public function check_phone_exist($phone){//检查手机号码是否存在,存在输出true
		$User=M("user");
        $condition['phone']=$phone;
        $exit=$User->where($condition)->find();
        if($exit){
			return true;
		}
		else
			return false;
    }
	/*
		函数 check_phone 检查手机号码格式是否正确
		参数 string phone 手机号
		返回 bool 格式正确为true
	*/
	public function  check_phone($phone){//检查手机号码格式
        $preg='/^(13|15|17|18)\d{9}$/';
        return preg_match($preg,$phone);
    }
	/*
	  基于第三方平台发送短信
	 */
	public function sendCodeMessage($phone,$code){
	  	$Yp_appkey=C("Yp_appkey");
	  	$Yp_qianming=C("Yp_qianming");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://yunpian.com/v1/sms/send.json");
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, TRUE);
        curl_setopt($ch, CURLOPT_SSLVERSION , 3);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'apikey='.$Yp_appkey.'&mobile='.$phone.'&text='.$Yp_qianming.'您的验证码：'.$code.'，如非本人操作，请忽略本短信');
        $res = curl_exec( $ch );
        curl_close( $ch );
        $res=json_decode($res);
        return $res->code;
	  }
}