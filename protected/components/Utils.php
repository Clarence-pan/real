<?php
class Utils {
    public static function getClientIp() {
        global $_SERVER;
        
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && ($_SERVER['HTTP_VIA'] != 'unknown')) {
            $realIp = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_CLIENT_IP"]) && ($_SERVER['HTTP_CLIENT_IP'] != 'unknown')) {
            $realIp = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $realIp = $_SERVER["REMOTE_ADDR"];
        }
        
        //有时候ip地址会还有多个值，特别是使用HTTP_X_FORWARDED_FOR这个变量时，如"127.0.0.1,88.20.18.134"
        //多个ip地址时，取第一个不是内网的ip（192.168, 172.16., 10., 127.0.0.1）
        if (strstr($realIp, ",")) {
            $ipArray = split(",", $realIp);
            $ipArrayCount = count($ipArray);
            for ($i = 0; $i < $ipArrayCount; $i++) {
                if (($ipArray[$i] != "127.0.0.1") && (substr($ipArray[$i], 0, 3) != "10.")
                        && (substr($ipArray[$i], 0, 7) != "172.16.") && (substr($ipArray[$i], 0, 7) != "192.168")) {
                    $realIp = $ipArray[$i];
                    break;
                }
            }
        }
        return $realIp;
    }
    
    /**
     * 根据文件类型获取MIME类型
     * @author zhangzheng
     * @param string $fileType
     * @return string $contentType
     */
    public static function getContentType($fileType) {
        $contentType = 'text/html';
        
        if (empty($fileType)) {
            return $contentType;
        }
        
        switch ($fileType) {
            case 'html':
                $contentType = 'text/html';
                break;
            case 'txt':
                $contentType = 'text/plain';
                break;
            case 'pdf':
                $contentType = 'application/pdf';
                break;
            case 'doc':
                $contentType = 'application/msword';
                break;
            case 'docx':
                $contentType = 'application/msword';
                break;
            case 'png':
                $contentType = 'image/png';
                break;
            case 'gif':
                $contentType = 'image/gif';
                break;
            case 'jpg':
                $contentType = 'image/jpeg';
                break;
            case 'jpeg':
                $contentType = 'image/jpeg';
                break;
            case 'bmp':
                $contentType = 'application/x-MS-bmp';
                break;
            case 'tif':
                $contentType = 'image/tiff';
                break;
            case 'tiff':
                $contentType = 'image/tiff';
                break;
            case 'xls':
                $contentType = 'application/vnd.ms-excel';
                break;
            case 'xlsx':
                $contentType = 'application/vnd.ms-excel';
                break;
            case 'zip':
                $contentType = 'application/zip';
                break;
            default:
                $contentType = 'text/html';
                break;
        }
        
        return $contentType;
    }
}
?>