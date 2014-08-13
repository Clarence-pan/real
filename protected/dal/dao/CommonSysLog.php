<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 11/25/12
 * Time: 5:09 PM
 * Description: CommonSysLog.php
 */
Yii::import('application.dal.dao.DaoModule');

class CommonSysLog extends DaoModule
{
    private $_tblName = 'common_sys_log';

    public function createCommonSystemLog($in)
    {
        $exeResult = $this->dbRW->createCommand()->insert($this->_tblName,array(
            'path'=>$in['path'],
            'title'=>$in['title'],
            'category'=>$in['category'],
            'author'=>$in['author'],
            'int_1'=>$in['int_1'],
            'int_2'=>$in['int_2'],
            'char_1'=>$in['char_1'],
            'char_2'=>$in['char_2'],
            'char_3'=>$in['char_3'],
            'add_time'=>date('Y-m-d H:i:s'),
            'misc'=>$in['misc'],
        ));
        $tblIndexLastID = $this->dbRW->lastInsertID;
        if(!empty($tblIndexLastID)){
            return $tblIndexLastID;
        }else{
            return false;
        }
    }

    public function readCommonSystemLog($condParams,$start,$limit)
    {
        $condSqlSegment = '';
        $paramsMapSegment = array();
        if(!empty($condParams)&&is_array($condParams)){
            foreach($condParams as $key=>$value){
                $condSqlSegment .= ' AND '.$key.'=:'.$key;
                $paramsMapSegment[':'.$key] = $value;
            }
        }
        $rows = $this->dbRO->createCommand()
            ->select('id,path,title,category,author,int_1,int_2,char_1,char_2,char_3,add_time,misc')
            ->from($this->_tblName)
            ->where('id>0 '.$condSqlSegment, $paramsMapSegment)
            ->order('id DESC')
            ->limit($limit,$start)
            ->queryAll();

        return $rows;
    }

}
