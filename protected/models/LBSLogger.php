<?php
/**
 * 本服务类仅提供测试环境中用于监控的用途，生产环境并无部署
 */

/**
 * Coypright © 2013 Neilsen Chan. All rights reserved.
 * Author: chenjinlong
 * Date: 5/25/13
 * Time: 3:06 PM
 * Description: LBSLogger.php
 * The Brooder Of LBS Series, clone from Github.com
 * @ref https://github.com/neilsenchan/brooder/tree/master/ext
 */
/*
Customize the directory where log files save. Only supports the absolute path currently.
Example:
    2013-05-01 00:00:00 [Logging Title Here] put contents after title words
Using demo:
LBSLogger::logging("RestInvoking",'Outer-API','外部数据接口调用','',array(
    'url' => $uri,
    'http_method' => $method,
    'get_parameter' => base64_decode($params),
    'response' => base64_decode($response),
));
*/
define('LOG_FILE_FOLDER', '/opt/tuniu/www/logs/buckbeek/');
define('LOG_FILE_PREFIX', 'BB-' . date('Ymd') . '-');

class LBSLogger 
{
    /**
     * @desc Query the whole log file lists
     *
     * @return array
     */
    public static function getLoggingFileList()
    {
        $fileArray = array();
        //Open Handle
        if (false != ($handle = opendir ( LOG_FILE_FOLDER ))) {
            $i=0;
            while ( false !== ($file = readdir ( $handle )) ) {
                //remove "“.”、“..” but “.xxx” postfix files
                if ($file != "." && $file != ".."
                        && is_numeric(strpos($file,".log"))
                        && is_numeric(strpos($file, LOG_FILE_PREFIX))) {
                    $fileArray[$i]= LOG_FILE_FOLDER . $file;
                    if($i==100){
                        break;
                    }
                    $i++;
                }
            }
            //Close Handle
            closedir ( $handle );
        }
        return $fileArray;
    }

    /**
     * @desc Do an actually logging job
     *
     * @param $fileName
     * @param $catType
     * @param $title
     * @param $notes
     * @param string $contents
     */
    public static function logging($fileName, $catType, $title, $notes, $contents='')
    {
        $fileDir = LOG_FILE_FOLDER . LOG_FILE_PREFIX . strval($fileName) . '.log';

        $curTime = date('Y-m-d H:i:s');
        $catType = ' [' . strval($catType) . ']';
        $title = '[' . strval($title) . '] ';
        $notes = strval($notes);
        file_put_contents($fileDir, strval($curTime) . $catType . $title . $notes . PHP_EOL, FILE_APPEND);
        file_put_contents($fileDir, print_r($contents, true) . PHP_EOL, FILE_APPEND);
    }

    /**
     * @desc Clear contents of a customize log file
     *
     * @param $fileName
     */
    public static function clearLogContents($fileName)
    {
        $fileDir = LOG_FILE_FOLDER . 'LBSLogger-' . strval($fileName) . '.log';
        file_put_contents($fileDir, '');
    }

    /**
     * @desc Unlink(delete) a customize log file
     *
     * @param $fileName
     */
    public static function deleteLogFile($fileName)
    {
        $fileDir = LOG_FILE_FOLDER . 'LBSLogger-' . strval($fileName) . '.log';
        if(file_exists($fileDir)){
            unlink($fileDir);
            echo 'File "' . $fileDir . '" has been deleted!' . '<br />';
        }else{
            echo 'File "' . $fileDir . '" doesnt exist!';
        }
    }

    /**
     * @see LBSLogger::getLoggingFileList()
     */
    public static function deleteTotalLogFile()
    {
        $fileDir = self::getLoggingFileList();
        if(!empty($fileDir) && is_array($fileDir)){
            foreach($fileDir as $dir)
            {
                if(file_exists($fileDir)){
                    unlink($fileDir);
                }else{
                    echo 'File "' . $fileDir . '" doesnt exist!' . '<br />';
                    continue;
                }
                echo 'File "' . $fileDir . '" has been deleted!' . '<br />';
            }
        }else{
            echo 'The Logging folder "' . LOG_FILE_FOLDER . '" doesnt have any log file!' . '<br />';
        }
    }

}