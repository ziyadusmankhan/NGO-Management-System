<?php

namespace App\Domain\Finance\Funds\Services;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class FundPurposeService
{
    public function allowsProject(Fund $fund, \App\Domain\Projects\Models\Project $project, string $purpose): bool
    {
        if ($fund->project_id && (int)$fund->project_id !== (int)$project->id) { return false; }
        return !$this->isRestricted($fund) || (in_array($purpose, config('projects.purposes'), true) && $fund->purpose === $purpose);
    }

    public function allowsBusiness(Fund $fund, string $supportType): bool
    {
        if ($fund->project_id) { return false; }
        return ! $this->isRestricted($fund) || $fund->purpose === 'small_business'
            || ($fund->purpose === 'qard' && $supportType === 'repayable');
    }

    public function allowsQard(Fund $fund): bool
    {
        if ($fund->project_id) { return false; }
        return ! $this->isRestricted($fund) || $fund->purpose === 'qard';
    }

    public function isRestricted(Fund $fund): bool
    {
        return $fund->type !== 'general' || $fund->restrict_spending;
    }

    public function allowsExpense(Fund $fund, string $category): bool
    {
        if ($fund->project_id) { return false; }
        if (! $this->isRestricted($fund)) {
            return true;
        }

        return match ($fund->purpose) {
            'general_welfare' => in_array($category, ['welfare', 'medical', 'education'], true),
            'education' => $category === 'education',
            'emergency' => in_array($category, ['medical', 'welfare'], true),
            'community_development' => $category === 'project',
            // A generic expense must not disburse money reserved for loans.
            default => false,
        };
    }

    public function assertAllowsDebit(Fund $fund, Model $source): void
    {
        if ($fund->project_id && !($source instanceof \App\Domain\Projects\Models\ProjectMovement && (int)$source->project_id === (int)$fund->project_id)) {
            throw ValidationException::withMessages(['fund_id'=>'This dedicated fund can only pay its named project.']);
        }
        if (! $this->isRestricted($fund)) {
            return;
        }

        $allowed = match (true) {
            $source instanceof \App\Domain\Projects\Models\ProjectMovement => $source->kind === 'payment' && $this->allowsProject($fund,$source->project,$source->project->approved()->purpose),
            $source instanceof \App\Domain\Business\Models\BusinessMovement => $source->kind === 'disbursement'
                && (int) $source->fund_id === (int) $fund->id && $this->allowsBusiness($fund, $source->application->support_type),
            $source instanceof \App\Domain\Qard\Models\QardMovement => $source->kind === 'disbursement'
                && (int) $source->fund_id === (int) $fund->id && $this->allowsQard($fund),
            $source instanceof Expense => $this->allowsExpense($fund, $source->category),
            $source instanceof WelfareAssistancePayment => $source->assistance !== null
                && $this->allowsWelfare($fund, $source->assistance),
            default => false,
        };
        if (! $allowed) {
            throw ValidationException::withMessages([
                'fund_id' => 'This spending purpose is not permitted by the '.$fund->name.' designation ('.str_replace('_', ' ', $fund->purpose).').',
            ]);
        }
    }

    public function allowsWelfare(Fund $fund, WelfareAssistance $assistance): bool
    {
        if ($fund->project_id) { return false; }
        if (! $this->isRestricted($fund)) {
            return true;
        }

        $category = $assistance->application_id
            ? $assistance->application?->category
            : match ($assistance->assistance_type) {
                'education_support' => 'education',
                'emergency_assistance' => 'emergency',
                'livelihood_assistance' => 'livelihood',
                'monthly_assistance', 'food_ration', 'medical_assistance', 'one_time_support', 'general_welfare' => 'welfare',
                default => null,
            };

        return match ($fund->purpose) {
            'general_welfare' => in_array($category, ['welfare', 'education', 'emergency'], true),
            'education' => $category === 'education',
            'emergency' => $category === 'emergency',
            'small_business' => $category === 'livelihood',
            // Loans and community projects must use their own authorized workflows.
            default => false,
        };
    }
}
