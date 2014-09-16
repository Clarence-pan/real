<?php
// nbooking
class NBookingIao {

    public static function getNBAccount($params) {
        $client = new RESTClient();
        $url = Yii::app()->params['NB_HOST'] . 'restful/login/query-account';
        $responseArr = $client->post($url, $params);
        return $responseArr;
    }

}

?>
