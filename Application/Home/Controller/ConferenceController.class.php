<?php
namespace Home\Controller;
use Think\Controller;
class ConferenceController extends Controller {

	public function sign_in(){
		$open_id=I('post.open_id');
		$conference_id=I('conference_id');//会议id
		$token=D('token')->get_admin_token(C('client_ID'),C('clientsecret'));
	    $access_token=$token['access_token'];
	    if(!$access_token)  $arr=array('ret'=>-2,'msg'=>'access_token 无效！');
	    else if(!D('conference')->check_conference_id($conference_id)) $arr=array('ret'=>300,'msg'=>'会议id不存在！');
		else{
			// $open_id=I('open_id');
	    	// $open_id='956ffe296b2bb8355b6113010b5adea9';
		    if(!$open_id)  $arr=array('ret'=>200,'msg'=>'用户不存在！');
		    else{
		    	$conference_info=M('conference')->where('id=%d',$conference_id)->find();
		    	$order['conference_info']=array('title'=>$conference_info['title'],'type'=>$conference_info['type'],'content'=>$conference_info['content'],'time'=>$conference_info['time']);
		    	if(!D('conference')->isconference_member($conference_id,$open_id)) $arr=array('ret'=>301,'data'=>$order);
		    	else{
		    		$userinfo=D('token')->get_info($access_token,$open_id);
		    		$orginfo=D('token')->get_blocs($access_token,$open_id);
		    		$orgs=$orginfo->orgs;
		    		$length=count($orgs);
		    		for($i=0;$i<$length;$i++){
		    			if($orgs[$i]->id==$conference_info['organization_id']){
		    				$identity=implode(array_column($orgs[$i]->identity, 'Name'), ',');
		    				$organization=$orgs[$i]->name;
                            $group_id=implode(array_column($orgs[$i]->groups,'group_id'),',');
                            $group_name=implode(array_column($orgs[$i]->groups,'group_name'),',');
		    			}
		    		}
		    		$photo=$userinfo->photo;
                    if(!$photo) $photo='/defaultlogo.png';
                    else{
                        $path_dir='Uploads/'.date("Y-m-d");//目录
                        $new_file = uniqid("_").".jpg";
                        $path=getImage($photo, $path_dir,$new_file,$type=0);
                        $photo='/'.$path["save_path"];
                    }
		    		$data=array('open_id'=>$open_id,'time'=>time(),'conference_id'=>$conference_id,'photo'=>$photo,'name'=>$userinfo->name,'gender'=>$userinfo->gender,'mobile'=>$userinfo->mobile,'organization_id'=>$conference_info['organization_id'],'identity'=>$identity,'organization'=>$organization,'group_id'=>$group_id,'group_name'=>$group_name);
		    		$order['user_info']=array('name'=>$userinfo->name,'identity'=>$identity,'gender'=>$userinfo->gender,'photo'=>$photo,'organization'=>$organization);
		    		if(D('conference')->issign($conference_id,$open_id)){
		    			$number=M('conference_sign_member')->where('open_id="%s"',$open_id)->getField('number');
                        M('conference_sign_member')->where('open_id="%s"',$open_id)->save($data);
		    			$order['user_info']['number']=$number;
		    			$arr=array('ret'=>0,'data'=>$order);
		    		} 
		    		else{
		    			$number=count(M('conference_sign_member')->where('conference_id=%d',$conference_id)->select());
		    			$data['number']=$number+1;
		    			$order['user_info']['number']=$number+1;
		    			if(M('conference_sign_member')->add($data)) $arr=array('ret'=>0,'data'=>$order);
		    			else $arr=array('ret'=>302,'msg'=>'签到失败！');
		    		}
		    		
		    	}
		    }
		}
		$this->ajaxreturn($arr);
	}

	public function conference_sign_list(){
		$conference_id=I('conference_id');
		if(!D('conference')->check_conference_id($conference_id)) $arr=array('ret'=>300,'msg'=>'会议id不存在！');
		else{
			$data['sign_list']=M('conference_sign_member')->where('conference_id=%d',$conference_id)->order('time desc')->select();
			$data['conference_info']=M('conference')->field('id,title,content,time,organization_id as org_id,type')->where('id=%d',$conference_id)->find();
			$data['conference_info']['count']=count(M('conference_sign_member')->where('conference_id=%d',$conference_id)->select());
			$data['conference_info']['sum']=count(M('conference_member')->where('conference_id=%d',$conference_id)->select());
			$arr=array('ret'=>0,'msg'=>$data);
		}
		$this->ajaxreturn($arr);
	}

	public function save_conference_info(){
		$title=I('title');
		$content=I('content');
		$type=I('type');//会议类型
		$member=I('member');//用户的guid串，','隔开
		$time=I('time');
		$organization_id=I('org_id');
        // $title='杭州市工商联（总商会）十三届二次执委（理事）会议';
        // $content='会议简介';
        // $type='会议';
        // $time='1484690400';
        // $member='76eca6a44374cac1c4ff8f8813f65373';
        // $organization_id=9;
		if(!($title&&$content&&$type&&$member&&$organization_id)) $arr=array('ret'=>304,'msg'=>'请补全会议数据！');
		else{
	        $data=array('title'=>$title,'content'=>$content,'type'=>$type,'time'=>$time,'organization_id'=>$organization_id);
	        $id=M('conference')->add($data);
	        if(!$id) $arr=array('ret'=>303,'msg'=>'会议数据写入失败！');
	        else{
	            $list=explode(',', $member);
	            $length=count($list);
	            for($i=0;$i<$length;$i++){
	            	$member_data[]=array('conference_id'=>$id,'open_id'=>$list[$i]);
	            }
	            $order=array('conference_id'=>$id);
	            if(M('conference_member')->addALL($member_data)) $arr=array('ret'=>0,'data'=>$order);
	            else $arr=array('ret'=>303,'msg'=>'会议数据写入失败！');
	        }
	    }
		$this->ajaxreturn($arr);
	}

	public function get_qrcode_url(){
		$conference_id=I('conference_id');
		if(!D('conference')->check_conference_id($conference_id)) $arr=array('ret'=>300,'msg'=>'会议id不存在！');
		else{
	        $data=array('url'=>($_SERVER['HTTP_HOST'].'/qiandao/auth.html?conference_id='.$conference_id));
	        $arr=array('ret'=>0,'data'=>$data);
	    }
		$this->ajaxreturn($arr);
	}

	public function get_list_url(){
		$conference_id=I('conference_id');
		if(!D('conference')->check_conference_id($conference_id)) $arr=array('ret'=>300,'msg'=>'会议id不存在！');
		else{
	        $data=array('url'=>($_SERVER['HTTP_HOST'].'/qiandao/web_checkpage.html?conference_id='.$conference_id));
	        $arr=array('ret'=>0,'data'=>$data);
	    }
		$this->ajaxreturn($arr);
	}

	public function export(){
        $conference_id=I('conference_id');
        if(!D('conference')->check_conference_id($conference_id)){
            $arr=array('ret'=>300,'msg'=>'会议id不存在！');
            $this->ajaxreturn($arr);
        }
        else{
    		$data = M('conference_sign_member')->where('conference_id=%d',$conference_id)->order('time')->field('number,name,photo,gender,mobile,time,identity,group_name,organization')->select();
            $info=M('conference')->where('id=%d',$conference_id)->find();
            $length=count($data);
            for ($i = 0;$i<$length;$i++) {
                if($data[$i]['gender']=='0') $data[$i]['gender']='男';
                else if($data[$i]['gender']==1) $data[$i]['gender']='女';
                else  $data[$i]['gender']='未定义';
                if($data[$i]['mobile']) $data[$i]['mobile']=telecode_show($data[$i]['mobile']);
                $data[$i]['time']=date('Y-m-d H:i',$data[$i]['time']);
            }
            //引入PHPExcel库文件（路径根据自己情况）
            include 'PHPExcel/Classes/PHPExcel.php';
            //创建对象
            $excel = new \PHPExcel();
            /*实例化excel图片处理类*/
            // $objDrawing = new \PHPExcel_Worksheet_Drawing();
            //Excel表格式,这里简略写了8列
            $letter = array('A','B','C','D','E','F','G','H','I');
            //表头数组
            $tableheader = array('签到次序','姓名','头像','性别','手机号','签到时间','职位','部门','单位');
        
            //填充表头信息
            for($i = 0;$i < count($tableheader);$i++) {
                $excel->getActiveSheet()->setCellValue("$letter[$i]5","$tableheader[$i]");
            }
            //设置选定sheet表名
            $excel->getActiveSheet()->setTitle($info['title']);
            //设置字体样式
            $excel->getActiveSheet()->getStyle('A1')->getFont()->setName('Arial')->setSize(25)->setBold(true);//////->setUnderline(true);/////->getColor()->setARGB('FFFF0000');///->setBold(true);
            //合并单元格 给单元格赋值(数值，字符串，公式)
            $excel->getActiveSheet()->mergeCells('A1:I3')->setCellValue('A1', $info['title'].'签到列表');
            ///////$excel->getActiveSheet()->mergeCells('A4:D4')->setCellValue('A4', "=SUM(E4:F4)");

            $date_now  = date("Y-m-d H:i");
            $excel->getActiveSheet()->mergeCells('A4:I4')->setCellValue('A4', "生成日期：".$date_now." "."　");

            //锁定窗格
            $excel->getActiveSheet()->freezePane('A6');

            //大边框样式 边框加粗
            $lineBORDER = array(
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THICK,
                        'color' => array('argb' => '000000'),
                    ),
                ),
            );
            //表头样式
            $head = array(
                'font'    => array(
                   'bold'      => true
                    ),
                'alignment' => array(
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
                        ),
                
            );
            //标题样式
            $title = array(
                'font'    => array(
                    'bold'      => true
                ),
            );
            //居中对齐
            $CENTER = array(
                'alignment' => array(
                        'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
                    ),
            );
            //靠右对齐
            $RIGHT = array(
                'alignment' => array(
                        'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                        'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
                    ),
            );
            //细边框样式
            $linestyle = array(
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array('argb' => 'FF000000'),
                    ),
                ),
            );


            $excel->getActiveSheet()->getStyle('A1:I3')->applyFromArray($head);///->getAlignment()->getHorizontal('');///->getBorders()->getTop()->setBorderStyle('');
            //->setWrapText(true);自动换行
            $excel->getActiveSheet()->getStyle('A4:I4')->applyFromArray($RIGHT); 
            $excel->getActiveSheet()->getStyle('A5:I5')->applyFromArray($title); 

            //填充色
            /////$excel->getActiveSheet()->getStyle('A1')->getFill()->getStartColor()->setARGB('FFFF0000');/

            //填充表格信息
            /*填充表格内容*/
            for ($i = 6;$i <= $length + 5;$i++) {//$i从初始写入数据行
                $j = 0;
                foreach ($data[$i - 6] as $key=>$value) {
                    if ($key == 'photo') {
                        /*实例化插入图片类*/
                        $objDrawing = new \PHPExcel_Worksheet_Drawing();
                        /*设置图片路径 切记：只能是本地图片*/
                        $objDrawing->setPath('.'.$data[$i- 6][$key]);
                        $objDrawing->setHeight(80);//照片高度
                        $objDrawing->setWidth(60); //照片宽度
                        /*设置图片要插入的单元格*/
                        $objDrawing->setCoordinates("$letter[$j]$i");
                        /*设置图片所在单元格的格式*/
                        $objDrawing->setOffsetX(30);//偏移距离
                        $objDrawing->setOffsetY(10);
                        // $objDrawing->setRotation(20);
                        $objDrawing->getShadow()->setVisible(true);
                        $objDrawing->getShadow()->setDirection(50);
                        $objDrawing->setWorksheet($excel->getActiveSheet());
                    }
                    else{
                        $excel->getActiveSheet()->setCellValue("$letter[$j]$i","$value");
                        $excel->getActiveSheet()->getStyle("$letter[$j]$i")->applyFromArray($linestyle);
                        $excel->getActiveSheet()->getRowDimension($i)->setRowHeight(60);
                        
                    }
                    $j++;
                }
            }
           
            //设置列宽度
            foreach ($letter as $value) {
                if(in_array($value,array('A','D'))) $width=10;
                else if(in_array($value,array('G','H','I'))) $width=15;
                else $width=18;
                $excel->getActiveSheet()->getColumnDimension($value)->setWidth($width);
            }
            $row_count=$length+5;//加入表头、注释栏

            $excel->getActiveSheet()->getStyle('A5:I'.$row_count)->applyFromArray($CENTER);
            $excel->getActiveSheet()->getStyle('A1:I3')->applyFromArray($lineBORDER);
            $excel->getActiveSheet()->getStyle('A4:I4')->applyFromArray($lineBORDER);
            $excel->getActiveSheet()->getStyle('A5:I5')->applyFromArray($lineBORDER);
            $excel->getActiveSheet()->getStyle('A6:I'.$row_count)->applyFromArray($lineBORDER);
            $excel->getActiveSheet()->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
            //设置自动筛选
            $excel->getActiveSheet()->setAutoFilter('A5:I'.$row_count);
            //设置自动换行
            $excel->getActiveSheet()->getStyle('I6:I'.$row_count)->getAlignment()->setWrapText(true);
            $excel->getActiveSheet()->getStyle('A1:I3')->getAlignment()->setWrapText(true);
            //创建Excel输入对象
            $write = new \PHPExcel_Writer_Excel5($excel);

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename='.$info['title'].date('Y月m月d日',time()).'签到记录.xls');
            header("Content-Transfer-Encoding:binary");
            $write->save('php://output');
            exit;
        }
	}

    public function reset_sign_list(){
        $conference_id=I('conference_id');
        if(!D('conference')->check_conference_id($conference_id)) $arr=array('ret'=>300,'msg'=>'会议id不存在！');
        else{
            if(M('conference_sign_member')->where('conference_id=%d',$conference_id)->delete()) $arr=array('ret'=>0,'msg'=>'删除成功！');
            else $arr=array('ret'=>305,'msg'=>'会议暂无相关签到记录！');
        }
        $this->ajaxreturn($arr);
    }








}