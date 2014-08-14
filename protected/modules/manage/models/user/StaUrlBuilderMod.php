<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/24/12
 * Time: 3:25 PM
 * Description: StaUrlBuilderMod.php
 */
Yii::import('application.modules.manage.dal.iao.BuckbeekIao');

class StaUrlBuilderMod
{
	private $_bidShowProductId;
	
    private $_productId;
    
    private $_productType;

    private $_adKey;

    private $_startCityCode;

    private $_catType;

    private $_webClass;

    private $_urlPrefixStr = '';
    private $_urlPostfixStr = '';

    private $_departureCitySet = array();

    function __construct()
    {
        $this->_departureCitySet = $this->getValidDepartureCityCodeSet();
    }

    /**
     * 招客宝改版-查询出发城市静态数据
     *
     * @author chenjinlong 20131121
     * @return array
     */
    public function getValidDepartureCityCodeSet()
    {
        $departureCities = BuckbeekIao::getDepartureCities();
        $citiesRows = array();
        foreach($departureCities as $row)
        {
            $citiesRows[$row['code']] = $row['letter'];
        }
        return $citiesRows;
    }

    public function buildUrlParameterArr($in)
    {
        $this->initProperties();
        
        $this->_bidShowProductId = $in['bid_show_product_id'];
        $this->_productId = $in['product_type']==33?'t_'.$in['product_id']:'p_'.$in['product_id'];
        $this->_productType = $in['product_type'];
        $this->_adKey = $in['ad_key'];
        $this->_startCityCode = $in['start_city_code'];
        $this->_catType = $in['cat_type'];
        $this->_webClass = $in['web_class'];
    }

    public function outputTrackedUrlString()
    {
        if(in_array($this->_startCityCode, array_keys($this->_departureCitySet))){
            $urlArr = array();
            //$urlArr[] = $this->getRouteUrl();
            switch($this->_adKey){
                case 'index_chosen':
                    $urlArr[$this->_bidShowProductId] = $this->getIndexCoreUrl();
                    break;
                case 'channel_hot':
                    $urlArr[$this->_bidShowProductId] = $this->getChannelHotUrl();
                    break;
                case 'class_recommend':
                    $urlArr[$this->_bidShowProductId] = $this->getSiteClsUrl();
                    break;
                default:
                    break;
            }
            return $urlArr;
        }else{
            return array();
        }
    }

    /*public function getRouteUrl()
    {
        $this->buildUrlCommon();

        return $this->_urlPrefixStr.'/tours/'.$this->_productId;
    }*/

    public function getIndexCoreUrl()
    {
        $this->buildUrlCommon();

        return $this->_urlPrefixStr;
    }

    public function getChannelHotUrl()
    {
        $this->buildUrlCommon();

        switch($this->_catType){
            case 1:
                $this->_urlPostfixStr = '/around';
                break;
            case 2:
                $this->_urlPostfixStr = '/domestic';
                break;
            case 3:
                $this->_urlPostfixStr = '/abroad';
                break;
            default:
                break;
        }
        return $this->_urlPrefixStr.$this->_urlPostfixStr;
    }

    public function getSiteClsUrl()
    {
        $this->buildUrlCommon();
        $productIdSplit = explode('_', $this->_productId);
        
        $productInfoArr = BuckbeekIao::getProductClassification(array(array('productId'=>$productIdSplit[1],'productType'=>$this->_productType)));
        $webClassArr = $productInfoArr[$this->_productId]['category'];
        if(!empty($webClassArr)){
            foreach($webClassArr as $cls)
            {
                if($cls['id'] == $this->_webClass){
                    $this->_urlPostfixStr = '/'.$cls['page_url'];
                    break;
                }else{
                    continue;
                }
            }
        }          
        return $this->_urlPrefixStr.$this->_urlPostfixStr;
    }

    private function initProperties()
    {
        $this->_urlPrefixStr = '';
        $this->_urlPostfixStr = '';
        $this->_productId = 0;
        $this->_bidShowProductId = 0;
        $this->_startCityCode = 0;
        $this->_catType = 0;
        $this->_webClass = 0;
    }

    private function buildUrlCommon()
    {
        $this->_urlPrefixStr = 'http://'.$this->_departureCitySet[$this->_startCityCode].'.tuniu.com';
    }

}
