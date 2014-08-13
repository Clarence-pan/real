<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/24/12
 * Time: 3:25 PM
 * Description: StaUrlBuilderMod.php
 */
Yii::import('application.modules.bidmanage.models.iao.IaoProductMod');

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

    private $_iaoProductMod;

    function __construct()
    {
        $this->_iaoProductMod = new IaoProductMod;
        $this->_departureCitySet = $this->getValidDepartureCityCodeSet();
    }

    public function getValidDepartureCityCodeSet()
    {
        return array(
            '200' => 'bj',
            '2500' => 'sh',
            '1602' => 'nj',
            '3402' => 'hz',
            '1615' => 'sz',
            '3000' => 'tj',
            '619' => 'shz',
            '2802' => 'cd',
            '1402' => 'wh',
            '1902' => 'sy',
            '300' => 'cq',
            '3415' => 'nb',
            '2702' => 'xa',
            '1619' => 'wx',
        );
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
        
        $productInfoArr = $this->_iaoProductMod->getProductInfoArr(array(array('productId'=>$productIdSplit[1],'productType'=>$this->_productType)));
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
