<?php
/**
 * Coypright (C) 2012 Tuniu All rights reserved
 * Author: chenjinlong
 * Date: 9/28/12
 * Time: 4:56 PM
 * Description: ProfileMonitor.php
 */
class ProfileMonitor
{
    private static $_startTime;

    private static $_stopTime;

    public static function start($blockId){
        self::$_startTime[$blockId] = self::microtimeToFloat();
    }

    public static function stop($blockId){
        self::$_stopTime[$blockId] = self::microtimeToFloat();
    }

    /**
     * 以秒级单位记录指定代码块运行时间日志
     *
     * @author chenjinlong 20120929
     * @static
     * @param $blockId
     * @param $title
     * @param $author
     * @return bool
     * @throws CHttpException
     */
    public static function log($blockId, $title, $author)
    {
        return;
        if(isset(self::$_startTime[$blockId]) && isset(self::$_stopTime[$blockId])){
            $periodTime = (self::$_stopTime[$blockId]-self::$_startTime[$blockId]);
            $finalTime = round($periodTime,6);
            $loggingResult = self::recordProfileLog($blockId,$title,$author,$finalTime);
            return $loggingResult;
        }else{
            throw new CHttpException(473003,'Undefined block ID for profile monitor.');
        }
    }

    public static function recordProfileLog($subject,$title,$author,$digit)
    {
        $inParams = array(
            'subject'=>$subject,
            'title'=>$title,
            'digit'=>$digit,
            'author'=>$author,
        );
       whh_dump($inParams);
    }

    private static function microtimeToFloat(){
        list($uSec,$curUnixStamp) = explode(" ",microtime());
        return ((float)$uSec+(float)$curUnixStamp);
    }

}
