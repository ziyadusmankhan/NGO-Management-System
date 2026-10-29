<?php

namespace App\Providers;

use App\Domain\Documents\Services\DocumentRegistry;
use App\Domain\Welfare\Applications\Models\WelfareApplicationAgreement;
use Illuminate\Support\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (array_merge(array_keys(DocumentRegistry::SOURCES), [WelfareApplicationAgreement::class]) as $class) {
            $class::saved(fn ($m) => app(DocumentRegistry::class)->source($m));
        }
    }
}
