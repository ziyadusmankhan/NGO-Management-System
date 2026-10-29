<?php

namespace Tests\Feature;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Committees\Models\Committee;
use App\Domain\Members\Models\Member;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class OrganizationExportTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    public static function resources(): array
    {
        return [['members', 'members.export', 'members.view'], ['committees', 'committees.export', 'committees.view'],
            ['users', 'users.export', 'users.view'], ['roles', 'users.roles.export', 'roles.manage']];
    }

    public static function downloads(): array
    {
        $cases = [];
        foreach (self::resources() as [$kind, $route, $permission]) {
            foreach (['pdf', 'xlsx'] as $format) {
                $cases[] = [$kind, $route, $permission, $format];
            }
        }

        return $cases;
    }

    private function workbook($response)
    {
        $path = tempnam(sys_get_temp_dir(), 'foundation-export-');
        file_put_contents($path, $response->getContent());
        try {
            return IOFactory::load($path);
        } finally {
            unlink($path);
        }
    }

    #[DataProvider('resources')]
    public function test_exports_require_the_module_permission(string $kind, string $route, string $permission): void
    {
        $url = route($route, ['format' => 'xlsx']);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->staff([]))->get($url)->assertForbidden();
        $this->actingAs($this->staff(['users.view']))->get(route('users.roles.export', ['format' => 'pdf']))->assertForbidden();
    }

    #[DataProvider('downloads')]
    public function test_real_files_are_downloaded_with_private_headers_and_audit(string $kind, string $route, string $permission, string $format): void
    {
        $actor = $this->staff([$permission]);
        $member = Member::create(['member_number' => 'MEM-EXPORT', 'first_name' => 'Nadia', 'last_name' => 'Rahman', 'phone' => '03000000200', 'cnic' => '99999-0000000-1', 'address' => 'Kasso', 'joining_date' => '2026-10-01', 'status' => 'active']);
        $committee = Committee::create(['committee_number' => 'COM-EXPORT', 'name' => 'Education Outreach', 'type' => 'implementation', 'status' => 'active', 'established_at' => '2026-09-01']);
        $committee->members()->attach($member, ['status' => 'active', 'position' => 'Coordinator', 'joined_at' => '2026-10-01']);
        $beforeMembers = Member::count();
        $beforeUsers = User::count();
        $this->actingAs($actor);
        $response = $this->get(route($route, ['format' => $format]))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        if ($format === 'pdf') {
            $response->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $reader = new PdfReader(new PdfParser(StreamReader::createByString($response->getContent())));
            $this->assertGreaterThanOrEqual(1, $reader->getPageCount());
        } else {
            $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $book = $this->workbook($response);
            $sheet = $book->getSheet(0);
            $this->assertSame(config('organization.name'), $sheet->getCell('D2')->getValue());
            $this->assertSame('C16', $sheet->getFreezePane());
            $this->assertNotEmpty($sheet->getDrawingCollection());
            $this->assertSame($kind === 'committees' || $kind === 'roles' ? 2 : 1, $book->getSheetCount());
            if ($kind === 'members') {
                $this->assertSame('03000000200', $sheet->getCell('D16')->getValue());
                $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('D16')->getDataType());
                $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('I16')->getDataType());
                $this->assertStringNotContainsString('99999-0000000-1', json_encode($sheet->toArray()));
            }
            $book->disconnectWorksheets();
        }
        $event = AuditEvent::where('action', $kind.'_exported')->firstOrFail();
        $this->assertSame($actor->id, $event->actor_id);
        $this->assertSame($format, $event->after_values['format']);
        $this->assertSame($beforeMembers, Member::count());
        $this->assertSame($beforeUsers, User::count());
    }

    public function test_member_export_uses_all_filtered_pages_and_excludes_archived_records(): void
    {
        $actor = $this->staff(['members.view']);
        for ($i = 1; $i <= 18; $i++) {
            Member::create(['member_number' => 'MEM-'.$i, 'first_name' => 'Nadia', 'last_name' => 'Rahman '.$i, 'status' => $i <= 16 ? 'active' : 'inactive']);
        }
        $archived = Member::create(['member_number' => 'MEM-ARCHIVED', 'first_name' => 'Nadia', 'last_name' => 'Rahman Archived', 'status' => 'active']);
        $archived->delete();
        $this->actingAs($actor)->get(route('members.index', ['search' => 'Nadia Rahman', 'status' => 'active']))->assertOk()->assertSee('MEM-16')->assertDontSee('MEM-ARCHIVED');
        $response = $this->get(route('members.export', ['search' => 'Nadia Rahman', 'status' => 'active', 'page' => 2, 'format' => 'xlsx']))->assertOk();
        $book = $this->workbook($response);
        $sheet = $book->getActiveSheet();
        $this->assertSame(16, $sheet->getCell('B6')->getCalculatedValue());
        $this->assertSame('MEM-1', $sheet->getCell('B31')->getValue());
        $this->assertStringNotContainsString('MEM-ARCHIVED', json_encode($sheet->toArray()));
        $this->assertStringNotContainsString('MEM-17', json_encode($sheet->toArray()));
        $book->disconnectWorksheets();
    }

    public function test_user_filters_include_secondary_roles_and_excel_text_is_not_executable(): void
    {
        $actor = $this->staff(['users.view']);
        $other = $this->staff([]);
        $other->update(['name' => '=HYPERLINK("https://example.test","Sample")']);
        $role = Role::create(['name' => 'Field Team', 'slug' => 'field-team', 'status' => 'active']);
        $other->roles()->attach($role);
        $this->actingAs($actor)->get(route('users.index', ['role' => 'field-team']))->assertOk()->assertSee('Sample')->assertDontSee($actor->email);
        $book = $this->workbook($this->get(route('users.export', ['role' => 'field-team', 'format' => 'xlsx']))->assertOk());
        $sheet = $book->getActiveSheet();
        $this->assertSame(1, $sheet->getCell('B6')->getCalculatedValue());
        $this->assertSame($other->name, $sheet->getCell('C16')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('C16')->getDataType());
        $this->assertStringContainsString('Field Team', $sheet->getCell('E16')->getValue());
        $this->assertStringNotContainsString($other->getRawOriginal('password'), json_encode($sheet->toArray()));
        $book->disconnectWorksheets();
    }

    public function test_committee_filters_and_roster_and_selected_role_exports_agree(): void
    {
        $actor = $this->staff(['committees.view', 'roles.manage']);
        $committee = Committee::create(['committee_number' => 'COM-SELECT', 'name' => 'Selected Committee', 'type' => 'decision', 'status' => 'active']);
        Committee::create(['committee_number' => 'COM-OTHER', 'name' => 'Other Committee', 'type' => 'implementation', 'status' => 'active']);
        $member = Member::create(['member_number' => 'MEM-ROSTER', 'first_name' => 'Rubina', 'status' => 'active']);
        $committee->members()->attach($member, ['position' => 'Chairperson', 'status' => 'active']);
        $this->actingAs($actor)->get(route('committees.index', ['type' => 'decision']))->assertOk()->assertSee('COM-SELECT')->assertDontSee('COM-OTHER');
        $book = $this->workbook($this->get(route('committees.export', ['type' => 'decision', 'format' => 'xlsx']))->assertOk());
        $this->assertSame(1, $book->getSheet(0)->getCell('B6')->getCalculatedValue());
        $this->assertSame('MEM-ROSTER', $book->getSheet(1)->getCell('D8')->getValue());
        $book->disconnectWorksheets();
        $role = $actor->roles()->firstOrFail();
        $permission = Permission::firstOrCreate(['slug' => 'members.view'], ['name' => 'View Members', 'module' => 'members']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $book = $this->workbook($this->get(route('users.roles.export', ['role' => $role->id, 'format' => 'xlsx']))->assertOk());
        $this->assertSame(1, $book->getSheet(0)->getCell('B6')->getCalculatedValue());
        $this->assertStringContainsString('members.view', json_encode($book->getSheet(1)->toArray()));
        $book->disconnectWorksheets();
    }

    public function test_empty_exports_and_invalid_formats_are_handled(): void
    {
        $actor = $this->staff(['members.view']);
        $this->actingAs($actor)->getJson(route('members.export', ['format' => 'exe']))->assertUnprocessable();
        $response = $this->get(route('members.export', ['format' => 'xlsx', 'search' => 'No matching person']))->assertOk();
        $book = $this->workbook($response);
        $this->assertSame(0, $book->getActiveSheet()->getCell('B6')->getCalculatedValue());
        $this->assertSame('No matching records', $book->getActiveSheet()->getCell('C16')->getValue());
        foreach ($book->getActiveSheet()->getCellCollection()->getCoordinates() as $coordinate) {
            $cell = $book->getActiveSheet()->getCell($coordinate);
            if ($cell->isFormula()) {
                $this->assertNotContains($cell->getCalculatedValue(), ['#REF!', '#VALUE!', '#DIV/0!', '#NAME?', '#NUM!', '#N/A']);
            }
        }
        $book->disconnectWorksheets();
    }
}
