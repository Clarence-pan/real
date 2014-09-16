<?php

class ReleaseEmailMod{
    
    
    public function genEmaiBody($productArray){
		$body = 'Hi,All<br/><br/><h2>'.'招客宝推广信息</h2>';
		$body.= '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;"><tr><td style="border:#000000 1px solid;">供应商编号</td>';
		$body.= '<td style="border:#000000 1px solid;">招客宝名称</td>';
		$body.= '<td style="border:#000000 1px solid;">产品编号</td>';
		$body.= '<td style="border:#000000 1px solid;">产品名称</td>';
                $body.= '<td style="border:#000000 1px solid;">审核状态</td>';
		$body.= '<td style="border:#000000 1px solid;">推广日期</td>';
		$body.= '<td style="border:#000000 1px solid;">推广位置</td>';
		$body.= '<td style="border:#000000 1px solid;">竞价金额</td>';
		$body.= '<td style="border:#000000 1px solid;">产品线&nbsp</td></tr>';
		if(count($productArray) > 0){
		    foreach($productArray as $key=>$value){
		        $body.= '<tr><td style="border:#000000 1px solid;">';
		        $body.= $value['agencyId'];
		        $body.= '</td><td style="border:#000000 1px solid;">';
		        $body.= $value['accountName'];
		        $body.= '</td><td style="border:#000000 1px solid;">';
		        $body.= $value['productId'];
		        $body.= '</td><td style="border:#000000 1px solid;">';
		        $body.= $value['productName'];
                        $body.= '</td><td style="border:#000000 1px solid;">';
                        $body.= $value['reviewState'];
		        $body.= '</td><td style="border:#000000 1px solid;">';
		        $body.= $value['showStartDate']."～".$value['showEndDate'];
		        $body.= '</td><td style="border:#000000 1px solid;">';
		        // 如果是非搜索页，则设置城市名称
		        if ('search_complex' != $value['adKey']) {
		        $body.= $value['startCityName'];
		        $body.= '出发';
		        } else {
		        	// 搜索页设置关键词
		        	$body.= $value['search_keyword'];
		        }
		        if($value['adKey'] != 'index_chosen' && 'search_complex' != $value['adKey']) {
		           $body.= $value['classification_name'];
		        }
		        $body.= $value['adKeyValue'];
		        $body.= '</td><td style="border:#000000 1px solid;">';
		        $body.= $value['bidPrice'];
		        $body.= '</td><td style="border:#000000 1px solid;">';
		        if(!empty($value['line_fullname'])){
		        	$body.= $value['line_fullname'];
		        }
		        $body.= '</td></tr>';
		    }
		    $body.='</table>';
		} else {
		    $body.= '</table>';
		    $body.='</br></br><b>'.'没有供应商在招客宝出价！</b>';
		}
        return $body;
    }

}
    