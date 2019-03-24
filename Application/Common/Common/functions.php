<?php

function du($data, $exit = true)
{

    echo '<pre>';
    var_dump($data);
    if ($exit) {
        exit;
    }
}

function pr($data, $exit = true)
{

    echo '<pre>';
    print_r($data);
    if ($exit) {
        exit;
    }
}

// 取得配置数据的值
// conf('conf,region,name')
function conf($data, $path = '')
{

    if (empty($data)) {
        return false;
    }
    $data = explode(',', $data);
    $data = array_map('trim', $data);
    $path = !empty($path) ? $path . $data[0] . '.php' : $_SERVER['DOCUMENT_ROOT'] . '/Application/' . MODULE_NAME . '/Conf/' . $data[0] . '.php';
    if (!is_file($path)) {
        return false;
    }
    $tmp = returnIncluded($path);
    array_shift($data);
    foreach ($data as $v) {
        if (!isset($tmp[$v])) {
            return false;
        }
        $tmp = $tmp[$v];
    }
    unset($data, $path);
    return $tmp;
}

/**
 * 返回include之后的数据
 * @param $file 地址
 * @return (string/array/object)
 */
function returnIncluded($file)
{

    return include $file;
}

/**
 * 上传文件类型控制 此方法仅限ajax上传使用
 * @param  string $path 字符串 保存文件路径示例： /upload/image/
 * @param  string $format 文件格式限制
 * @param  integer $maxSize 允许的上传文件最大值 52428800
 * @return booler   返回ajax的json格式数据
 */
function ajaxUpload($path = 'file', $format = 'empty', $type = 3, $maxSize = '52428800')
{
    ini_set('max_execution_time', '0');
    // 去除两边的/
    $path = trim($path, '/');
    // 添加Upload根目录
    $path = strtolower(substr($path, 0, 6)) === 'upload' ? ucfirst($path) : 'Upload/' . $path;
    // 上传文件类型控制
    $ext_arr = array(
        'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
        'photo' => array('jpg', 'jpeg', 'png'),
        'flash' => array('swf', 'flv'),
        'media' => array('swf', 'flv', 'mp3', 'mp4', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
        'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2', 'pdf')
    );
    if (!empty($_FILES)) {
        // 上传文件配置
        $config = array(
            'maxSize' => $maxSize,               // 上传文件最大为50M
            'rootPath' => './',                   // 文件上传保存的根路径
            'savePath' => './' . $path . '/',         // 文件上传的保存路径（相对于根路径）
            'saveName' => array('uniqid', ''),     // 上传文件的保存规则，支持数组和字符串方式定义
            'autoSub' => true,                   // 自动使用子目录保存上传文件 默认为true
            'exts' => isset($ext_arr[$format]) ? $ext_arr[$format] : '',
        );
        // p($_FILES);
        // 实例化上传
        $upload = new \Think\Upload($config);
        // 调用上传方法
        $info = $upload->upload();
        // p($info);
        $data = array();
        if (!$info) {
            // 返回错误信息
            $error = $upload->getError();
            $data['error_info'] = $error;
            echo json_encode($data);
        } else {
            // 返回成功信息
            foreach ($info as $key => $file) {
                $data['name'] = trim($file['savepath'] . $file['savename'], '.');
                $data['type'] = $type;
                echo json_encode($data);
            }
        }
    }
}

function select_domain()
{

    $type = ['admin' => 1, 'course' => 2];
    $tmp = explode('.', $_SERVER['HTTP_HOST']);
    if (!count($tmp)) {
        return $type['admin'];
    }
    if (!in_array($tmp[0], ['admin', 'course'])) {
        return $type['admin'];
    }
    return $type[$tmp[0]];
}
