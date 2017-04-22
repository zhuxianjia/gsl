<?php
namespace Home\Controller;
use Think\Controller;
class VoteController extends Controller {

    Public function VoteInfo(){
        $user_id=session('user_id');
        $article_id=I('id');
        if(!D('portal')->check_id(M('article'),$article_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            $data=D('vote')->vote_info($article_id,$user_id);
            if(!$data) $data=[];
            $arr=['state'=>'0','order'=>$data];
        }            
        $this->ajaxreturn($arr); 
    }

    public function VoteModify(){
        $user_id=session('adminuser_id');
        $vote_id=I('id');
        $question=I('question');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if(!D('portal')->check_id(M('vote'),$vote_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            $article_id=M('vote')->where('id=%d',$vote_id)->getField('article_id');
            $result=(M('vote')->where('id=%d',$vote_id)->delete()!==false);
            if($question) $result=$result&&D('vote')->vote_publish($article_id,$question);
            if($result) $arr=['state'=>'0','detail'=>'操作成功！'];
            else $arr=['state'=>'10001','detail'=>'操作失败！'];
        }
        $this->ajaxreturn($arr); 
    }

    public function ReceiptModify(){
        $user_id=session('adminuser_id');
        $receipt_title=I('receipt_title');
        $receipt=I('receipt');
        $article_id=I('id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if(!D('portal')->check_id(M('article'),$article_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{
            M()->startTrans();
            $article_data['receipt_title']=$receipt_title;
            $result=(M('article')->where('id=%d',$article_id)->save($article_data)!==false);

            M('article_receipt')->where('article_id=%d',$article_id)->delete();
            foreach ($receipt as $key => $value) {
                $receipt_data[]=['name'=>$value,'article_id'=>$article_id];
            }
            $result=$result&&(M('article_receipt')->addALL($receipt_data)!==false);

            if($result) {
                M()->commit();
                $arr=['state'=>'0','detail'=>'操作成功！'];
            }
            else {
                M()->rollback();
                $arr=['state'=>'10001','detail'=>'操作失败！'];
            }
        }
        $this->ajaxreturn($arr);
    }

    public function VoteCount(){
        $user_id=session('adminuser_id');
        $article_id=I('id');
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else if(!D('portal')->check_id(M('article'),$article_id,$condition)) $arr=['state'=>'10003','detail'=>'参数错误！'];
        else{

        }
        $this->ajaxreturn($arr); 
    }

    public function Vote(){
        $user_id=session('user_id');
        $select=I('select');
        // $select=[['question_id'=>85,'choice_id'=>[204]],['question_id'=>86,'choice_id'=>[206]]];
        if(!$user_id) $arr=['state'=>'10000','detail'=>'未登录！'];
        else {
            foreach ($select as $key => $value) {
                $is_single_choice=M('vote_question')->where('id=%d',$value['question_id'])->getField('is_single_choice');
                if(M('vote_select')->where('question_id=%d and user_id=%d',$value['question_id'],$user_id)->find()){
                    $arr=['state'=>'20007','detail'=>'请勿重复投票！'];
                     $this->ajaxreturn($arr); 
                } 
                else if($is_single_choice==1&&count($value['choice_id'])>1)  {
                    $arr=['state'=>'20008','detail'=>'该选项为单选！'];
                     $this->ajaxreturn($arr);   
                }
                else if(!D('portal')->check_id(M('vote_question_choice'),$value['choice_id'],$condition)){
                    $arr=['state'=>'10003','detail'=>'参数错误！'];
                     $this->ajaxreturn($arr); 
                } 
                else {
                    foreach ($value['choice_id']as $ckey => $cvalue) {
                        $data[]=['user_id'=>$user_id,'choice_id'=>$cvalue,'question_id'=>$value['question_id']];
                    }
                }
            }
            if(M('vote_select')->addALL($data)) $arr=['state'=>'0','detail'=>'投票成功'];
            else $arr=['state'=>'10001','detail'=>'投票失败'];
        }
        $this->ajaxreturn($arr); 
    }

    public function a(){
        $question=[[['text'=>'1.请输入项目标题','is_single_choice'=>1,'children'=>[['text'=>'方大同热特热特'],['text'=>'热特热 人'],['text'=>'他热特特']]],['text'=>'1.请输入项目标题','is_single_choice'=>1,'children'=>[['text'=>'方大同热特热特'],['text'=>'热特热 人'],['text'=>'他热特特']]]]];
        $question=D('vote')->vote_modify_info(252);
        $this->ajaxReturn($question);
    }



}