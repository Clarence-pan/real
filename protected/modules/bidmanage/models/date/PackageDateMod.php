<?php
/**
 * Coypright © 2013 Tuniu Inc. All rights reserved.
 * Author: p-sunhao
 * Date: 11/11/13
 * Time: 4:52 PM
 * Description: PackageDateDao.php
 */
// 打包时间
Yii::import('application.modules.bidmanage.dal.dao.date.PackageDateDao');
Yii::import('application.modules.bidmanage.dal.dao.product.ProductDao');
Yii::import('application.modules.bidmanage.models.iao.IaoProductMod');
Yii::import('application.modules.bidmanage.models.product.ProductMod');

/**
 * 打包时间业务处理类
 * 
 * Author: p-sunhao
 */
class PackageDateMod {
	
	/**
	 * 打包时间
	 */
	private $packageDateDao;
    private $_iaoProductMod;
    	/**
	 * 包场信息
	 */
	private $productDao;
    private $productMod;
    
    /**
     * 构造函数
     */
    function __construct() {
    	// 打包时间
    	$this->packageDateDao = new PackageDateDao();
        $this->_iaoProductMod = new IaoProductMod();
	// 包场信息
    	$this->productDao = new ProductDao();
        $this->productMod = new ProductMod();
    }
	
	/**
	 * 查询打包时间列表
	 * @param array $param
	 * @return array
	 */
	public function queryPakDat($param) {
		try {
			// 设置查询标记为列表查询
			$param['queryFlag'] = 0;
			// 查询打包时间信息
			$result = $this->packageDateDao->queryPackageDate($param);
			// 查询打包时间数量
			$count = $this->packageDateDao->queryPackageCount($param);
			// 整合查询结果
			$reData['count'] = $count['count'];
			$reData['rows'] = $result;
			$reData['start'] = $param['start'];
			$reData['limit'] = $param['limit'];
			// 查询成功返回数据
			return $reData;
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
	}
	
	/**
	 * 插入单条打包时间记录
	 * @param array $param
	 * @return array
	 */
	public function insertPakDat($param, $uid) {
		try {
//			// 设置查询标记为对比单条查询
//			$param['queryFlag'] = 3;
//			// 查询数据库
//			$queryResult = $this->packageDateDao->queryPackageDate($param);
//			// 如果查询结果不为空，则返回错误结果
//			if (!empty($queryResult) && is_array($queryResult)) {
//				// 返回错误结果
//				return false;
//			} else {
				// 插入信息
				$result = $this->packageDateDao->insertPackageInfo($param, $uid);
//			}
			
			// 判断是否插入成功
			if ($result) {
				// 返回插入成功
				return true;
			} else {
				// 返回插入失败
				return false;				
			}
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
	}
	
	/**
	 * 插入多条打包时间记录
	 * @param array $param
	 * @return array
	 */
	public function insertMutliPakDat($param) {
		try {
			// 设置查询标记为对比单条查询
			$paramTemp['queryFlag'] = 2;
			// 查询数据库
			$queryResult = $this->packageDateDao->queryPackageDate($paramTemp);
			// 如果查询结果不为空，则循环对比
			if (!empty($queryResult) && is_array($queryResult)) {
				
				// 循环对比变量
				foreach ($queryResult as $reObj) {
					foreach ($param['data'] as $paObj) {		
						// 对比是否有重复项
						if ($reObj['showStartDate']==$paObj['showStartDate']) {
							// 有重复，返回错误结果
							return false;
						}
					}						
				}
				// 没有重复记录，循环插入信息
				foreach ($param['data'] as $paObj) {
					// 插入信息
					$result = $this->packageDateDao->insertPackageInfo($paObj, $param['uid']);
					// 如果插入信息出错，则提示前台出错
					if (!$result) {
						// 返回插入失败
						return false;
					}		
				}
			} else {
				// 循环插入信息
				foreach ($param['data'] as $paObj) {
					// 插入信息
					$result = $this->packageDateDao->insertPackageInfo($paObj, $param['uid']);
					// 如果插入信息出错，则提示前台出错
					if (!$result) {
						// 返回插入失败
						return false;
					}					
				}
			}
			// 返回插入成功
			return true;
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
	}
	
	/**
	 * 更新单条打包时间记录
	 * @param array $param
	 * @return array
	 */
	public function updatePakDat($param, $uid) {
		try {
			// 设置修改参数为更新
			$param['updateFlag'] = 0;
            // 时间参数校验
            if ($param['bidStartDate'] > $param['bidEndDate']) {
                return -2;
            }
            if ($param['bidStartDate'] == $param['bidEndDate'] && $param['bidStartTime'] >= $param['bidEndTime']) {
                return -3;
            }
            // 查询该条打包计划是否已经被竞拍
            $bidFlag = $this->packageDateDao->packageIsBided($param);
            if (true == $bidFlag) {
                return -1;
            }
//						// 设置查询标记为对比单条查询
//			$param['queryFlag'] = 1;
//			// 查询数据库
//			$queryResult = $this->packageDateDao->queryPackageDate($param);
//			
//			// 如果查询结果不为空，则返回错误结果
//			if (!empty($queryResult) && is_array($queryResult)) {
//				// 返回错误结果
//				return false;
//			} else {
				// 更新信息
				$result = $this->packageDateDao->updatePackageInfo($param, $uid);
//			}
			
			// 判断是否保存成功
			if ($result) {
				// 返回保存成功
				return true;
			} else {
				// 返回保存失败
				return false;				
			}
			
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
	}
	
	/**
	 * 发布打包时间
	 * @param array $param
	 * @return array
	 */
	public function submitPakDat($param, $uid) {
		try {
			// 设置修改参数为更新
			$param['updateFlag'] = 0;
			
			// 更新信息
			$result = $this->packageDateDao->updatePackageInfo($param, $uid);
			
			// 判断是否保存成功
			if ($result) {
				// 返回保存成功
				return true;
			} else {
				// 返回保存失败
				return false;				
			}
			
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
	}
	
	/**
	 * 删除打包时间记录
	 * @param array $param
	 * @return array
	 */
	public function deletePakDat($param, $uid) {
		try {
		    // 设置修改参数为删除
			$param['updateFlag'] = 1;
            // 查询该条打包计划是否已经被竞拍
            $bidFlag = $this->packageDateDao->packageIsBided($param);
            if (true == $bidFlag) {
                return -1;
            }
            
			// 删除打包计划信息
			$result = $this->packageDateDao->updatePackageInfo($param, $uid);
			
			// 判断是否删除成功
			if ($result) {
				// 返回删除成功
				return true;
			} else {
				// 返回删除失败
				return false;				
			}
			
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
	}

    /**
     * 招客宝改造-计算指定id的打包时间长度
     *
     * @author chenjinlong 20131118
     * @param $showDateId
     * @return int
     */
    public function getBidShowDays($showDateId)
    {
        if($showDateId > 0 && !empty($showDateId)){
            $count = $this->packageDateDao->countBidShowDays($showDateId);
            return intval($count);
        }else{
            return 0;
        }
    }
	
	/**
	 * 查询列表
	 * 
	 * @param $param
	 * @return array()
	 */
	public function queryPakTab($param) {
		// 设置查询标记为列表查询
		$param['queryFlag'] = 0;
		// 查询打包时间信息
		$result = $this->packageDateDao->queryPackageDate($param);
		// 查询打包时间数量
		$count = $this->packageDateDao->queryPackageCount($param);
		// 初始化查询ID参数
		$paramIdArr = '';
		// 预整合查询结果
		$reData['count'] = $count['count'];
		$reData['start'] = $param['start'];
		$reData['limit'] = $param['limit'];
		// 若全部没有打包时间，则直接空
		if (empty($result) || null == $result) {
			// 整合结果
			$reData['rows'] = $result;
			// 返回结果
			return $reData;
		}
		// 循环初始化广告位信息查询ID参数
		foreach($result as $k => $reObj) {
			// 累加查询ID
			$paramIdArr = $paramIdArr.$reObj['id'].',';
			// 初始化ad_key和ad_name为空
			$result[$k]['ad_key']='';
			$result[$k]['ad_name']='';
		}
		// 过滤ID参数
		$paramIdArr = substr($paramIdArr, 0, strlen($paramIdArr) - 1);
        // 查询打包时间的其它广告位信息
        $adRe = $this->packageDateDao->queryAdInfo($paramIdArr);
        // 查询打包时间的首页广告位信息
        $indexAdRe = $this->packageDateDao->queryIndexAd($paramIdArr);
        if (!empty($indexAdRe)) {
            $adRe = array_merge($adRe,$indexAdRe);
        }
        // 查询打包时间的频道页广告位信息
        $channelAdRe = $this->packageDateDao->queryChannelAd($paramIdArr);
        if (!empty($indexAdRe)) {
            $adRe = array_merge($adRe,$channelAdRe);
        }
        // 查询打包时间的分类页广告位信息
        $clsrecomAdRe = $this->packageDateDao->queryClsrecomAd($paramIdArr);
        $adRe = array_merge($adRe,$clsrecomAdRe);
        
		// 若全部没有打包时间广告位信息，则直接返回数据
		if (empty($adRe) || null == $adRe) {
			// 整合结果
			$reData['rows'] = $result;
			// 返回结果
			return $reData;
		}
        // 循环整合列表结果集
        foreach($result as $k => $reObj) {
            // 初始化临时结果集合
            $tempArr = array();

            $indexChosenSignal = 0;
            $channelChosenSignal = 0;

            foreach($adRe as $adObj) {
                // 如果改打包时间有广告位竞拍信息，则添加竞拍信息
                if ($reObj['id'] == $adObj['show_date_id']) {

                    if(strpos($adObj['ad_key'], 'index_chosen') !== false && 'index_chosen' != $adObj['ad_key'] && $indexChosenSignal == 0){
                        $indexChosenSignal = 1;
                        array_push($tempArr, array(
                            'ad_name' => '首页-全部',
                            'show_date_id' => $adObj['show_date_id'],
                        ));
                    } else if(strpos($adObj['ad_key'], 'channel_chosen') !== false && 'channel_chosen' != $adObj['ad_key'] && $channelChosenSignal == 0){
                        $channelChosenSignal = 1;
                        array_push($tempArr, array(
                            'ad_name' => '频道页-全部',
                            'show_date_id' => $adObj['show_date_id'],
                        ));
                    } else if ('index_chosen' == $adObj['ad_key'] || 'class_recommend' == $adObj['ad_key'] || 'search_complex' == $adObj['ad_key'] || 'brand_zone' == $adObj['ad_key']) {
                    	// 添加临时结果集
						array_push($tempArr, $adObj);
                    }

                    
				}
			}
			// 设置广告位信息
			$result[$k]['ad_info']=$tempArr;
		}
		// 整合结果
		$reData['rows'] = $result;
		// 返回结果
		return $reData;
	}
	
	/**
	 * 查询打包时间详细信息
	 * 
	 * @param $param
	 * @return array()
	 */
	public function queryPakDel($param) {
        $ad_product_count = '';
        $floor_price = '';
        $coupon_use_percent = '';
        $show_date_id = '';
        $ad_position_id = '-1';
        // 是否存在首页广告位标记
        $indexFlag = 0;
        // 查询广告类型维度信息
        $adTypeWd = $this->packageDateDao->queryAdDetailInfo($param['show_date_id']);

        // 所有首页处理
        $indexAllAd = $this->packageDateDao->queryIndexAllAdInfo($param['show_date_id']);
        if ($indexAllAd) {
            $indexFlag = 1;
            $ad_product_count = $indexAllAd['ad_product_count'];
            $floor_price = $indexAllAd['floor_price'];
            $coupon_use_percent = $indexAllAd['coupon_use_percent'];
            $show_date_id = $indexAllAd['show_date_id'];
            if ($param['show_date_id'] != "-1") {
                $ad_position_id = $indexAllAd['ad_position_id'];
            } else {
                $ad_position_id = "-1";
            }
        }

        // 首页老数据处理
        $indexAd = $this->packageDateDao->queryIndexAdInfo($param['show_date_id']);
        // 只让查看和编辑，不让新增
        if ($indexAd && '-1' != $indexAd['ad_position_id']) {
            array_push($adTypeWd,$indexAd);
        }

        // 查询附加维度信息
        $extWd = $this->packageDateDao->queryExtWd();
        // 预初始化已勾选的附加信息数据
        $vasInfo = array();
        // 分类判断是新增查询还是编辑查询
        if (1 == intval($param['pack_flag'])) {
            // 初始化已勾选的附加信息数据查询参数
            $alGx = '-1,';
            // 循环初始化参数
            foreach ($adTypeWd as $k => $adTypeWdObj) {
                // 累加参数
                $alGx = $alGx.$adTypeWdObj['ad_position_id'].",";

            }
			// 过滤参数
			$alGx = substr($alGx, 0, strlen($alGx) - 1);
			// 查询已勾选的附加信息数据
			$vasInfo = $this->packageDateDao->queryVasInfo($alGx);
		}
		// 循环整合结果
		foreach($adTypeWd as $k => $adTypeWdObj) {
			// 初始化附加信息结果集
			$vasRe = array();
			// 开启附加信息维度循环
			foreach($extWd as $extWdObj) {
				// 如果类型ID匹配，则添加附加信息
				if ($adTypeWdObj['id'] == $extWdObj['position_type_id']) {
					// 初始化附加信息结果集对象
					$vasObj = array();
					// 默认设置vas_id为-1
					$vasObj['vas_id']=-1;
					// 设置key，name和底价
					$vasObj['vas_key']=$extWdObj['vas_key'];
					$vasObj['vas_name']=$extWdObj['vas_name'];
					$vasObj['unit_floor_price']=$extWdObj['unit_floor_price'];
					// 设置附加信息位置信息为空值
					$vasObj['vas_position']='';
					// 开启已勾选的附加信息循环
					foreach($vasInfo as $vasInfoObj) {
						// 若位置ID相等，则添加勾选ID
						if ($vasInfoObj['ad_position_id'] == $adTypeWdObj['ad_position_id'] && $vasInfoObj['vas_key'] == $extWdObj['vas_key']) {
							// 设置为已勾选  ID为已勾选的ID
							$vasObj['vas_id']=$vasInfoObj['id'];
							// 设置附加信息位置信息
							$vasObj['vas_position']=$vasInfoObj['vas_position'];
							// 中断循环
							break;
						}
					}
					// 添加临时结果集
					array_push($vasRe, $vasObj);
				}
			}
			// 设置明细信息
			$adTypeWd[$k]['vas'] = $vasRe;
		}

        // 打包计划针对首页后台临时处理
        if ($indexFlag == 1) {
            $tempAdKey = array(
                'ad_key' => 'index_chosen_all',
                'ad_name' => '首页-全部',
                'start_city_code' => '',
                'start_city_name' => '',
                'ad_position_id' => $ad_position_id ? $ad_position_id : '',
                'ad_product_count' => $ad_product_count ? $ad_product_count : '',
                'floor_price' => $floor_price ? $floor_price : '',
                'coupon_use_percent' => $coupon_use_percent ? $coupon_use_percent : '',
                'show_date_id'=> $show_date_id ? $show_date_id : '',
                'vas' => array());
            array_push($adTypeWd,$tempAdKey);
        }

        // 结果重新赋值
        $rows = array();
        foreach ($adTypeWd as $temp) {
            $rows[] = $temp;
        }
		// 初始化最终结果
		$result['rows'] = $rows;
		// 返回结果
		return $result;
	}
	
	/**
	 * 插入单条打包时间记录
	 * 
	 * @param array $param
	 * @return array
	 */
	public function insPakDat($param, $uid) {
		try {
            if ($param['bidStartDate'] > $param['bidEndDate']) {
                return -2;
            }
            if ($param['bidStartDate'] == $param['bidEndDate'] && $param['bidStartTime'] >= $param['bidEndTime']) {
                return -3;
            }
            // 查询新增的打包计划是否与已经存在的出现重复
            $existArray = array();
            $indexChosenSignal = 0;
            $existPosition = $this->packageDateDao->existPosition($param);
            if ($existPosition) {
                foreach ($existPosition as $tempPosition) {
                    foreach ($param['detail'] as $newPosition) {
                        if ($newPosition['ad_key'] == $tempPosition['ad_key']) {
                            array_push($existArray,$newPosition['ad_key']);
                        }
                        if ($newPosition['ad_key'] == 'index_chosen_all' && strpos($tempPosition['ad_key'], 'index_chosen') !== false && $indexChosenSignal == 1) {
                            continue;
                        } elseif ($newPosition['ad_key'] == 'index_chosen_all' && strpos($tempPosition['ad_key'], 'index_chosen') !== false && $indexChosenSignal == 0) {
                            array_push($existArray,$newPosition['ad_key']);
                            $indexChosenSignal = 1;
                        }
                    }
                }
                if (!empty($existArray)) {
                    return $existArray;
                }
            }
			// 插入打包时间信息
			$dateId = $this->packageDateDao->insertPackageInfo($param, $uid);
			// 判断是否插入成功
			if ($dateId) {
				// 初始化位置结果集
				$posiRe = $param['detail'];
                // 循环插入位置信息
                foreach($posiRe as $posiObj) {
                    //插入首页全部广告位
                    if ('index_chosen_all' == $posiObj['ad_key']) {
                        $result = $this->packageDateDao->getIndexAd();
                        if ($result) {
                            foreach ($result as $indexAdArr) {
                                $indexParam = array(
                                    'ad_key' => $indexAdArr['adKey'],
                                    'ad_name' => $indexAdArr['adName'],
                                    'start_city_code' => $indexAdArr['startCityCode'],
                                    'ad_product_count' => $posiObj['ad_product_count'],
                                    'floor_price' => $posiObj['floor_price'],
                                    'coupon_use_percent' => $posiObj['coupon_use_percent']);
                                // 插入位置信息至数据库
                                $posiId = $this->packageDateDao->insertBaadPosition($indexParam, $uid, $dateId);
                                // 判断是否插入成功
                                if (!$posiId) {
                                    return false;
                                }
                            }
                        }
                    } else {
                        // 插入位置信息至数据库
                        $posiId = $this->packageDateDao->insertBaadPosition($posiObj, $uid, $dateId);
                        // 判断是否插入成功
                        if ($posiId) {
                            // 初始化附加信息结果集
                            $vasRe = $posiObj['vas'];
                            // 循环插入附加信息
                            foreach($vasRe as $vasObj) {
                                // 插入附加信息至数据库
                                $result = $this->packageDateDao->insertBaadVas($vasObj, $uid, $posiId);
                                // 判断是否插入成功
                                if (!$result) {
                                    return false;
                                }
                            }
                        } else {
                            // 返回插入失败
                            return false;
                        }
                    }
                }
                // 返回插入成功
                return true;
            } else {
				// 返回插入失败
				return false;				
			}
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
	}
	
	/**
	 * 更新单条打包时间记录
	 * 
	 * @param array $param
	 * @return array
	 */
	public function updPakDat($param, $uid) {
		// 设置标记为更新
		$param['updateFlag'] = 0;
        // 时间参数校验
        if ($param['bidStartDate'] > $param['bidEndDate']) {
            return -2;
        }
        if ($param['bidStartDate'] == $param['bidEndDate'] && $param['bidStartTime'] >= $param['bidEndTime']) {
            return -3;
        }
        // 查询该条打包计划是否已经被竞拍
        $bidFlag = $this->packageDateDao->packageIsBided($param);
        if (true == $bidFlag) {
            return -1;
        }
		// 更新打包时间表
		$dateFlag = $this->packageDateDao->updatePackageInfo($param, $uid);
		// 判断是否更新成功
		if (!$dateFlag) {
			// 更新失败，返回false
			return false;
		}
		// 判断是否需要删除已勾选的广告位置
		if (!empty($param['del_ad_position_id']) && null != $param['del_ad_position_id'] && '' != $param['del_ad_position_id']) {
			// 删除已勾选的广告位置
			$this->packageDateDao->deleteBaadPosition($param['del_ad_position_id'], $uid, 0);
			// 删除附加信息表
			$this->packageDateDao->deleteBaadVas($param['del_ad_position_id'], $uid, 1);
		}
		// 初始化广告位置数据集合
		$detail = $param['detail'];
		// 循环更新或插入广告位置数据
		foreach($detail as $detailObj) {
			// 预初始化位置ID临时变量
			$positionIDTemp = '';
            //更新首页全部广告位
            if ('index_chosen_all' == $detailObj['ad_key']) {
                // 判断是更新还是新增
                if (-1 == $detailObj['ad_position_id']) {
                    // 新增
                    $result = $this->packageDateDao->getIndexAd();
                    if ($result) {
                        foreach ($result as $indexAdArr) {
                            $indexParam = array(
                                'ad_key' => $indexAdArr['adKey'],
                                'ad_name' => $indexAdArr['adName'],
                                'start_city_code' => $indexAdArr['startCityCode'],
                                'ad_product_count' => $detailObj['ad_product_count'],
                                'floor_price' => $detailObj['floor_price'],
                                'coupon_use_percent' => $detailObj['coupon_use_percent']);
                            // 插入位置信息至数据库
                            $posiId = $this->packageDateDao->insertBaadPosition($indexParam, $uid, $param['id']);
                            // 判断是否插入成功
                            if (!$posiId) {
                                return false;
                            }
                        }
                    }
                } else {
                    // 更新
                    $indexParam = array('show_date_id' => $param['id'], 'ad_key' => $detailObj['ad_key'], 'ad_product_count' => $detailObj['ad_product_count'], 'floor_price' => $detailObj['floor_price'], 'coupon_use_percent' => $detailObj['coupon_use_percent']);
                    $this->packageDateDao->updateBaadPosition($indexParam, $uid);
                }
            } else {
                // 判断是更新还是新增
                if (-1 == $detailObj['ad_position_id']) {
                    // 新增
                    $tempFlag = $this->packageDateDao->insertBaadPosition($detailObj, $uid, $param['id']);
                    // 判断新增是否成功
                    if (!$tempFlag) {
                        return false;
                    }
                    // 初始化位置ID临时变量
                    $positionIDTemp = $tempFlag;
                } else {
                    // 更新
                    $this->packageDateDao->updateBaadPosition($detailObj, $uid);
                    // 判断是否需要删除附加位置信息
                    if (!empty($detailObj['del_vas_id']) && null != $detailObj['del_vas_id'] && '' != $detailObj['del_vas_id']) {
                        // 删除附加位置信息
                        $this->packageDateDao->deleteBaadVas($detailObj['del_vas_id'], $uid, 0);
                    }
                    // 初始化位置ID临时变量
                    $positionIDTemp = $detailObj['ad_position_id'];
                }
                // 初始化附加信息数据集合
                $vasRe = $detailObj['vas'];
                // 循环插入附加信息
                foreach ($vasRe as $vasObj) {
                    // 判断是新增还是更新
                    if (-1 == $vasObj['vas_id']) {
                        // 将附加信息插入数据库
                        $this->packageDateDao->insertBaadVas($vasObj, $uid, $positionIDTemp);
                    } else {
                        // 更新附加信息
                        $this->packageDateDao->updateBaadVas($vasObj, $uid);
                    }
                }
            }
        }
		// 更新成功，返回true
		return true;
	}
	
	/**
	 * 发布打包时间
	 * 
	 * @param $param
	 * @param $adduid
	 * @return boolean
	 */
	public function subPakDat($param, $uid) {
		try {
			// 发布信息
			$result = $this->packageDateDao->submitPackage($param, $uid);
			
			// 判断是否发布成功
			if ($result) {
				// 返回发布成功
				return true;
			} else {
				// 返回发布失败
				return false;				
			}
			
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
	}
	
	/**
	 * 删除打包时间
	 * 
	 * @param array() $param
	 * @param int $uid
	 * @return boolean
	 */
	public function delPakDat($param, $uid) {
		try {
			// 设置标记为删除
			$param['updateFlag'] = 1;
            // 查询该条打包计划是否已经被竞拍
            $bidFlag = $this->packageDateDao->packageIsBided($param);
            if (true == $bidFlag) {
                return -1;
            }
			// 删除打包时间
			$result = $this->packageDateDao->updatePackageInfo($param, $uid);
			
			// 判断是否删除成功
			if (!$result) {
				// 返回删除失败
				return false;				
			}
			// 查询position_id
			$result = $this->packageDateDao->queryPositionId($param['id']);
			// 判断改打包计划是否有位置信息关联
			if (!empty($result)) {
				// 有关联，初始化附加信息删除条件
				$positionIdArr = '';
				// 循环累加附加信息删除条件
				foreach($result as $resultObj) {
					// 累加条件
					$positionIdArr = $positionIdArr.$resultObj['id'].',';
				}
				// 过滤条件
				$positionIdArr = substr($positionIdArr, 0, strlen($positionIdArr) - 1);
				// 删除附加信息
				$result = $this->packageDateDao->deleteBaadVas($positionIdArr, $uid, 1);
				// 判断是否删除成功
				if (!$result) {
					// 返回删除失败
					return false;				
				}
				// 删除位置信息
				$result = $this->packageDateDao->deleteBaadPosition($param['id'], $uid, 1);
				// 判断是否删除成功
				if (!$result) {
					// 返回删除失败
					return false;				
				}
			}
			// 全部删除成功，返回true
			return true;
		} catch (Exception $e) {
			// 打印错误日志
			Yii :: log($e, 'warning');
			// 抛异常
			throw new Exception($e);
		}
	}

    /**
     * 广告位管理列表
     *
     * @param $params
     * @return array
     */
    public function getAdManageList($params) {
        $result = $this->packageDateDao->getAdManageList($params);
        $data = array();
        if ($result['count']) {
            // 格式化categoryId，catType，classBrandTypes
            foreach ($result['rows'] as $k => $tempData) {
                $result['rows'][$k]['categoryId'] = json_decode($tempData['categoryId'],true);
                $result['rows'][$k]['catType'] = json_decode($tempData['catType'],true);
                $result['rows'][$k]['classBrandTypes'] = json_decode($tempData['classBrandTypes'],true);
            }
            // 获取所有出发城市
            $startCityList = $this->_iaoProductMod->getMultiCityInfo();
            foreach ($result['rows'] as $temp) {
                // 根据出发城市code获取name
                if ($startCityList['all']) {
                    foreach ($startCityList['all'] as $tempArr) {
                        if ($tempArr['code'] == $temp['startCityCode']) {
                            $temp['startCityName'] = $tempArr['name'];
                            break;
                        } else {
                            $temp['startCityName'] = '';
                        }
                    }
                }
                $data[] = $temp;
            }
        }
        return array('count' => $result['count'], 'rows' => $data);
    }

    /**
     * 广告位是否存在
     *
     * @param $params
     * @return array
     */
    public function getAdIsExist($params) {
        return $this->packageDateDao->getAdIsExist($params);
    }

    /**
     * 删除广告位
     *
     * @param $params
     * @return array
     */
    public function postAdDel($params) {
        // 查询该广告位是否已经被竞拍
        $bidFlag = $this->packageDateDao->adKeyIsBided($params);
        if (true == $bidFlag) {
            return -1;
        }
        return $this->packageDateDao->postAdDelByName($params);
    }

    /**
     * 添加广告位
     *
     * @param $params
     * @return array
     */
    public function postAdAdd($params) {
        // 如果为包含index_chosen的首页广告位时，过滤招客宝支持的产品类型数据并保存到数据库; 新增频道页广告位
        if (strpos($params['adKey'],'index_chosen') !== false || strpos($params['adKey'],'channel_chosen') !== false) {
            $classBrandTypes = array();
            // 产品类型转换
            $globalBbProductType = ConstDictionary::$bbRorProductMapping;
            foreach ($globalBbProductType as $temp) {
                if ($params['classBrandTypes']) {
                    foreach ($params['classBrandTypes'] as $value) {
                        // 过滤自助游和门票
                        if ($temp == $value && '2' != $value && '6' != $value) {
                            array_push($classBrandTypes,strval($value));
                        }
                    }
                }
            }
            $params['classBrandTypes'] = json_encode($classBrandTypes);
            $params['categoryId'] = json_encode($params['categoryId']);
            $params['catType'] = json_encode($params['catType']);
        }
        return $this->packageDateDao->postAdAddNew($params);
    }
    
    /**
	 * 查询可添加和编辑包场的运营计划
	 */
	public function getBuyoutDate($param) {
		// 初始化返回结果
		$result = array();
		
		try {
			// 查询数据
			$data['rows'] = $this->packageDateDao->queryBuyoutDate($param);
			
			// 整合最终返回的正确结果
			$result['data'] = $data;
			$result['success'] = true;
			$result['msg'] = '查询成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			// 如果是数据库错误，则另行设定错误编码和信息
			if (230100 < $e->getCode() && 230200 > $e->getCode()) {
				$result['msg'] = $e->getMessage();
				$result['errorCode'] = $e->getCode();
			} else {
				$result['msg'] = '数据库错误，查询失败！';
				$result['errorCode'] = 230099;
			}
        }
        
        // 返回结果
        return $result;
	}
	
	/**
     * 获取首页所有位置的配置信息
     * 
     * @author wenrui 2014-06-05
     */
	public function getIndexAdList($param){
		$bbCityInfo = array();
		// 获取首页所有位置
		$result = $this->packageDateDao->getIndexAdList($param);
		$memcacheKey = md5('getBBCityInfo');
        $finalBeginCityResult = Yii::app()->memcache->get($memcacheKey);
        if(!empty($finalBeginCityResult)){
        	$bbCityInfo = $finalBeginCityResult;
        }else{
        	// 获取所有出发城市
			$startCityList = $this->_iaoProductMod->getMultiCityInfo();
			foreach($startCityList['all'] as $city){
				$bbCityInfo[$city['code']] = $city['name'];
			}
			Yii::app()->memcache->set($memcacheKey, $bbCityInfo, 86400);
        }
		foreach($result['rows'] as &$ad){
			$ad['startCityName'] = $bbCityInfo[$ad['startCityCode']];
		}
		return $result;
	}
	
	/**
     * 添加多个广告位的运营计划new
     * 
     * @author wenrui 2014-06-05
     */
	public function addPakDtList($param){
		$result = $this->packageDateDao->addPakDtList($param);
		return $result;
	}
	
	/**
	 * 添加运营计划
	 * 
	 * @author wenrui 2014-06-05
	 */
	public function addPakDt($param){
        //插入首页全部广告位
        if ('index_chosen_all' == $param['adKey']) {
            // 插入位置信息至数据库
            $this->packageDateDao->insertIndexPosition($param);
        } else {
        	$position = array();
        	switch($param['adKey']){
        		case 'search_complex':$position['ad_name']='搜索页';$position['type_id'] = 3;$position['is_major'] = 0;$position['ad_key_type'] = 3;break;
        		case 'class_recommend':$position['ad_name']='分类页';$position['type_id'] = 1;$position['is_major'] = 0;$position['ad_key_type'] = 2;break;
        		case 'special_subject':$position['ad_name']='专题页';$position['type_id'] = 4;$position['is_major'] = 0;$position['ad_key_type'] = 4;break;
        		case 'brand_zone':$position['ad_name']='品牌专区';$position['type_id'] = 5;$position['is_major'] = 0;$position['ad_key_type'] = 6;break;
        		default :$position['ad_name']='';
        	}
        	$position['ad_key'] = $param['adKey'];
        	$position['floor_price'] = $param['floorPrice'];
        	$position['ad_product_count'] = $param['adProductCount'];
        	$position['coupon_use_percent'] = $param['couponUsePercent'];
        	$position['start_city_code'] = 0;
            // 插入位置信息至数据库
            $posiId = $this->packageDateDao->insertBaadPosition($position, $param['uid'], $param['showDateId']);
            // 判断是否插入成功
            if (!$posiId) {
                return false;
            }
        }
        return true;
	}
	
	/**
	 * 更新运营计划
	 * 
	 * @author wenrui 2014-06-05
	 */
	public function updatePakDt($param){
		// 先判断打包计划是否正在使用
        $bidFlag = $this->packageDateDao->packageIsBided($param);
        if (true == $bidFlag) {
            return false;
        }
	    $result = $this->packageDateDao->updateAdPosition($param);
	    return $result;
	}
    /**
     * 查询打包日期详情
     *
     * @param $params
     * @return array
     */
    public function getShowDateInfo($params) {
        return $this->packageDateDao->getShowDateInfoById($params);
    }

    /**
     * 保存打包计划日期
     *
     * @param $params
     * @return array
     */
    public function saveShowDateInfo($params,$uid) {
        // 避免重复添加相交推广日期的运营计划
        $existShowDateId = '';
        $existShowDateInfo = $this->packageDateDao->existShowDateInfo($params);
        if ($existShowDateInfo) {
            foreach ($existShowDateInfo as $temp) {
                $existShowDateId .= $temp['id'] . ' ';
            }
            return array('existShowDateId' =>$existShowDateId);
        }
    	// 新配置打包计划
    	if (!empty($params['isHaveFlag']) && !empty($params['copyId'])) {
    		// 插入打包时间信息
            $dateId = $this->packageDateDao->insertPackageInfo($params, $uid);
            // 判断是否插入成功
            if ($dateId) {
                $this->packageDateDao->copyPositionData($params, $dateId, $uid);
            }
    	} else if (0 === $params['updateFlag']) {
            // 更新打包时间信息
            $dateId = $this->packageDateDao->updatePackageInfo($params, $uid);
            // 判断是否更新成功
            if ($dateId) {
                return $this->getShowDateInfo($dateId);
            }
        } else {
            // 插入打包时间信息
            $dateId = $this->packageDateDao->insertPackageInfo($params, $uid);
            // 判断是否插入成功
            if ($dateId) {
                return $this->getShowDateInfo($dateId);
            }
        }
    }

    /**
     * 查询广告位置信息
     *
     * @param $params
     * @return array
     */
    public function getAdPositionInfo($params) {
        $allAdKeyInfo = $this->productMod->getPositionType();
        $result = array();
        if ($allAdKeyInfo) {
            foreach ($allAdKeyInfo as $temp) {
                $AdPositionInfo = $this->packageDateDao->getAdPositionInfo($temp['adKey'],$params['showDateId']);
                if ($AdPositionInfo) {
                    array_push($result,$AdPositionInfo);
                }
            }
        }
        return $result;
    }
    
    /**
	 * 查询打包计划产品详情
	 */
	public function updatePackOpenStatus($param) {
		// 初始化返回结果
		$result = array();
		
		try {
			
			// 更新打包计划打开关闭状态
			$this->packageDateDao->updatePackOpenStatus($param);
			
			// 整合最终返回的正确结果
			$result['data'] = array();
			$result['success'] = true;
			$result['msg'] = '查询成功！';
			$result['errorCode'] = 230000;
			
		} catch (Exception $e) {
    		// 打印错误日志
            Yii::log($e);
            // 整合错误结果
            $result['data'] = array();
			$result['success'] = false;
			$result['msg'] = $e->getMessage();
			$result['errorCode'] = $e->getCode();
        }
        
        // 返回结果
        return $result; 
	}
    
}
?>
