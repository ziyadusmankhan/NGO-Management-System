<?php

namespace Tests\Feature;

use App\Domain\Members\Models\Member;
use App\Domain\Security\Services\FieldEncryption;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use App\Rules\UniqueMemberCnic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PhaseElevenEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_fields_are_encrypted_but_names_and_exact_indexes_work(): void
    {
        $member = Member::create(['member_number' => 'M-1', 'first_name' => 'Private', 'last_name' => 'Example', 'cnic' => '12345-1234567-1', 'phone' => '0300-1234567', 'status' => 'active', 'address' => 'Private street 42']);
        $raw = DB::table('members')->where('id', $member->id)->first();
        $this->assertStringStartsWith(FieldEncryption::PREFIX, $raw->cnic);
        $this->assertStringNotContainsString('Private street', $raw->address);
        $this->assertSame('12345-1234567-1', $member->fresh()->cnic);
        $this->assertSame('Private Example', $raw->first_name.' '.$raw->last_name);
        $this->assertSame($raw->cnic_lookup, app(FieldEncryption::class)->lookup('cnic', '1234512345671'));
        $this->assertSame($raw->phone_lookup, app(FieldEncryption::class)->lookup('phone', '+92 300 1234567'));
        $member->delete();
        $this->assertTrue(Validator::make(['cnic' => '1234512345671'], ['cnic' => [new UniqueMemberCnic]])->fails());
    }

    public function test_conversion_is_verified_resumable_and_dry_run_does_not_write(): void
    {
        $id = DB::table('beneficiaries')->insertGetId(['beneficiary_number' => 'B-1', 'full_name' => 'Existing person', 'cnic' => '12345-1234567-1', 'phone' => '03001234567', 'address' => 'Legacy private road', 'category' => 'student', 'verification_status' => 'pending', 'approval_status' => 'pending']);
        $before = DB::table('beneficiaries')->find($id);
        $this->artisan('security:encrypt', ['--dry-run' => true])->assertExitCode(0);
        $this->assertEquals($before, DB::table('beneficiaries')->find($id));
        $this->artisan('security:encrypt')->assertExitCode(0);
        $after = DB::table('beneficiaries')->find($id);
        $this->assertStringStartsWith(FieldEncryption::PREFIX, $after->address);
        $this->assertSame('Legacy private road', Beneficiary::find($id)->address);
        $this->artisan('security:encrypt')->assertExitCode(0);
        $this->assertEquals($after, DB::table('beneficiaries')->find($id));
        $this->artisan('security:encrypt', ['--verify' => true])->assertExitCode(0);
    }

    public function test_conversion_refuses_normalization_collisions_without_replacing_values(): void
    {
        foreach (['12345-1234567-1', '1234512345671'] as $i => $cnic) {
            DB::table('members')->insert(['member_number' => 'M-'.$i, 'first_name' => 'Duplicate', 'last_name' => (string) $i, 'cnic' => $cnic, 'phone' => '03001234567', 'status' => 'active']);
        }
        $this->artisan('security:encrypt')->assertExitCode(1);
        $this->assertSame('12345-1234567-1', DB::table('members')->orderBy('id')->value('cnic'));
    }

    public function test_sealed_fields_never_silently_fall_back_to_plaintext(): void
    {
        DB::table('security_state')->where('key', 'encryption')->update(['value' => 'sealed']);
        $this->expectException(\RuntimeException::class);
        app(FieldEncryption::class)->decode('unconverted narrative');
    }

    public function test_tampering_or_missing_lookup_key_fails_closed(): void
    {
        $cipher = app(FieldEncryption::class);
        $value = $cipher->encode('private');
        $this->assertSame('private', $cipher->decode($value));
        config(['security.lookup_key' => null]);
        $this->expectException(\RuntimeException::class);
        $cipher->lookup('phone', '03001234567');
    }

    public function test_missing_security_state_does_not_allow_plaintext_fallback(): void
    {
        DB::table('security_state')->where('key', 'encryption')->delete();
        $this->expectException(\RuntimeException::class);
        app(FieldEncryption::class)->decode('old sensitive value');
    }
}
