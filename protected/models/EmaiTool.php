<?php
class EmaiTool{


	public static function sendEmail($params)
	{
        GLOBAL $emailFlag;
        $info = array();
        require_once dirname(__FILE__).'/../extensions/phpmailer/vendors/class.phpmailer.php';
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $body = $params;
        $mail->CharSet = 'UTF-8';
		$mail->Host       = "mail.tuniu.com";
		$mail->Port       = 25;
		$mail->Username   = "wuhuanhong";
        $mail->SetFrom('wuhuanhong@tuniu.com', "吴焕红");
        $mail->AddReplyTo("wuhuanhong@tuniu.com","吴焕红");
        $mail->Subject    = "招客宝竞价通知";
        $mail->AltBody    = "@author wuhuanhong";
        $mail->MsgHTML($body);

        if ($emailFlag == _TEST_LEVEL_){
//            $address = "wanglongsheng@tuniu.com";
//            $ccAddress =  "wanglongsheng@tuniu.com";
//            $mail->AddAddress($address);
//            $mail->AddCC($ccAddress);
            $address = "fangmin2@tuniu.com";
            // $address = "p-sunhao@tuniu.com";
            $mail->AddAddress($address);
        } else if ($emailFlag == _TRUE_LEVEL_ || !$emailFlag){
//            $address = "zhuchunye@tuniu.com";
//            $address_1 = "lihaihong@tuniu.com";
//            $ccAddress = "g-project-bb@tuniu.com";
//            $mail->AddAddress($address);
//            $mail->AddAddress($address_1);
//            $mail->AddCC($ccAddress);
			$address = "fangmin2@tuniu.com";
			// $address = "p-sunhao@tuniu.com";
            $mail->AddAddress($address);
		}

        $info['path'] = dirname(__FILE__);
        $info['author'] = 'wanglongsheng';
//        $info['char1'] = 'Reciever:'.$address.$address_1;
//        $info['char2'] = 'CC:'.$ccAddress;
		$info['char1'] = 'Reciever:'.$address;
        if(!$mail->Send()){
            $info['title'] = '招客宝-竞价信息邮件推送-失败';
            CommonSysLogMod::log($info['path'],$info['title'],2,$info['author'],0,0,$mail->ErrorInfo);
            return false;
        }else{
            $info['title'] = '招客宝-竞价信息邮件推送-成功';
//            CommonSysLogMod::log($info['path'],$info['title'],2,$info['author'],0,0,$info['char1'],$info['char2']);
			CommonSysLogMod::log($info['path'],$info['title'],2,$info['author'],0,0,$info['char1']);
            return true;
        }

    }

}
?>