<?php
namespace Home\Model;
class VoteModel{//投票类
	/**
	 * @DateTime 2017-01-12T14:28:55+0800
	 * @param    [int]                   $article_id [文章id]
	 * @param    [int]                   $user_id [用户id]
	 * @return   [array]                            [投票详情]
	 */
	public function vote_info($article_id,$user_id){
		$vote_id=M('vote')->where('article_id=%d',$article_id)->getField('id',true);
		foreach ($vote_id as $vkey => $vvalue) {
			$condition=['vote_id'=>$vvalue];
			$data=M('vote_question')->where($condition)->field('id as question_id,title as text,is_single_choice')->select();
			$length=count($data);
			for($i=0;$i<$length;$i++){
				$condition=['question_id'=>$data[$i]['question_id']];
				$data[$i]['children']=M('vote_question_choice')->where($condition)->field('id,text')->select();
	 			foreach ($data[$i]['children'] as $key => $value) {
					$conditions['choice_id']=$value['id'];
					$user_list=M('vote_select')->where($conditions)->getField('user_id',true);
					$data[$i]['children'][$key]['count']=count($user_list);
					if(in_array($user_id, $user_list)) $data[$i]['children'][$key]['isselect']=true;
					else $data[$i]['children'][$key]['isselect']=false;
				}
			}
			$order[]=['id'=>$vvalue,'children'=>$data];
		}
		return $order;
	}

	public function vote_modify_info($article_id){
		$vote_id=M('vote')->where('article_id=%d',$article_id)->getField('id',true);
		foreach ($vote_id as $vkey => $vvalue) {
			$condition=['vote_id'=>$vvalue];
			$data[$vkey]=M('vote_question')->where($condition)->field('id as question_id,title as text,is_single_choice')->select();
			$length=count($data[$vkey]);
			for($i=0;$i<$length;$i++){
				$condition=['question_id'=>$data[$vkey][$i]['question_id']];
				$data[$vkey][$i]['children']=M('vote_question_choice')->where($condition)->field('id,text')->select();
			}
		}
		return $data;
	}
	/**
	 * @DateTime 2017-01-14T10:02:44+0800
	 * @param    [int]                   $article_id [文章id]
	 * @param    [array]                   $question   [投票数组]                              
	 */
	public function vote_publish($article_id,$question){
		$length=count($question);
		for($i=0;$i<$length;$i++){
			$vote=['article_id'=>$article_id,'time'=>time()];
			$vote_id=M('vote')->add($vote);
			foreach ($question[$i] as $key => $value) {
				$vote_question=['title'=>$value['text'],'is_single_choice'=>$value['is_single_choice'],'vote_id'=>$vote_id];
				$question_id=M('vote_question')->add($vote_question);
				$vote_question_choice=[];
				foreach ($value['children'] as $ckey => $cvalue) {
					$vote_question_choice[]=['question_id'=>$question_id,'text'=>$cvalue['text']];
				}
				$result=M('vote_question_choice')->addAll($vote_question_choice);
				if($result===false) break;
			}
		}
		if($result!==false) return true;
    }



	// /**
	//  * @DateTime 2017-01-12T14:32:09+0800
	//  * @param    [int]                   $article_id [文章id]
	//  * @return   [int]                               [文章对应投票id]
	//  */
	// public function vote_article($article_id){
	// 	$titleid=M('vote_article')->where('article_id=%d',$article_id)->getField('titleid');
	// 	if($titleid) return $titleid;
	// }

	// /**
	//  * @DateTime 2017-01-12T14:33:21+0800
	//  * @param    [int]                   $titleid [投票id]
	//  * @return   [array]                               [用户选择]
	//  */
	// public function user_select($titleid,$user_id){
	// 	$data=M('vote')->where('titleid=%d and user_id=%d',$titleid,$user_id)->field('id')->select();
	// 	if(!$data) $data=[];
	// 	return $data;
	// }

	// /**
	//  * @DateTime 2017-01-12T14:50:53+0800
	//  * @param    [int]                   $id     [选项id]
	//  * @param    [int]                   $limit [显示数量]
	//  * @return   [array]                           [数组]
	//  */
	// public function choice_select($id,$limit){
	// 	$condition=['id'=>['in',$id]];
	// 	$data=M('vote_select')->where($condition)->limit($limit)->field('user_id,time')->select();
	// 	$length=count($data);
	// 	for($i=0;$i<$length;$i++){
	// 		$info=M('user')->where('id=%d',$data[$i]['user_id'])->find();
	// 		$data[$i]['name']=$info['nick'];
	// 		$data[$i]['avatar']=$info['avatar'];
	// 	}
	// 	if(!$data) $data=[];
	// 	return $data;
	// }
}