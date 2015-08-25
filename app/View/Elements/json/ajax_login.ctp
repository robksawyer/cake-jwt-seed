<?php
$returnObject=array();
$returnObject['status'] = 'error';
$returnObject['code'] = 403;
$returnObject['message'] = 'Not authorized to access this location, please login';
echo json_encode($returnObject);