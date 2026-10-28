<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuditServiceProvider;
use App\Providers\DocumentServiceProvider;
use App\Providers\SecurityServiceProvider;

return [
    SecurityServiceProvider::class,
    DocumentServiceProvider::class,
    AppServiceProvider::class,
    AuditServiceProvider::class,
];
