<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 1/14/13
 * Time: 4:54 PM
 * Description: BidMessageDao.php
 */
Yii::import('application.dal.dao.DaoModule');

class BidMessageDao extends DaoModule
{
    private $_tblName = 'bb_message';

    /**
     * 插入消息
     * @param array $params
     * @return boolean
     */
    public function insertMessage($params) {
        $result = $this->dbRW->createCommand()->insert($this->_tblName, array(
            'account_id' => $params['id'],
            'type' => $params['type'],
            'content' => $params['content'] ? $params['content'] : '',
            'amount' => floatval($params['amount']) > 0 ? floatval($params['amount']) : 0,
            'add_uid' => $params['id'],
            'add_time' => date('Y-m-d H:i:s'),
            'del_flag' => 0,
            'misc' => !empty($params['misc'])?strval($params['misc']):'',
        ));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取广告信息
     * @param array $condParams
     * @return array
     */
    public function readAdMessage($condParams) {
        if(empty($condParams['accountId'])){
            return array();
        }
        $paramsMapSegment = array();
        $condSqlSegment .= ' AND account_id=:accountId AND type=:type';
        $paramsMapSegment[':accountId'] = -1;
        $paramsMapSegment[':type'] = 6;
        $adMessage = $this->dbRO->createCommand()
            ->select('account_id accountId,type,content,add_time addTime')
            ->from($this->_tblName)
            ->where('del_flag=0 AND account_id=-1 '.$condSqlSegment, $paramsMapSegment)
            ->queryRow();
        if (empty($adMessage)) {
            $this->dbRW->createCommand()->insert($this->_tblName, array(
                'account_id' => -1,
                'type' => 6,
                'add_time' => date('Y-m-d H:i:s'),
                'del_flag' => 0,
            ));
        }
        return $adMessage;
    }

    /**
     * 更新广告信息
     * @param array $condParams
     * @return boolean
     */
    public function updateAdMessage($condParams) {
        if(empty($condParams['content']) || empty($condParams['uid'])){
            return array();
        }
        $cond = 'account_id=:account_id';
        $param = array(':account_id' => -1);
        $result = $this->dbRW->createCommand()->update($this->_tblName, array(
            'content' => $condParams['content'],
            'add_uid' => $condParams['uid'],
            'add_time' => date("Y-m-d H:i:s"),
        ), $cond, $param);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}
