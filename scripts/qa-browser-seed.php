<?php

require dirname(__DIR__).'/vendor/autoload.php';
$app=require dirname(__DIR__).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$receipt=json_decode(file_get_contents(base_path('tmp/mysql-test-target.json')),true);
$name=$receipt['database']??'';
if(!preg_match('/^foundation_management_test_[0-9]{14}_[a-f0-9]{4}$/',$name))throw new RuntimeException('Disposable QA target required.');
config(['database.connections.mysql.database'=>$name,'database.connections.mysql.url'=>null,'database.default'=>'mysql']);
Illuminate\Support\Facades\DB::purge('mysql');
Illuminate\Support\Facades\DB::table('procurement_policies')->insertOrIgnore(['id'=>1,'quote_count'=>2,'created_at'=>now(),'updated_at'=>now()]);
if(($argv[1]??'')==='--bootstrap-only'){echo 'Default quotation policy restored in disposable QA schema.'.PHP_EOL;exit(0);}
Illuminate\Support\Facades\Artisan::call('db:seed',['--class'=>'DatabaseSeeder','--force'=>true]);
$user=App\Domain\Users\Models\User::firstOrCreate(['email'=>'qa.admin@example.test'],['name'=>'QA Browser Administrator','password'=>'QA-test-only-2026!','status'=>'active']);
$user->roles()->syncWithoutDetaching([App\Domain\Users\Models\Role::where('slug','administrator')->firstOrFail()->id]);
$receipt['schema_only']=false;$receipt['browser_seeded']=true;
file_put_contents(base_path('tmp/mysql-test-target.json'),json_encode($receipt,JSON_PRETTY_PRINT));
echo 'QA browser administrator prepared in '.$name.PHP_EOL;
