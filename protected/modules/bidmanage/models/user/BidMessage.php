<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 1/14/13
 * Time: 5:06 PM
 * Description: BidMessage.php
 */
Yii::import('application.modules.bidmanage.dal.dao.user.BidMessageDao');
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');
Yii::import('application.modules.bidmanage.dal.dao.bid.BidProductDao');

class BidMessage
{
    private $bidMessageDao;
    private $manageDao;
    private $_bidProductDao;

    function __construct() {
        $this->bidMessageDao = new BidMessageDao();
        $this->manageDao = new UserManageDao();
        $this->_bidProductDao = new BidProductDao;
    }

    /**
     * 排名变动信息添加到消息中心
     * @param array $params
     * @return array
     */
    public function insertRankMessage($params) {
        //获取用户上次登录时间
        $user = $this->manageDao->readUser($params);
        $lastLoginTime = $user['lastLoginTime'];
        //获取上次登录至今排名发生变化的竞价记录数

        $rankChangeCount = $this->_bidProductDao->readRankChangeCount($params, $lastLoginTime);

        $insertResult = true;

        if (0 != intval($rankChangeCount)) {
            $params['type'] = 4;
            $params['content'] = '有'.$rankChangeCount.'个产品排名发生了变动';
            $params['amount'] = 0;

            $insertResult = $this->bidMessageDao->insertMessage($params);
        }

        return $insertResult;
    }

    /**
     * 消息中心表记录插入[通用版]
     *
     * @author chenjinlong 20121219
     * @param $paramsArr
     * @return bool
     */
    public function insertIntoMessageCenter($paramsArr)
    {
        $insertArr = array(
            'id' => $paramsArr['account_id'],
            'type' => $paramsArr['type'],
            'content' => mysql_escape_string($paramsArr['content']),
            'amount' => $paramsArr['amount'],
            'addUid' => $paramsArr['add_uid'],
            'misc' => mysql_escape_string($paramsArr['misc']),
        );
        $execResult = $this->bidMessageDao->insertMessage($insertArr);
        if($execResult){
            return $execResult;
        }else{
            return false;
        }
    }

    /**
     * 获取获取广告信息
     * @param array $params
     * @return array
     */
    public function readAdMessage($condParams) {
        $adMessage = $this->bidMessageDao->readAdMessage($condParams);
        return $adMessage;
    }

    /**
     * 更新广告信息
     * @param array $params
     * @return
     */
    public function updateAdMessage($params) {
        $updateResult = $this->bidMessageDao->updateAdMessage($params);
        return $updateResult;
    }
}
