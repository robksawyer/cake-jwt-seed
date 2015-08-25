<?php
$returnObject = array();
$returnObject['status'] = 'redirect';
$returnObject['code'] = 302;
$returnObject['redirectUrl'] = $redirectUrl;
$returnObject['message'] = $message;
echo json_encode($returnObject);
