<?php
/**
 * Copyright © 2013 Tuniu Inc. All rights reserved.
 * 
 * @author chenjinlong
 * @date 14-1-16
 * @time 下午9:51
 * @description AdPositionMod.php
 */
Yii::import('application.modules.bidmanage.dal.dao.date.AdPositionDao');

class AdPositionMod 
{
    private $_adPositionDao;

    function __construct()
    {
        $this->_adPositionDao = new AdPositionDao();
    }

    /**
     * 查询出价广告位类型配置信息（指定广告类型）
     *
     * @author chenjinlong 20140116
     * @param $showDateId
     * @param $adKey
     * @return array
     */
    public function queryAdPositionSpecific($param)
    {
        return $this->_adPositionDao->queryAdPositionByShowDateId($param);
    }

}
 