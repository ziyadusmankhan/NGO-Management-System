<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';
$app=require dirname(__DIR__).'/bootstrap/app.php';$app->make(Kernel::class)->bootstrap();
if(!app()->environment('local')||DB::getDriverName()!=='mysql')throw new RuntimeException('Run locally with the working MySQL connection.');
$source=DB::connection();$live=$source->getDatabaseName();
$name='foundation_management_test_'.date('YmdHis').'_'.bin2hex(random_bytes(2));
$source->statement('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
config(['database.connections.qa'=>array_replace(config('database.connections.mysql'),['url'=>null,'database'=>$name])]);
$qa=DB::connection('qa');$qa->statement('SET FOREIGN_KEY_CHECKS=0');
try{
 foreach($source->select('SELECT TABLE_NAME AS name FROM information_schema.tables WHERE table_schema=?',[$live])as$table){
  $ddl=(array)$source->selectOne('SHOW CREATE TABLE `'.$table->name.'`');
  $qa->statement(str_replace('`'.$live.'`.','`'.$name.'`.',$ddl['Create Table']));
 }
 $qa->table('migrations')->insert(array_map(fn($r)=>(array)$r,$source->table('migrations')->get()->all()));
 $qa->table('security_state')->insert(['key'=>'encryption','value'=>'converting']);
 $qa->table('procurement_policies')->insert(['id'=>1,'quote_count'=>2,'created_at'=>now(),'updated_at'=>now()]);
}finally{$qa->statement('SET FOREIGN_KEY_CHECKS=1');}
// Only schema and migration bookkeeping are copied; no real user or financial rows.
$receipt=['database'=>$name,'schema_only'=>true,'source'=>$live,'created_at'=>date(DATE_ATOM)];
file_put_contents(base_path('tmp/mysql-test-target.json'),json_encode($receipt,JSON_PRETTY_PRINT));
echo json_encode($receipt,JSON_PRETTY_PRINT),PHP_EOL;
