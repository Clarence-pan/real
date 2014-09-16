<table>
    <tr>
        <td>
            <form method="post">
                <textarea name="b" cols="100" rows="10"><?php
$req_string = trim($_POST['b']);
echo $req_string;
?></textarea>
                <br/>
                <input type='submit' value='GET' name="go"/>
                <input type='submit' value='POST' name="go"/>
                <input type='submit' value='PUT' name="go"/>
                <input type='submit' value='DELETE' name="go"/>
                json显示：<input type='checkbox' name="JSON" <?php
                    $is_show_json = $_POST['JSON'] ? 'checked' : '';
                    echo $is_show_json;
?>/>
                <br/>


            </form>
        </td>
        <td>

            使用说明：
            <br/>1.  http:// 开头自动启用restful访问;可以使用按钮控制访问方法;url?后面可以为base64格式数据或者json格式数据
            <br/>2.  {或者 [ 开头自动启用json解析
            <br/>3.  array 开头自动启用 php数组解析
            <br/>4.  其他情况 均进行base64解码
            <br/>5.  默认以php array var_dump形式呈现数据，选中“json显示”后变更为json格式，
        </td>
    </tr>
</table>

<?php

header("Content-type:text/html; charset=utf-8");

require_once 'whh.php';
require_once 'restful.cls.php';

function show($obj) {
    $is_show_json = $_POST['JSON'];
    if (is_string($obj)) {
        echo $obj;
    } else if ($is_show_json) {
        echo'<pre>', indent(ch_json_encode($obj)), '<pre>';
    } else {
        whh_dump($obj);
    }
}

function restful_request($req_string) {
    $uri_aray = explode('?', $req_string);
    $uri_param = trim($uri_aray[1]);
    if (strpos($uri_param, '{') !== false) {
        $param = json_decode($uri_param, true);
        show(base64_encode($uri_param));
    } else {
        $param = json_decode(base64_decode($uri_param), true);
        show($uri_param);
    }

    $rest = new restful_client();
    $method = $_REQUEST['go'];

    show($param);
    $start = explode(' ', microtime());
    $startTime = (floatval($start[1]) + floatval($start[0]));
    $result = $rest->request($method, $uri_aray[0], $param);
    $end = explode(' ', microtime());
    $endTime = (floatval($end[1]) + floatval($end[0]));
    $runtime = $endTime - $startTime;
    if ($runtime > 0.05) {
        echo '<font color="#cc0000"><p>' . $startTime . ' -> ' . $endTime . '  =  ' . $runtime . '</font>';
    } else {
        echo '<p>' . $startTime . ' -> ' . $endTime . '  =  ' . ($runtime);
    }
    return $result;
}

function json_request($req_string) {
    $result = json_decode($req_string, true);
    show(base64_encode($req_string));
    return $result;
}

function array_request($req_string) {
//    $r1 = rand(0, 9);
//    $r10 = rand(10, 99);
//
//    $req_string = preg_replace('/\[|\]/', "'", $req_string);
//    $req_string = preg_replace('/=>\s[^A]/', "=> '',", $req_string);
//    $req_string = preg_replace('/\)/', "),", $req_string);
//    $req_string = preg_replace("/(Id|'start|'limit|'count|'uid)'\s=>\s''/", "$1'=> $r10", $req_string);
//    $req_string = preg_replace("/(('is\w*)|Type|State|Enabled|Flag)'\s=>\s''/", "$1'=> $r1", $req_string);
//    //$b = preg_replace("/\s=>\s''/", "$1'=> $r", $b);
//    $req_string = trim($req_string, ',');
//    echo $b;
    $uri_aray = '$result=' . $req_string . ';';
    eval($uri_aray);
    show(base64_encode(json_encode($result,true)));
    return $result;
}

function base64_request($req_string) {
    $param_json = base64_decode($req_string);
//    echo '<pre>', $param_json, '<pre>';
    $result = json_decode($param_json, true);
    return $result;
}

if (strpos($req_string, 'http://') === 0) {
    $result = restful_request($req_string);
} else if (strpos($req_string, '{') === 0 || strpos($req_string, '[') === 0) {
    $result = json_request($req_string);
} else if (strpos(strtolower($req_string), 'array') === 0) {
    $result = array_request($req_string);
} else {
    $result = base64_request($req_string);
}

show($result);
