<?php

require 'vendor/jimmyyem/helpers/autload.php';

try {
    $a = 11;
    echo $a[1];
} catch (Exception $exception) {
    $dingtalk = new Dingtalk();
    $dingtalk->pushPlain($exception);
}