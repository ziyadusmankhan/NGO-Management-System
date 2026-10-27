<?php

namespace Tests\Support;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Support\Money;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Procurement\Models\ProcurementSupplier;
use App\Domain\Procurement\Services\ProcurementOperationsService;
use App\Domain\Procurement\Services\ProcurementWorkflowService;
use Database\Seeders\BusinessRoleSeeder;
use Database\Seeders\ProcurementRoleSeeder;
use Database\Seeders\ProjectRoleSeeder;
use Illuminate\Support\Str;

trait CreatesProcurementFixtures
{
    use CreatesProjectFixtures;

    protected function procurementActor()
    {
        return $this->staff([...array_map(fn ($p) => 'procurement.'.$p, ProcurementRoleSeeder::PERMISSIONS), ...array_map(fn ($p) => 'projects.'.$p, ProjectRoleSeeder::PERMISSIONS), ...array_map(fn ($p) => 'business.'.$p, BusinessRoleSeeder::PERMISSIONS)]);
    }

    protected function purchaseVersion($p, array $d = []): array
    {
        return ['version' => $p->fresh()->version] + $d;
    }

    protected function purchaseDraft($a): Procurement
    {
        $supplier = ProcurementSupplier::create(['name' => 'Supplier '.Str::ulid(), 'name_key' => (string) Str::ulid(), 'active' => true]);
        $fund = Fund::create(['fund_reference' => 'FND-'.Str::ulid(), 'name' => 'Office fund', 'type' => 'general', 'purpose' => 'general', 'opening_balance' => '1000.00', 'status' => 'active']);

        return app(ProcurementWorkflowService::class)->save(null, ['title' => 'Office chairs', 'description' => 'Replace broken chairs', 'parent_type' => 'office', 'fund_id' => $fund->id, 'supplier_id' => $supplier->id, 'delivery_location' => 'Office', 'required_on' => now('Asia/Karachi')->addDays(3)->toDateString(), 'items' => [['description' => 'Chair', 'unit' => 'each', 'quantity' => '2', 'unit_price' => '250.00']]], $a);
    }

    protected function purchaseQuotes(Procurement $p, $a): void
    {
        $other = ProcurementSupplier::create(['name' => 'Other '.Str::ulid(), 'name_key' => (string) Str::ulid(), 'active' => true]);
        foreach ([$p->proposal()->supplier_id, $other->id] as $id) {
            app(ProcurementWorkflowService::class)->quote($p, $this->purchaseVersion($p, ['supplier_id' => $id, 'reference' => 'Q-'.$id, 'quoted_on' => now('Asia/Karachi')->toDateString(), 'valid_until' => now('Asia/Karachi')->addMonth()->toDateString(), 'terms' => 'Delivered to office, all costs included', 'items' => collect($p->proposal()->items)->map(fn ($i) => ['key' => $i['key'], 'unit_price' => Money::decimal($i['unit_minor'])])->all(), 'evidence' => $this->evidence()]), $a);
        }
    }

    protected function purchaseApprove(Procurement $p, $a): Procurement
    {
        $w = app(ProcurementWorkflowService::class);
        $this->purchaseQuotes($p, $a);
        $w->transition($p, 'submit', $this->purchaseVersion($p), $a);
        foreach (['coordinator', 'secretary', 'committee'] as $stage) {
            $d = ['decision' => 'accept', 'notes' => 'Approved'];
            if ($stage === 'committee') {
                $d += $this->committeeResolution();
                $d['evidence'] = $d['resolution_file'];
                unset($d['resolution_file']);
            }
            $w->decide($p, $stage, $this->purchaseVersion($p, $d), $this->procurementActor());
        }
        $w->transition($p, 'issue', $this->purchaseVersion($p), $a);

        return $p->fresh();
    }

    protected function purchaseInvoice(Procurement $p, $a, string $quantity = '2')
    {
        $ops = app(ProcurementOperationsService::class);
        $ops->receive($p, $this->purchaseVersion($p, ['item_key' => $p->approved()->items[0]['key'], 'received_on' => now('Asia/Karachi')->toDateString(), 'reference' => 'DEL-1', 'accepted' => $quantity, 'rejected' => '0', 'notes' => 'Inspected and accepted', 'evidence' => $this->evidence()]), $this->procurementActor());
        $ops->invoice($p, $this->purchaseVersion($p, ['reference' => 'INV-1', 'invoiced_on' => now('Asia/Karachi')->toDateString(), 'due_on' => now('Asia/Karachi')->addDays(7)->toDateString(), 'lines' => [['receipt_id' => $p->receipts()->latest('id')->first()->id, 'quantity' => $quantity]], 'evidence' => $this->evidence()]), $a);

        return $p->lines()->latest('id')->first();
    }

    protected function purchasePayment(Procurement $p, $line, string $amount = '500.00'): array
    {
        return $this->purchaseVersion($p, ['invoice_line_id' => $line->id, 'amount' => $amount, 'payment_date' => now('Asia/Karachi')->toDateString(), 'payment_method' => 'cash', 'receipt' => 'PAY-1', 'evidence' => $this->evidence(), 'idempotency_key' => (string) Str::uuid()]);
    }

    protected function linkedPurchase($a, string $type, $parent, int $item, string $supplier): Procurement
    {
        $s = ProcurementSupplier::firstOrCreate(['name_key' => mb_strtolower($supplier)], ['name' => $supplier, 'active' => true]);

        return app(ProcurementWorkflowService::class)->save(null, ['title' => 'Linked purchase', 'description' => 'Approved goods', 'parent_type' => $type, 'parent_id' => $parent->id, 'fund_id' => $parent->fund_id, 'supplier_id' => $s->id, 'delivery_location' => 'Kasso', 'required_on' => now('Asia/Karachi')->addDays(3)->toDateString(), 'items' => [['description' => 'Goods', 'unit' => 'each', 'quantity' => '2', 'unit_price' => '250.00', 'budget_item_id' => $item]]], $a);
    }
}
