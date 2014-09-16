<?php

/**
 * Coypright © 2013 Tuniu Inc. All rights reserved.
 * Author: p-sunhao
 * Date: 11/12/13
 * Time: 3:58 PM
 * Description: StaIntegrateMod.php
 */
Yii :: import('application.modules.bidmanage.models.date.PackageDateMod');
Yii::import('application.modules.bidmanage.models.product.ProductMod');

class PackagedateController extends restSysServer {

	/**
	 * 打包时间操作类
	 */
	private $_packageDateMod;
    private $_product;

	/**
	 * 默认构造函数
	 */
	function __construct() {
		$this->_packageDateMod = new PackageDateMod();
        $this->_product = new ProductMod();
	}

	/*************************************PackageDateController.php接口*******************************************/

	/**
	 * $client = new RESTClient();
	 * $requestData = array('type'=> ,'date'=> ,);
	 * $response = $client->get($url, $requestData);
	 *
	 * @mapping /packageDtae
	 * @method GET
	 * @param string $url 
	 * @param  array $params {'start'=> , 'limit'=> ,}
	 * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
	 * @desc 获取打包时间信息
	 */
	public function doRestGetquePakDat($url, $requestData) {
		try {
			// 分类查询运营计划
			if (empty($requestData['pack_flag']) && 0 != $requestData['pack_flag'] && 1 != $requestData['pack_flag']) {
				// 获取老版运营计划结果
				$this->returnRest($this->_packageDateMod->queryPakDat($requestData));
			} else if (0 == intval($requestData['pack_flag'])) {
				// 获取新版运营计划列表数据
				$this->returnRest($this->_packageDateMod->queryPakTab($requestData));
			} else if (0 < intval($requestData['pack_flag'])) {
				// 获取新版运营计划明细数据
				$this->returnRest($this->_packageDateMod->queryPakDel($requestData));
			}
			
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 返回错误结果
			$this->returnRest(array (), false, 230001, '数据异常');
		}
	}

	/**
	 * $client = new RESTClient();
	 * $requestData = array('type'=> ,'date'=> ,);
	 * $response = $client->post($url, $requestData);
	 *
	 * @mapping /packageDtae
	 * @method POST
	 * @param  array $data
	 * @return array {"success":false|true,"msg":"","errorcode":|470000,"data":}
	 * @desc 更新打包时间信息
	 */
	public function doRestPostsavPakDat($requestData) {
		// 初始化操作类型
		$postType = $requestData['type'];
		try {
			// 分类调用操作层函数
			switch ($postType) {
				// 新增
				case 'insert':
					$result = $this->_packageDateMod->insertPakDat($requestData['data'], $requestData['uid']);
					break;
				// 批量新增
				case 'insertMulti' :
					$result = $this->_packageDateMod->insertMutliPakDat($requestData);
					break;
				// 更新
				case 'update' :
					$result = $this->_packageDateMod->updatePakDat($requestData['data'], $requestData['uid']);
					break;
				// 打包
				case 'submit' :
					$result = $this->_packageDateMod->submitPakDat($requestData['data'], $requestData['uid']);
					break;
				// 删除
				case 'delete' :
					$result = $this->_packageDateMod->deletePakDat($requestData['data'], $requestData['uid']);
					break;
				// 新新增
				case 'ins':
					$result = $this->_packageDateMod->insPakDat($requestData['data'], $requestData['uid']);
					break;
				// 新更新
				case 'upd' :
					$result = $this->_packageDateMod->updPakDat($requestData['data'], $requestData['uid']);
					break;
				// 新打包
				case 'sub' :
					$result = $this->_packageDateMod->subPakDat($requestData['data'], $requestData['uid']);
					break;
				// 新删除
				case 'del' :
					$result = $this->_packageDateMod->delPakDat($requestData['data'], $requestData['uid']);
					break;
				default :
					// 返回结果
					$this->returnRest(array ('flag'=>false, 'msg'=>'参数不正确，操作错误！'), false, 230115, '参数不正确，操作错误！');
					break;
			}
			// 设置定制msg
			if (true === $result) {
				$msg = '保存成功！';
                $errorCode = 2300000;
			} elseif (-1 === $result) {
                $msg = '打包计划已经参与竞拍, 不能操作！';
                $result = false;
                $errorCode = 230115;
            } elseif (-2 === $result) {
                $msg = '竞拍开始时间必须小于等于竞拍结束时间';
                $result = false;
                $errorCode = 230115;
            } elseif (-3 === $result) {
                $msg = '竞拍开始时间点必须小于竞拍结束时间点';
                $result = false;
                $errorCode = 230115;
            } elseif (is_array($result) && !empty($result)) {
                $msg = '所选打包日期已存在的竞拍位置：';
                foreach ($result as $tempResult) {
                    if ('index_chosen_all' == $tempResult) {
                        $msg.='首页-全部 ';
                    }
                    // 获取对应的adName
                    $adKeyInfo = $this->_product->getAdKeyInfo($tempResult);
                    if ($adKeyInfo[0]) {
                        $msg.= $adKeyInfo[0]['adName'].' ';
                    }
                }
                $result = false;
                $errorCode = 230115;
            } else {
				$msg = '打包时间段重复, 保存失败！';
                $result = false;
                $errorCode = 230115;
			}
			
			// 返回结果
			$this->returnRest(array ('flag'=>$result, 'msg'=>$msg), true, $errorCode, $msg);
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 返回错误结果
			$this->returnRest(array ('flag'=>false, 'msg'=>'数据异常！'), false, 230001, '数据异常');
		}

	}

	/*************************************PackageDateController.php接口*******************************************/

    /**
     * 广告位管理列表
     *
     * @param $url
     * @param $data
     * @return array
     */
    public function doRestGetAdManageList($url, $requestData) {
        $result = $this->_packageDateMod->getAdManageList($requestData);
        $this->returnRest($result);
    }

    /**
     * 广告位是否存在
     *
     * @param $url
     * @param $data
     * @return array
     */
    public function doRestGetAdIsExist($url, $requestData) {
        $result = $this->_packageDateMod->getAdIsExist($requestData);
        $this->returnRest($result);
    }

    /**
     * 删除广告位
     *
     * @param $url
     * @param $data
     * @return array
     */
    public function doRestPostAdDel($requestData) {
        $result = $this->_packageDateMod->postAdDel($requestData);
        $this->returnRest($result);
    }

    /**
     * 添加广告位
     *
     * @param $url
     * @param $data
     * @return array
     */
    public function doRestPostAdAdd($requestData) {
        $result = $this->_packageDateMod->postAdAdd($requestData);
        if($result) {
            $this->returnRest($result);
        }else {
            $this->returnRest(array(), false, 230021, '添加广告位失败');
        }
    }

    /**
     * 查询可添加和编辑包场的运营计划
     */
    public function doRestGetBuyoutdate($data) {
        // 初始化返回结果
        $result = array();
        // 查询可添加和编辑包场的运营计划
        $result = $this->_packageDateMod->getBuyoutDate($data);
        // 返回结果
        $this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    }
    
    /**
     * 获取首页所有位置的配置信息
     * 
     * @author wenrui 2014-06-05
     */
    public function doRestGetIndexAdList($url, $param){
    	$result = $this->_packageDateMod->getIndexAdList($param);
        $this->returnRest($result);
    }
    
    /**
	 * 添加多个广告位的运营计划new
	 * 
	 * @author wenrui 2014-06-05
	 */
    public function doRestPostAddPakDtList($param){
    	$result = $this->_packageDateMod->addPakDtList($param);
    	if($result){
    		$this->returnRest(array(),true,0,'更新成功');
    	}else{
    		$this->returnRest(array(),false,0,'系统出错，更新失败');
    	}
    }
    
	/**
	 * 添加运营计划new
	 * 
	 * @author wenrui 2014-06-05
	 */
    public function doRestPostAddPakDt($param){
    	$operation = empty($param['flag']) ? '' : $param['flag'];
    	switch($operation){
    		case 'ins':
    			// 添加运营计划
	    		$result = $this->_packageDateMod->addPakDt($param);
	    		break;
    		case 'upd':
    			// 更新运营计划
    			$result = $this->_packageDateMod->updatePakDt($param);
    			break;
			default :
				$this->returnRest(array(), false, 230115, '参数不正确，操作错误');
    	}
    	if($result){
    		$this->returnRest(array(),true,0,'操作成功');
    	}else{
    		$this->returnRest(array(),false,0,'操作失败');
    	}
    }

    /**
     * 查询打包日期详情
     *
     * @param $url
     * @param $requestData
     * @return array
     */
    public function doRestGetShowDateInfo($url, $requestData) {
        $result = $this->_packageDateMod->getShowDateInfo($requestData['showDateId']);
        $this->returnRest($result);
    }

    /**
     * 保存打包计划日期
     *
     * @param $data
     * @return array
     */
    public function doRestPostShowDateInfo($requestData) {
        if (!$requestData['uid']) {
            // 返回参数不正确
            $this->returnRest(array('flag' => true,'msg' => '请重新登录！'), true, 210000, '参数不正确！');
        } else {
            $result = $this->_packageDateMod->saveShowDateInfo($requestData, $requestData['uid']);
            $this->returnRest($result);
        }
    }

    /**
     * 查询广告位置信息
     *
     * @param $url
     * @param $requestData
     * @return array
     */
    public function doRestGetAdPositionInfo($url, $requestData) {
        $result = $this->_packageDateMod->getAdPositionInfo($requestData);
        $this->returnRest($result);
    }
    
    /**
     * 打开或关闭广告位
     */
    public function doRestPostPackopenstatus($data) {
    	// 若参数正确，则打开或关闭广告位
    	if (!empty($data['id'])) {
            // 打开或关闭广告位
            $result = $this->_packageDateMod->updatePackOpenStatus($data);
    		// 返回结果
    		$this->returnRest($result['data'], $result['success'], $result['errorCode'], $result['msg']);
    	} else {
    		// 返回参数不正确
    		$this->returnRest(array(), false, 210000, '参数不正确！');
    	}
    } 
    
}
?>