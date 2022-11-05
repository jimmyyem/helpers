<?php

require 'vendor/jimmyyem/helpers/autload.php';

try {
    throw new Exception('自定义错误', 1000);
} catch (Exception $exception) {
    $dingtalk = new Dingtalk();
    $dingtalk->pushText($exception);
}