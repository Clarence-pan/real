<?php
/**
 * Created by PhpStorm.
 * User: huangxun
 * Date: 14-8-4
 * Time: 下午1:37
 */
Yii::import('application.dal.dao.DaoModule');

class ConfigManageDao extends DaoModule{

    /**
     * 获取赠币配置列表
     */
    public function queryCouponConfigList($param) {
        // 初始化返回结果
        $result = array();

        // 逻辑执行
        try {
            // 初始化动态SQL
            $dySql = "";
            $limitSql = "";
            if (!empty($param['agencyName'])) {
                $dySql = $dySql . " AND b.account_name like '%" . $param['agencyName'] . "%'";
            }
            if (!empty($param['agencyId'])) {
                $dySql = $dySql . " AND a.agency_id = " . $param['agencyId'];
            }
            if (!empty($param['limit'])) {
                $limitSql = $limitSql . "LIMIT " . $param['start'] . "," . $param['limit'];
            }
            // 操作DB
            $sqlRows = "SELECT
                      a.id           AS id,
                      a.agency_id    AS agencyId,
                      a.account_id    AS accountId,
                      a.coupon_use_percent    AS couponUsePercent,
                      b.account_name AS agencyName,
                      a.add_time AS addTime
                    FROM bb_account b
                      LEFT JOIN coupon_config a
                        ON a.account_id = b.id
                          AND a.agency_id = b.vendor_id
                          AND b.del_flag = 0
                    WHERE a.del_flag = 0 " . $dySql . "
                    ORDER BY a.id,a.add_time DESC " . $limitSql;
            $sqlCount = "SELECT
                      count(0) as countRe
                    FROM bb_account b
                      LEFT JOIN coupon_config a
                        ON a.account_id = b.id
                          AND a.agency_id = b.vendor_id
                          AND b.del_flag = 0
                    WHERE a.del_flag = 0 " . $dySql;
            $result['rows'] = $this->executeSql($sqlRows, self::ALL);
            $count = $this->executeSql($sqlCount, self::ROW);
            $result['count'] = $count['countRe'];
        } catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
            throw new BBException($e->getCode(), $e->getMessage());
        }

        // 根据需要，返回结果
        return $result;
    }

    /**
     * 插入供应商赠币配置
     */
    public function insertCouponConfig($param) {
        // 逻辑执行
        try {
            // 操作DB
            $sqlRows = "INSERT INTO coupon_config
                                (account_id,
                                 agency_id,
                                 coupon_use_percent,
                                 del_flag,
                                 add_time,
                                 update_time)
                    VALUES (" . $param['accountId'] . ","
                                . $param['agencyId'] . ","
                                . "1.00 ,"
                                . "0 ,'"
                                . date('Y-m-d H:i:s') . "','"
                                . date('Y-m-d H:i:s')."')";
            $this->executeSql($sqlRows, self::SROW);
        } catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
            throw new BBException($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 删除供应商赠币配置
     */
    public function delCouponConfig($param) {
        // 逻辑执行
        try {
            // 操作DB
            $sqlRows = "UPDATE coupon_config
                    SET del_flag = 1,
                        update_time = '" . date('Y-m-d H:i:s') . "'
                    WHERE id =" . $param['id'];
            $this->executeSql($sqlRows, self::SROW);
        } catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
            throw new BBException($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 插入供应商赠币配置
     */
    public function synCouponConfig($param) {
        // 逻辑执行
        try {
            $sqlRows = array();
            foreach ($param as $accountId => $agencyId) {
                $sqlRowsTemp = "INSERT INTO coupon_config
                                (account_id,
                                 agency_id,
                                 coupon_use_percent,
                                 del_flag,
                                 add_time,
                                 update_time)
                            VALUES (" . $accountId . ","
                                        . $agencyId . ","
                                        . "1.00 ,"
                                        . "0 ,'"
                                        . date('Y-m-d H:i:s') . "','"
                                        . date('Y-m-d H:i:s')."');";
                array_push($sqlRows, $sqlRowsTemp);
            }
            // 操作DB
            $this->executeSql($sqlRows, self::SALL);
        } catch (BBException $e) {
            // 抛异常
            throw $e;
        } catch (Exception $e) {
            // 抛异常
            throw new BBException($e->getCode(), $e->getMessage());
        }
    }
}