<?php

// Temporary local browser QA server; never targets the working database or file disks.
$root=dirname(__DIR__);
$receipt=json_decode(file_get_contents($root.'/tmp/mysql-test-target.json'),true);
$name=$receipt['database']??'';
if(!preg_match('/^foundation_management_test_[0-9]{14}_[a-f0-9]{4}$/',$name)||($_SERVER['SERVER_PORT']??'')!=='8011'){http_response_code(503);exit('QA target unavailable.');}
foreach(['DB_CONNECTION'=>'mysql','DB_DATABASE'=>$name,'APP_URL'=>'http://127.0.0.1:8011','APP_ENV'=>'local','SESSION_SECURE_COOKIE'=>'false','CACHE_STORE'=>'array','SESSION_COOKIE'=>'foundation_qa_session']as$key=>$value){putenv($key.'='.$value);$_ENV[$key]=$value;$_SERVER[$key]=$value;}
$path=realpath($root.'/public'.parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));
if($path&&str_starts_with($path,realpath($root.'/public').DIRECTORY_SEPARATOR)&&is_file($path))return false;
require $root.'/vendor/autoload.php';$app=require $root.'/bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);$kernel->bootstrap();
if(config('database.default')!=='mysql'||config('database.connections.mysql.database')!==$name){http_response_code(503);exit('QA database guard failed.');}
foreach(['private','public','local']as$disk)config(['filesystems.disks.'.$disk.'.root'=>$root.'/storage/framework/testing/qa-browser/'.$disk]);
$request=Illuminate\Http\Request::capture();$response=$kernel->handle($request);$response->send();$kernel->terminate($request,$response);
