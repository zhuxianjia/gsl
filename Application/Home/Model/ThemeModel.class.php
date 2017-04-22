<?php
namespace Home\Model;
class ThemeModel{//栏目类

	/**
	 * @DateTime 2017-01-04T13:51:25+0800
	 * @param    [array]                   $list [栏目的一维数组]
	 * @return   [array]                         [栏目信息]
	 */
	public function theme_list($list){
		$temp=M('get_admin_theme')->field('theme_id as id,theme,logo,link,nick')->select();
		$info=array_column($temp, 'id');
		$length=count($list);
        for($i=0;$i<$length;$i++) {
        	$key=array_search($list[$i], $info);
        	$order[$i]=$temp[$key];
        }
        return $order;
    }

    /**
     * @DateTime 2017-01-11T20:11:11+0800
     * @param    [array]                   $condition [条件]
     * @param    integer                  $page      [页码]
     * @param    integer                  $number    [数量]
     * @param    integer                  $key    [缩略图开关]
     * @return   [array]                              [文章数组]
     */
    public function article_list($User,$condition,$page=1,$number=10,$key=2,$user_id){
		$data=$User->where($condition)->page($page,$number)->select();
		// D('portal')->article_update();
		for($i=0;$i<count($data);$i++){
			$data[$i]['photo_photo']=M('photo')->where('article_id=%d',$data[$i]['id'])->getField('photo');
			$data[$i]['photo_compress_photo']=M('photo')->where('article_id=%d',$data[$i]['id'])->getField('compress_photo');
			$data[$i]['theme']=M('theme')->where('id=%d',$data[$i]['theme_id'])->getField('theme');
			if($key==1){
				$g=$data[$i]['photo'];
				$g2=$data[$i]['photo2'];
				$g3=$data[$i]['photo3'];
				$t=$data[$i]['photo_photo'];
			}
			else{
				$g=$data[$i]['compress_photo'];
				if(!$data[$i]['compress_photo']&&$data[$i]['photo']) $g=$data[$i]['photo'];//处理错误文件导致无法压缩图片问题
				$g2=$data[$i]['compress_photo2'];
				$g3=$data[$i]['compress_photo3'];
				$t=$data[$i]['photo_compress_photo'];
			}
	    	$order[]=array('id'=>$data[$i]['id'],'title'=>$data[$i]['title'],'author'=>$data[$i]['author'],'photo'=>$g,'photo2'=>$g2,'photo3'=>$g3,'photo_photo'=>$t,'photo_text'=>html_entity_decode($data[$i]['photo_text']),'theme'=>$data[$i]['theme'],'function'=>$data[$i]['function'],'link'=>$data[$i]['link'],'in_time'=>date('m-d H:i',$data[$i]['in_time']));
	    	$order[$i]['comment_count']=count(M('comment')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
		    	$order[$i]['like_count']=count(M('article_like')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
		    	$order[$i]['read_count']=count(M('user_article')->where('article_id=%d',$data[$i]['id'])->getField('id',true));
	    	$order[$i]['value']=M('article_tag_name')->where('article_id=%d',$data[$i]['id'])->field('tag,tag_id')->select();
	    	$data[$i]['text']=html_entity_decode($data[$i]['text']);
	    	preg_match('/<(iframe)/', $data[$i]['text'], $match);
			if($match[0]) $order[$i]['isvideo']=1;
			preg_match("/附件 <a href=/",$data[$i]['text'],$vmatch);
			if($vmatch[0]) $order[$i]['isattachment']=1;
			unset($data[$i]['text']);
			if($user_id){
				if(M('user_article')->where('article_id=%d and user_id=%d',$order[$i]['id'],$user_id)->find()) $order[$i]['isread']=1;
			}

			if(D('portal')->isreceipt($order[$i]['id'])) $order[$i]['isreceipt']=1;

            if(D('portal')->isvote($order[$i]['id'])) $order[$i]['isvote']=1;

			}
		return $order;
	}

	/**
	 * @DateTime 2017-01-16T00:25:10+0800
	 * @param    [array]                   $list [用户id的一维数组]
	 * @param    integer                  $page   [页码]
	 * @param    integer                  $number [展示数量]
	 * @return   [array]                         [用户相关数据]
	 */
	public function user_info($list,$page=1,$number=10){
		$list=array_unique($list);//去重  投票为多选
		$condition['user_id']=['in',$list];
		$data=M('user_group')->where($condition)->page($page,$number)->select();
		for($i=0;$i<count($data);$i++){
			$info=M('user')->where('id=%d',$data[$i]['user_id'])->find();
			$data[$i]['avatar']=$info['avatar'];
			$data[$i]['mobile']=$info['mobile'];
		}
		return $data;
	}


}