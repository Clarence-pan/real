<?php
/**
 * Created by JetBrains PhpStorm.
 * User: chenjinlong
 * Date: 7/18/12
 * Time: 10:16 AM
 * To change this template use File | Settings | File Templates.
 */
Yii::import('application.dal.dao.DaoModule');
class StaticProductLine extends DaoModule {
    private $_tblName = 'static_product_line';

    public function readProductCatList($targetBasis,$params,$pagerParam=array())
    {
        switch($targetBasis)
        {
            case 'productType':
                $rows = $this->dbRO->createCommand(array(
                    'select'=>'product_type productType,product_type_name productTypeName',
                    'distinct'=>'product_type',
                    'from'=>$this->_tblName,
                    'where'=>'del_flag=:del_flag',
                    'params'=>array(':del_flag'=>0),
                ))->queryAll();
                break;
            case 'destinationClass':
                $rows = $this->dbRO->createCommand(array(
                    'select'=>'cat_type destinationClass,cat_type_name destinationClassName',
                    'distinct'=>'cat_type',
                    'from'=>$this->_tblName,
                    'where'=>'product_type=:product_type',
                    'params'=>array(':product_type'=>$params['product_type']),
                ))->queryAll();
                break;
            case 'startCityCode':
                $rows = $this->dbRO->createCommand(array(
                    'select'=>'begin_city_code startCityCode,begin_city_name startCityName',
                    'distinct'=>'begin_city_code',
                    'from'=>$this->_tblName,
                    'where'=>'del_flag=:del_flag',
                    'params'=>array(':del_flag'=>0),
                ))->queryAll();
                break;
            case 'product_line':
                $condSqlSegment = '';
                if($params['product_cat_type'])
                    $condSqlSegment .=' AND cat_type='.$params['product_cat_type'];
                if($params['product_type'])
                    $condSqlSegment .=' AND product_type='.$params['product_type'];
                if($params['product_line_id'])
                    $condSqlSegment .=' AND id IN('.$params['product_line_id'].')';
                if($params['product_line_name'])
                    $condSqlSegment .=' AND name LIKE "%'.$params['product_line_name'].'%"';
                $count = $this->dbRO->createCommand()
                    ->select('count(id)')
                    ->from($this->_tblName)
                    ->where('del_flag=:del_flag'.$condSqlSegment,
                    array(':del_flag'=>0))
                    ->queryScalar();
                $data = $this->dbRO->createCommand()
                    ->select('id,name,product_type_name,cat_type_name,begin_city_name')
                    ->from($this->_tblName)
                    ->where('del_flag=:del_flag'.$condSqlSegment,
                    array(':del_flag'=>0))
                    ->limit($pagerParam['pager_count'],$pagerParam['pager_offset'])
                    ->queryAll();
                $rows = array('count'=>$count,'rows'=>$data);

                break;
            default:
                break;
        }
        return $rows;
    }

    /**
     * [product]查询产品线ID串
     * @param $params,$condSqlSegment,$paramsMapSegment
     * @return
     */
    public function readProductLineIdStr($params) {
        $condSqlSegment = '';
        $paramsMapSegment = array();
        $condSqlSegment .= " del_flag=0";
        if ($params['productType']) {
            $condSqlSegment .= ' AND product_type = :productType';
            $paramsMapSegment[':productType'] = $params['productType'];
        }
        if ($params['destinationClass']) {
            $condSqlSegment .= ' AND cat_type = :catType';
            $paramsMapSegment[':catType'] = $params['destinationClass'];
        }
        $productLineId = $this->dbRO->createCommand()
            ->select('id')
            ->from($this->_tblName)
            ->where($condSqlSegment, $paramsMapSegment)
            ->queryAll();
        if ($productLineId) {
            $productLineIdArr = array();
            foreach ($productLineId as $value) {
                $productLineIdArr[] = $value['id'];
            }
            $productLineIdStr = trim(implode(',', $productLineIdArr));
        }
        return $productLineIdStr;
    }

}