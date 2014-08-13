<?php
/**
 * UI呈现接口 | 收客宝帐号相关
 * Buckbeek account interfaces for inner UI system.
 * @author chenjinlong@2013-01-04
 * @version 1.1
 * @func doRestGetShowEffect
 * @func doRestGetMessage
 * @func doRestGetAccountMessage
 * @func doRestGetInfo
 * @func doRestGetAdPosition
 * @func doRestGetVendorInfo
 * @func doRestGetVendorCertInfo
 */
Yii::import('application.modules.bidmanage.dal.iao.BidProductIao');
Yii::import('application.modules.bidmanage.dal.iao.FinanceIao');
Yii::import('application.modules.bidmanage.dal.iao.HagridIao');
Yii::import('application.modules.bidmanage.dal.dao.bid.BidProductDao');
Yii::import('application.modules.bidmanage.dal.dao.user.BidMessageDao');
Yii::import('application.modules.bidmanage.dal.dao.product.ProductDao');
Yii::import('application.modules.bidmanage.dal.dao.user.UserManageDao');
Yii::import('application.modules.bidmanage.dal.dao.fmis.StatementDao');
Yii::import('application.modules.bidmanage.dal.dao.common.CommonDao');
Yii::import('application.modules.bidmanage.models.bid.BidLog');
Yii::import('application.modules.bidmanage.models.product.ProductMod');
Yii::import('application.modules.bidmanage.models.user.UserManageMod');
Yii::import('application.modules.bidmanage.models.user.StaBbEffectMod');
Yii::import('application.modules.bidmanage.models.fmis.StatementMod');
Yii::import('application.modules.bidmanage.models.user.BidMessage');
Yii::import('application.modules.bidmanage.models.bid.BidProduct');
Yii::import('application.modules.bidmanage.dal.dao.date.PackageDateDao');

class UserController extends restUIServer
{
    private $_bidMessageMod;
    private $_manageMod;
    private $_staBbEffectMod;
    private $_statement;
    private $packageDateDao;

    function __construct() {
        $this->_bidMessageMod = new BidMessage();
        $this->_manageMod = new UserManageMod();
        $this->_staBbEffectMod = new StaBbEffectMod;
        $this->_statement = new StatementMod();
        $this->packageDateDao = new PackageDateDao();
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'token'=> ,);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /admessage
     * @method GET
     * @param string $url
     * @param  array $data {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b"}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】获取消息中心列表
     */
    public function doRestGetAdmessage($url, $data) {
        $params = array(
            'accountId' => $this->getAccountId()
        );
        $bidMessageList = $this->_bidMessageMod->readAdMessage($params);
        if (count($bidMessageList) > 0) {
            $this->returnRest($bidMessageList);
        } else {
            $this->returnRest(array(), false, 230099, '未知原因失败');
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'token'=> ,);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /authority
     * @method GET
     * @param string $url
     * @param  array $data {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b"}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】查询是否拥有跟团、自助游、门票的权限
     */
    public function doRestGetAuthority($url, $data) {
        $info = $this->_manageMod->getAuthority($this->getAccountId());
        $result = array();
        if (empty($info)) {
            $result = ConstDictionary::getGlobalBbProductTypeList();
        } else {
            $result = array(
                array(
                    'id' => 4,
                    'name' => ConstDictionary::getBbProductTypeNameByKey(4),
                ),
                array(
                    'id' => 5,
                    'name' => ConstDictionary::getBbProductTypeNameByKey(5),
                ),
            );
            if ($info['isGt'] == 1) {
                array_push($result,array(
                    'id' => 1,
                    'name' => ConstDictionary::getBbProductTypeNameByKey(1),
                ));
            }
            if ($info['isDiy'] == 1) {
                array_push($result,array(
                    'id' => 3,
                    'name' => ConstDictionary::getBbProductTypeNameByKey(3),
                ));
            }
            if ($info['isTicket'] == 1) {
                array_push($result,array(
                    'id' => 33,
                    'name' => ConstDictionary::getBbProductTypeNameByKey(33),
                ));
            }
        }
        // 如果为包含index_chosen或channel_chosen的广告位时，过滤其产品种类权限
        if ((strpos($data['adKey'],'index_chosen') !== false || strpos($data['adKey'],'channel_chosen') !== false) && $data['startCityCode']) {
            $adCategory = $this->packageDateDao->getAdCategory($data);
            if ($adCategory) {
                // 产品类型转换
                $convertResult = array();
                $classBrandTypes = json_decode(str_replace("\"","",$adCategory['classBrandTypes']),true);
                $globalBbProductType = ConstDictionary::$bbRorProductMapping;
                foreach ($globalBbProductType as $k => $temp) {
                    foreach ($classBrandTypes as $value) {
                    	
                        // 过滤自助游和门票
                        if ($temp == $value && '2' != $value && '6' != $value) {
                            array_push($convertResult,strval($k));
                        }
                    }
                }
                // 产品权限变更
                foreach ($result as $key => $value) {
                    if (!in_array($value['id'],$convertResult)) {
                        unset($result[$key]);
                    }
                }
            }
        }
        //针对productType权限信息进行ASC排序
        if ($result) {
            foreach($result as $key => $eachType)
            {
                $productTypeIdArr[$key] = $eachType['id'];
            }
            array_multisort($productTypeIdArr, SORT_ASC, $result);
        }

        if(empty($result)) {
            $this->returnRest(array(), false, 230019, '该招客宝账户没有参与竞拍的权限');
        } else {
            $this->returnRest(array('productType' => $result));
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /reportforms
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】bb-招客宝报表
     */
    public function doRestGetReportForms ($url, $data) {
        $params = array(
            'accountId' => $this->getAccountId(),
            'startDate' => $data['startDate'] ? $data['startDate'] : '0000-00-00',
            'endDate' => $data['endDate'] ? $data['endDate'] : '0000-00-00',
            'productId' => $data['productId'] ? $data['productId'] : '',
            'productName' => $data['productName'] ? $data['productName'] : '',
            'start' => intval($data['start']) ? intval($data['start']) : 0,
            'limit' => intval($data['limit']) ? intval($data['limit']) : 10,
        );
        $result = $this->_statement->getReportFormsList($params);
        if (count($result) > 0) {
            $this->returnRest(array('count' => $result['count'], 'rows' => $result['rows']));
        } else {
            $this->returnRest(array('count' => 0, 'rows' => array()), true, 230000, array());
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /biInfo
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】bb-招客宝报表-查询所有的BI数据
     */
    public function doRestGetBIInfo ($url, $data) {
        $params = array(
            'accountId' => $this->getAccountId(),
            'startDate' => $data['startDate'] ? $data['startDate'] : '0000-00-00',
            'endDate' => $data['endDate'] ? $data['endDate'] : '0000-00-00',
            'productId' => $data['productId'] ? $data['productId'] : '',
            'productName' => $data['productName'] ? $data['productName'] : '',
        );
        //设置缓存
        $key = md5(json_encode($params));
        $data = Yii::app()->memcache->get($key);
        if (!empty($data)) {
            $result = $data;
        } else {
            $result = $this->_statement->getAllBIInfo($params);
            if ($result) {
                Yii::app()->memcache->set(md5(json_encode($params)), $result, 43200);
            }
        }
        if (count($result) > 0) {
            $this->returnRest(array('biInfo' => $result));
        } else {
            $this->returnRest(array('biInfo' => array()), true, 230000, array());
        }
    }

    /**
     * $client = new RESTClient();
     * $requestData = array('accountId'=> ,'limit'=> ,'start'=>,
     * ,'searchKey'=>,'searchType'=>,'rankIsChange'=>);
     * $response = $client->get($url, $requestData);
     *
     * @mapping /trend
     * @method GET
     * @param string $url
     * @param  array $params {"accountId":"1","token":"1d9cab6ad9589b8aeaad4b","start":0,"limit":10}
     * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
     * @desc 【帐号】bb-招客宝报表趋势
     */
    public function doRestGetTrend ($url, $data) {
        $user = $this->getAccountInfo();
        $params = array(
            'vendorId' => $data['vendorId'] ? $data['vendorId'] : '',
            //'vendorId' =>$user['vendorId'],
            'showStartDate' => $data['showStartDate'] ? $data['showStartDate'] : '0000-00-00',
            'showEndDate' => $data['showEndDate'] ? $data['showEndDate'] : '0000-00-00',
            'routeId' => $data['productId'] ? $data['productId'] : '',
            'trendType' => $data['trendType'] ? $data['trendType'] : '',// 1=>ip访问数, 2=>有效订单数, 3=>签约订单数, 4=>订单转化率
            'statisticType' => 0,
            'start' => 0,
            'limit' => 1,
        );
        $productTrend = $this->_statement->getProductTrend($params);
        if ($productTrend) {
            $this->returnRest(array( 'index' => $productTrend));
        } else {
            $this->returnRest(array( 'index' => array()), true, 230000, array());
        }
    }
    
    /**
     * 查询供应商预算
     */
    public function doRestGetAgencybudget($url, $data) {
    	// 初始化共通参数
	    $data['accountId'] = $this->getAccountId();
	    $data['agencyId'] = $this->getAgencyId();
	    $data['isFather'] = $this->getAdminFlag();
	    // 设置默认登录名
    	if (empty($data['subAgency']) || '' == $data['subAgency']) {
    		$data['subAgencyDefault'] = $this->getLoginName();
    	}
        // 查询供应商预算
        $result = $this->_manageMod->getAgencybudget($data);
        // 返回结果
        $this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
    
    /**
     * 新增修改供应商预算
     */
    public function doRestPostAgencybudget($data) {
	    // 初始化共通参数
	    $data['data']['accountId'] = $this->getAccountId();
	    $data['data']['agencyId'] = $this->getAgencyId();
    	// 新增修改供应商预算
        $result = $this->_manageMod->saveAgencybudget($data);
        // 返回结果
        $this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
    
    /**
     * 获取供应商预算分配上限
     */
    public function doRestGetBudgetup($url, $data) {
    	// 初始化供应商ID参数
    	$data['agencyId'] = $this->getAgencyId();
    	$data['accountId'] = $this->getAccountId();
    	$data['isFather'] = $this->getAdminFlag();
    	// 获取供应商可分配预算上限
        $result = $this->_manageMod->getBudgetup($data);
        // 返回结果
        $this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
    
    /**
     * 联想查询供应商子账号
     */
    public function doRestGetSubagencyassociate($url, $data) {
    	// 初始化供应商ID参数
    	$data['agencyId'] = $this->getAgencyId();
    	$data['isFather'] = $this->getAdminFlag();
    	// 获取供应商可分配预算上限
        $result = $this->_manageMod->getSubagencyassociate($data);
        // 返回结果
        $this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
    
    /**
     * 获取供应商信息
     */
    public function doRestGetSubagencyinfo($url, $data) {
    	// 初始化返回结果
    	$result = array();
    	// 判断是否是管理员账号或父账号
    	if ($this->getAdminFlag()) {
    		$result['isFather'] = true;
    	} else {
    		$result['isFather'] = false;
    	}
    	// 返回结果
        $this->returnRest($result);
    }
    
    /**
     * 获取供应商是否开通自营
     */
    public function doRestGetAgencyisopen($url, $data) {
    	$data['accountId'] = $this->getAccountId();
    	// 获取供应商可分配预算上限
        $result = $this->_manageMod->getAgencyConfig($data);
        // 返回结果
        $this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
    
    /**
     * 开通配置自营供应商
     */
    public function doRestPostAgencyopenconfig($data) {
    	$result = $this->_manageMod->saveConfigAgency($data);
    	// 返回结果
        $this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
    
}