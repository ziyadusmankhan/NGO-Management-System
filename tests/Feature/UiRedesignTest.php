<?php

namespace Tests\Feature;

use App\Domain\Members\Models\Member;
use App\Domain\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class UiRedesignTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    public function test_reachable_entry_screens_render_with_shared_shell_and_preserved_forms(): void
    {
        $admin = $this->staff([]);
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator', 'status' => 'active']));
        $this->actingAs($admin);
        $application = $this->signedApplication($admin);
        $assistance = $this->legacyAssistance($application, 'approved');
        $member = Member::create(['member_number' => 'MEM-UI-001', 'first_name' => 'Sample', 'last_name' => 'Member', 'status' => 'active']);
        $routes = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if ($name && in_array('GET', $route->methods()) && ! str_contains($route->uri(), '{')
                && preg_match('/(\.index|\.create|\.categories|\.roles|\.settings|\.reports|\.report)$|^dashboard$/', $name)) {
                $routes[$name] = [];
            }
        }
        $routes += [
            'members.show' => [$member], 'members.edit' => [$member],
            'users.show' => [$admin], 'users.edit' => [$admin],
            'finance.funds.show' => [$application->fund_id], 'finance.funds.edit' => [$application->fund_id],
            'welfare.beneficiaries.show' => [$application->beneficiary_id],
            'welfare.beneficiaries.edit' => [$application->beneficiary_id],
            'welfare.applications.show' => [$application],
            'welfare.assistance.show' => [$assistance],
        ];
        foreach ($routes as $name => $parameters) {
            if (getenv('UI_EXPORT_PREVIEWS') === '1') { fwrite(STDERR, "Rendering {$name}\n"); }
            $response = $this->get(route($name, $parameters));
            $response->assertOk();
            if ($name !== 'security.index') {
                $response->assertSee('foundation-ui.css', false)->assertSee('foundation-ui.js', false);
            }
            $this->exportPreview($name, $response->getContent());
        }
        $this->assertGreaterThan(30, count($routes));
    }

    public function test_guest_entry_and_authentication_challenge_layouts_remain_available(): void
    {
        $response = $this->get('/login')->assertOk()->assertSee('foundation-auth.css', false);
        $this->exportPreview('login', $response->getContent());
        $this->get('/dashboard')->assertRedirect('/login');
    }

    private function exportPreview(string $name, string $html): void
    {
        if (getenv('UI_EXPORT_PREVIEWS') !== '1') { return; }
        // Only synthetic records in the in-memory test DB can reach this code.
        $directory = base_path('tmp/ui-previews');
        if (! is_dir($directory)) { mkdir($directory, 0777, true); }
        file_put_contents($directory.'/'.$name.'.html', $html);
    }
}
