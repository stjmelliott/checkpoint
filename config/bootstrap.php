<?php
// /config/bootstrap.php — Updated for Task 5.1
date_default_timezone_set('UTC');

// Core infrastructure
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/credentials.php';

// Task 5.1 service classes
require_once __DIR__ . '/../lib/Logger.php';
require_once __DIR__ . '/../lib/TokenService.php';
require_once __DIR__ . '/../lib/LocationService.php';
require_once __DIR__ . '/../lib/PingValidator.php';
require_once __DIR__ . '/../lib/SecretManager.php';

// Full error handlers (Task 4)
set_error_handler(function($errno, $errstr, $file, $line) {
    Logger::write('error.log', 'ERROR', 'PHP error', ['errno'=>$errno,'msg'=>$errstr,'file'=>basename($file),'line'=>$line]);
    return true;
});
set_exception_handler(function($e) {
    Logger::write('error.log', 'FATAL', 'Uncaught exception', ['class'=>get_class($e),'msg'=>$e->getMessage(),'file'=>basename($e->getFile()),'line'=>$e->getLine()]);
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Internal server error']);
    exit;
});
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR])) {
        Logger::write('error.log', 'FATAL', 'PHP fatal shutdown error', ['msg'=>$err['message'],'file'=>basename($err['file']),'line'=>$err['line']]);
    }
});
?>
