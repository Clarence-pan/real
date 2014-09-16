<?php

function whh_dump($value, $level = 0) {
    if ($level == -1) {
        $trans[' '] = '&there4;';
        $trans["\t"] = '&rArr;';
        $trans["\n"] = '&para;;';
        $trans["\r"] = '&lArr;';
        $trans["\0"] = '&oplus;';
        return strtr(htmlspecialchars($value), $trans);
    }

    if ($level == 0)
        echo '<pre>';
    $type = gettype($value);
    if ($type == 'NULL') {
        $type = strtolower($type);
        echo "<font color= '#3465a4'> $type</font>";
    } else {
        echo " $type";
    }
    if ($type == 'string') {
        echo '(' . strlen($value) . ')';
        $value = whh_dump($value, -1);
        echo "<font color= '#cc0000'> '$value'</font>";
    } elseif ($type == 'boolean') {
        $value = ($value ? 'true' : 'false');
        echo "<font color= '#75507b'> $value</font>";
    } elseif ($type == 'integer') {
        echo "<font color= '#4e9a06'> $value</font>";
    } elseif ($type == 'double') {
        echo "<font color= '#4e9a06'> $value</font>";
    } elseif ($type == 'object') {
        $props = get_class_vars(get_class($value));
        echo '(' . count($props) . ') <u>' . get_class($value) . '</u>';
        foreach ($props as $key => $val) {
            echo "\n" . str_repeat("  ", $level + 1) . $key . ' => ';
            whh_dump($value->$key, $level + 1);
        }
        $value = '';
    } elseif ($type == 'array') {
        echo '(' . count($value) . ')';
        foreach ($value as $key => $val) {
            echo "\n" . str_repeat("   ", $level + 1) . "" . dump_key($key, -1) . " => ";
            whh_dump($val, $level + 1);
        }
        $value = '';
    }

    if ($level == 0)
        echo '</pre>';
}

function dump_key($value) {
    $type = gettype($value);
    if ($type == 'string') {
        return " '$value'";
    } elseif ($type == 'integer') {
        return "<font color= '#4e9a06'> $value</font>";
    } else {
        return $value;
    }
}

/**
 * Indents a flat JSON string to make it more human-readable.
 * @param string $json The original JSON string to process.
 * @return string Indented version of the original JSON string.
 */
function indent($json) {

    $result = '';
    $pos = 0;
    $strLen = strlen($json);
    $indentStr = '    ';
    $newLine = "\n";
    $prevChar = '';
    $outOfQuotes = true;

    for ($i = 0; $i <= $strLen; $i++) {

// Grab the next character in the string.
        $char = substr($json, $i, 1);
// Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;
// If this character is the end of an element,
// output a new line and indent the next line.
        } else if (($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos--;
            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }
// Add the character to the result string.
        $result .= $char;
// If the last character was the beginning of an element,
// output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos++;
            }
            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }
        $prevChar = $char;
    }

    return $result;
}

/**
 * json 编码
 * 
 * 解决 json_encode() 不支持中文的情况
 * 
 * @param array|object $data
 * @return array|object
 */
function ch_json_encode($data) {

    if(!function_exists('ch_urlencode')) {

        /**
         * 将中文编码
         * @param array $data
         * @return string
         */
        function ch_urlencode($data) {
            if (is_array($data) || is_object($data)) {
                foreach ($data as $k => $v) {
                    if (is_scalar($v)) {
                        if (is_array($data)) {
                            $data[$k] = urlencode($v);
                        } else if (is_object($data)) {
                            $data->$k = urlencode($v);
                        }
                    } else if (is_array($data)) {
                        $data[$k] = ch_urlencode($v); //递归调用该函数
                    } else if (is_object($data)) {
                        $data->$k = ch_urlencode($v);
                    }
                }
            }
            return $data;
        }

    }

    $ret = ch_urlencode($data);
    $ret = json_encode($ret);
    return urldecode($ret);
}