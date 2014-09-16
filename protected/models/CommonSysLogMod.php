<?php
/**
 * Coypright (C) 2012 Tuniu All rights reserved
 * Author: chenjinlong
 * Date: 9/26/12
 * Time: 5:39 PM
 * Description: CommonSystemLogMod.php
 */
Yii::import('application.dal.dao.CommonSysLog');

class CommonSysLogMod
{
    private static function create($inParams)
    {
        $commonSystemLog = new CommonSysLog;
        $path = $inParams['path']?$inParams['path']:$inParams['path']='';
        $title = $inParams['title']?$inParams['title']:$inParams['title']=0;
        $category = $inParams['category']?$inParams['category']:$inParams['category']=0;
        $author = $inParams['author']?$inParams['author']:$inParams['author']='';
        $int1 = $inParams['int_1']?$inParams['int_1']:$inParams['int_1']=0;
        $int2 = $inParams['int_2']?$inParams['int_2']:$inParams['int_2']=0;
        $char1 = $inParams['char_1']?$inParams['char_1']:$inParams['char_1']='';
        $char2 = $inParams['char_2']?$inParams['char_2']:$inParams['char_2']='';
        $char3 = $inParams['char_3']?$inParams['char_3']:$inParams['char_3']='';
        $misc = $inParams['misc']?$inParams['misc']:$inParams['misc']='';
        $execResult = $commonSystemLog->createCommonSystemLog($inParams);
        return $execResult;
    }

    public static function read($condParams,$start=0,$limit=30)
    {
        $commonSystemLog = new CommonSysLog;
        $title = $condParams['title'];
        $category = $condParams['category'];
        $author = $condParams['author'];
        $int1 = $condParams['int_1']; //索引1
        $int2 = $condParams['int_2']; //索引2
        $queryRows = $commonSystemLog->readCommonSystemLog($condParams,$start,$limit);
        return $queryRows;
    }

    public static function log($path,$title,$type,$author,$int1,$int2,$char1,$char2='',$char3='',$misc='')
    {
        $paramArray = array(
            'path' => $path, //varchar 255
            'title' => $title, //varchar 255
            'category' => $type, //tinyint
            'author' => $author, //varchar 255
            'int_1' => $int1, //int
            'int_2' => $int2, //int
            'char_1' => $char1, //varchar 255
            'char_2' => $char2, //varchar 2500
            'char_3' => $char3, //varchar 5000
            'misc' => $misc, //varchar 255
        );
        self::create($paramArray);
    }

}