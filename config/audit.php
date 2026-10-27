<?php

use App\Domain\Business\Models\BusinessCategoryEvent;
use App\Domain\Business\Models\BusinessEvent;
use App\Domain\Committees\Models\Committee;
use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\FundChange;
use App\Domain\Members\Models\Member;
use App\Domain\Procurement\Models\ProcurementEvent;
use App\Domain\Projects\Models\ProjectEvent;
use App\Domain\Qard\Models\QardEvent;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Applications\Models\WelfareApplicationAgreement;
use App\Domain\Welfare\Applications\Models\WelfareApplicationReview;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Models\WelfareAssistanceReview;
use App\Domain\Welfare\Assistance\Models\WelfareAssistanceStatusChange;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use App\Domain\Welfare\Verification\Models\BeneficiaryVerification;

return [
    'models' => [
        BusinessCategoryEvent::class => ['module' => 'business', 'parent' => 'category_id', 'actor' => 'actor_id', 'time' => 'created_at', 'history' => true],
        User::class => ['module' => 'accounts'],
        Role::class => ['module' => 'accounts'],
        Member::class => ['module' => 'organization'],
        Committee::class => ['module' => 'organization'],
        Donation::class => ['module' => 'finance'],
        Expense::class => ['module' => 'finance'],
        Beneficiary::class => ['module' => 'welfare'],
        BeneficiaryVerification::class => ['module' => 'welfare'],
        WelfareApplication::class => ['module' => 'welfare'],
        WelfareApplicationAgreement::class => ['module' => 'welfare'],
        WelfareAssistance::class => ['module' => 'welfare'],
        FundChange::class => ['module' => 'finance', 'parent' => 'fund_id', 'actor' => 'changed_by', 'time' => 'changed_at', 'history' => true],
        QardEvent::class => ['module' => 'qard', 'parent' => 'qard_loan_id', 'actor' => 'actor_id', 'time' => 'created_at', 'history' => true],
        BusinessEvent::class => ['module' => 'business', 'parent' => 'business_application_id', 'actor' => 'actor_id', 'time' => 'created_at', 'history' => true],
        ProjectEvent::class => ['module' => 'projects', 'parent' => 'project_id', 'actor' => 'actor_id', 'time' => 'created_at', 'history' => true],
        ProcurementEvent::class => ['module' => 'procurement', 'parent' => 'procurement_id', 'actor' => 'actor_id', 'time' => 'created_at', 'history' => true],
        WelfareApplicationReview::class => ['module' => 'welfare', 'parent' => 'application_id', 'actor' => 'decided_by', 'time' => 'decided_at', 'history' => true],
        WelfareAssistanceStatusChange::class => ['module' => 'welfare', 'parent' => 'assistance_id', 'actor' => 'changed_by', 'time' => 'changed_at', 'history' => true],
        WelfareAssistanceReview::class => ['module' => 'welfare', 'parent' => 'assistance_id', 'actor' => 'reviewed_by', 'time' => 'created_at', 'history' => true],
    ],
];
