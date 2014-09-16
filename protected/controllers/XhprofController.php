<?php

class XhprofController extends CController{
    function actionIndex(){
        $xhprof = file_get_contents('/opt/tuniu/www/buckbeek/assets/bb_xhprof_log.txt');
        $xhprof_array = explode("\n", $xhprof);
        foreach ($xhprof_array as $x) {
            if(!$x ){
                continue;
            }

            if( strpos($x,'http') === false){
                echo "$x<br/>";
            }
            else{
                echo "<a href='$x' target='_blank'>$x</a><br/>";
            }
        }
       
    }
    
    function actionClear(){
        file_put_contents('/opt/tuniu/www/buckbeek/assets/bb_xhprof_log.txt', '');
    }
}