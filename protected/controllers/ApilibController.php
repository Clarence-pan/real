<?php
/**
 * Coypright (C) 2012 Tuniu All rights reserved
 * Author: wuhuanhong
 * Date: 2013-01-14
 * API Lib 接口文档生成的REST Controller, 用于Yii框架
 * Note: 添加端口映射时的文件目录，需要以APILibController所在目录为相对目录，不然可能会找不到文件
 */
class APILibController extends ERestController {

    private $response;

    function doCustomRestPostDoc($params) {
        $fp = $this->open_file(dirname(__FILE__) . '/../runtime/interface_doc.log');
        $fp_err = $this->open_file(dirname(__FILE__) . '/../runtime/interface_doc_error.log');
        try {
            fwrite($fp, date('Y-m-d H:i:s') . " request_data :" . json_encode($params) . "\r\n");
            $directories = $params['dirs'];
            if (!empty($directories) && is_array($directories)) {
                foreach ($directories as $interface) {
                    $this->get_interface_content($interface, $fp, $fp_err);
                }
            }
            fwrite($fp, "response_data : " . json_encode($this->response) . "\r\n");
            if(!$this->response){
                $this->response = new stdClass();
            }
            $this->_returnData = $this->response;
            $this->renderJson();
        } catch (restful_exception $e) {
            $error_str = date('Y-m-d H:i:s') . " error code:" . $e->getCode() . "! exception '" . __CLASS__ . "' with message '" . $e->getMessage() . "' in " . $e->getFile() . ":" . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString() . "\r\n";
            fwrite($fp_err, $error_str);
        }
        fclose($fp);
        fclose($fp_err);
    }

    public function open_file($str_filePath) {
        $strDir = dirname($str_filePath);
        if (!is_dir($strDir)) {
            mkdir($strDir, 0777, true);
        }
        return fopen($str_filePath, 'a+');
    }

    public function get_interface_content($interface, $fp, $fp_err) {
        $path = dirname(__FILE__) . '/' . $interface['file_directory'];
        try {
            $path_parts = pathinfo($interface['file_directory']);
            if ('php' == strtolower($path_parts['extension'])) {
                if (!file_exists($path)) {
                    $error_str = "文件：'$path'不存在\r\n";
                    fwrite($fp_err, $error_str);
                    return;
                }
                //by liubaozhong 2012-07-04
                $dirname = $path_parts['dirname'];
                chdir(dirname(__FILE__) . '/' . $dirname);
                require_once($path);
                chdir(dirname(__FILE__)); // change back to previous working dir

                if (class_exists($path_parts['filename'])) {
                    $class = $path_parts['filename'];
                    $reflector = new ReflectionClass($path_parts['filename']);
                    $docs = $reflector->getDocComment();
                    $docs = explode("\n", $docs);
                    $author = $version = $desc = $example = $help = "";
                    $notes = $func = array();
                    foreach ($docs as $doc) {
                        $doc = trim($doc, " \r\t/*");
                        if (strlen($doc) && strpos($doc, '@') !== 0) {
                            $help .= $doc . "\n";
                            continue;
                        }
                        if (preg_match('/@(\S*)( )*(.*)/', $doc, $matches)) {
                            switch ($matches[1]) {
                                case "author":
                                    $author = $matches[3];
                                    break;
                                case "version":
                                    $version = $matches[3];
                                    break;
                                case "func":
                                    $func[] = trim($matches[3]);
                                    break;
                                case "description":
                                    $desc = $matches[3];
                                    break;
                                case "desc":
                                    $desc = $matches[3];
                                    break;
                                case "example":
                                    $example = $matches[3];
                                    break;
                                default:
                                    $notes[$matches[1]] = $matches[3];
                            }
                        }
                    }
                    $desc = $help . $desc;
                    $func = $this->get_method_docs($func, $reflector);
                    $this->response[$interface['id']] = compact("class", "author", "version", "desc", "example", "notes", "func");
                }
            }
        } catch (Exception $e) {
            $error_str = date('Y-m-d H:i:s') . " error code:" . $e->getCode() . "! exception '" . __CLASS__ . "' with message '" . $e->getMessage() . "' in " . $e->getFile() . ":" . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString() . "\r\n";
            fwrite($fp_err, $error_str);
        }
    }

    public function get_method_docs($func, $reflector) {
        $funclist = array();
        foreach ($func as $value) {
            $name = $method = $return = $desc = $example = $help = "";
            $name = $value;

            //by liubaozhong 2012-07-05
            try {
                //当该类不存在该方法时，会抛出ReflectionException异常，所以必须捕获异常。
                $value = $reflector->getMethod($value);
            } catch (ReflectionException $e) {
                continue;
            }

            if (empty($value)) {
                continue;
            }
            if ($value->name == "__construct") {
                continue;
            }
            $return = array("type" => "mixed");
            $permission = "protected";
            $paramDocs = $params = $notes = array();
            $docs = $value->getDocComment();
            $docs = explode("\n", $docs);
            foreach ($docs as $doc) {
                $doc = trim($doc, " \r\t/*");
                if (strlen($doc) && strpos($doc, '@') !== 0) {
                    $help .= $doc . "\n";
                    continue;
                }
                if (preg_match('/@(\S*)( )*(.*)/', $doc, $matches)) {
                    switch ($matches[1]) {
                        case "method":
                            $method = $matches[3];
                            break;
                        case "mapping":
                            $mapping = $matches[3];
                            break;
                        case "param":
                            $paramDocs[] = $doc;
                            break;
                        case "return":
                            if (preg_match('/@return\s+(\S+)(\s+(.+))/', $doc, $matches2)) {
                                $return['type'] = $matches2[1];
                                $return['desc'] = $matches2[3];
                            } else {
                                $param = preg_split("/\s+/", $doc);
                                if (isset($param[1])) {
                                    $return['type'] = $matches[1];
                                }
                            }
                            break;
                        case "description":
                            $desc = $matches[3];
                            break;
                        case "desc":
                            $desc = $matches[3];
                            break;
                        default:
                            $notes[$matches[1]] = $matches[3];
                    }
                }
            }
//			$method = $value->getName();
            foreach ($value->getParameters() as $parameterIndex => $parameter) {
                // Parameter defaults
                $newParameter = array('type' => 'mixed');

                // Attempt to extract type and doc from docblock
                if (array_key_exists($parameterIndex, $paramDocs) &&
                        preg_match('/@param\s+(\S+)(\s+(.+))/', $paramDocs[$parameterIndex], $matches)) {
                    if (strpos($matches[1], '|')) {
                        $newParameter['type'] = self::_limitPHPType(explode('|', $matches[1]));
                    } else {
                        $newParameter['type'] = self::_limitPHPType($matches[1]);
                    }
                    $tmp = '$' . $parameter->getName() . ' ';
                    if (strpos($matches[2], '$' . $tmp) === 0) {
                        $newParameter['desc'] = $matches[2];
                    } else {
                        // The phpdoc comment is something like "@param string $param description of param"    
                        // Let's keep only "description of param" as documentation (remove $param)
                        $newParameter['desc'] = substr($matches[2], strlen($tmp));
                    }
                }
                $newParameter['name'] = $parameter->getName();
                $params[] = $newParameter;
            }
            $desc = $help . $desc;
            $funclist[] = compact("name", "method", "params", "return", "desc", "example", "notes", "mapping");
        }
        return $funclist;
    }

    // }}}
    // {{{ _limitPHPType()
    /**
     * standardise type names between gettype php function and phpdoc comments (and limit to xmlrpc available types)
     * 
     * @var string $type
     * @return string standardised type
     */
    private static function _limitPHPType($type) {
        $tmp = strtolower($type);
        $convertArray = array(
            'int' => 'integer',
            'i4' => 'integer',
            'integer' => 'integer',
            'string' => 'string',
            'str' => 'string',
            'char' => 'string',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'float' => 'double',
            'double' => 'double',
            'array' => 'array',
            'struct' => 'array',
            'assoc' => 'array',
            'structure' => 'array',
            'datetime' => 'mixed',
            'datetime.iso8601' => 'mixed',
            'iso8601' => 'mixed',
            'base64' => 'string'
        );
        if (isset($convertArray[$tmp])) {
            return $convertArray[$tmp];
        }
        return 'mixed';
    }
}