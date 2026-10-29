<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            WelfareApplicationWorkflowPermissionsSeeder::class,
            WelfareApplicationAgreementPermissionsSeeder::class,
            ImplementationCommitteeRoleSeeder::class,
            WelfareReviewRoleSeeder::class,
            QardRoleSeeder::class,
            BusinessRoleSeeder::class,
            ProjectRoleSeeder::class,
            ProcurementRoleSeeder::class,
            AuditRoleSeeder::class,
            DocumentSecurityRoleSeeder::class,
            OperationsReportingRoleSeeder::class,
        ]);
    }
}
