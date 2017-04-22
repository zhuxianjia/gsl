<?php
namespace Home\Model;
class TokenModel{//接口

    public function get_access_token($app_id,$app_secret){
        $post_data=array('grant_type'=>"client_credential",'app_id'=>$app_id,"app_secret"=>$app_secret);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, C('url')."/openapi/oauth2/app_token");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $post_data);
        $code=curl_exec($ch);
        curl_close($ch); 
        $object=json_decode($code);
        $ret=$object->ret;
        $data=$object->data;
        if($ret==0){
            $user_data['access_token']=$data->access_token;
            $user_data['expires_in']=$data->expires_in;
        }
        return $user_data;
    }
 
    public function get_admin_token($client_id,$client_secret){
        $post_data=array('grant_type'=>"client_credential",'client_id'=>$client_id,"client_secret"=>$client_secret);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,C('url')."/openapi/oauth2/admin_token");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $post_data);
        $code=curl_exec($ch);
        curl_close($ch); 
        $object=json_decode($code);
        $ret=$object->ret;
        $data=$object->data;
        if($ret==0){
            $user_data['access_token']=$data->access_token;
            $user_data['expires_in']=$data->expires_in;
        }
        return $user_data;
    }

    public function get_base_info($access_token,$open_id){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,C('url')."/openapi/user/get_base_info?access_token=".$access_token."&open_id=".$open_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $code=curl_exec($ch);
        curl_close($ch);
        $object=json_decode($code);
        $ret=$object->ret;
        $data=$object->data;
        return $data;
    }

    public function get_info($access_token,$open_id){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,C('url')."/openapi/user/get_info?access_token=".$access_token."&open_id=".$open_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $code=curl_exec($ch);
        curl_close($ch);
        $object=json_decode($code);
        $ret=$object->ret;
        $data=$object->data;
        return $data;
    }

    public function get_blocs($access_token,$open_id){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,C('url')."/openapi/user/get_blocs?access_token=".$access_token."&open_id=".$open_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $code=curl_exec($ch);
        curl_close($ch);
        $object=json_decode($code);
        $ret=$object->ret;
        $data=$object->data;
        return $data;
    }
}