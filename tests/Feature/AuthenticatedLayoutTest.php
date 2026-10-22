<?php

namespace Tests\Feature;

use App\Domain\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class AuthenticatedLayoutTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    public function test_both_profiles_show_the_authenticated_name_initials_and_all_roles(): void
    {
        $user = $this->staff(['welfare.assistance.view']);
        $user->update(['name' => 'Élodie Khan']);
        $user->roles()->first()->update(['name' => 'Implementation / Administration Committee']);
        $user->roles()->attach(Role::create([
            'name' => 'Reviewer & Adviser', 'slug' => 'reviewer', 'status' => 'active',
        ]));

        $response = $this->actingAs($user)->get(route('welfare.assistance.index'));
        $response->assertOk()->assertDontSee('Yasir Khan')->assertDontSee('Chairman');
        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'title="Élodie Khan"'));
        $this->assertSame(2, substr_count($html, 'title="Implementation / Administration Committee, Reviewer &amp; Adviser"'));
        $this->assertSame(2, preg_match_all('/class="(?:sidebar|profile)-avatar">\s*ÉK\s*</u', $html));
        $response->assertSee('action="'.route('logout').'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('Log out');
    }

    public function test_account_without_a_role_and_single_word_unicode_name_render_correctly(): void
    {
        $user = $this->staff([]);
        $user->update(['name' => 'احمد']);
        $user->roles()->detach();

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'title="احمد"'));
        $this->assertSame(2, substr_count($html, 'title="No role assigned"'));
        $this->assertSame(2, preg_match_all('/class="(?:sidebar|profile)-avatar">\s*ا\s*</u', $html));
    }

    public function test_logout_clears_the_session_and_another_account_can_sign_in(): void
    {
        $first = $this->staff([]);
        $first->update(['name' => 'First Account']);
        $second = $this->staff([]);
        $second->update(['name' => 'Second Account']);
        $second->roles()->first()->update(['name' => 'General Secretary']);

        $this->post(route('login'), ['email' => $first->email, 'password' => 'test-password'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($first);
        $this->withSession(['account_switch_marker' => 'old account'])
            ->post(route('logout'))->assertRedirect('/login')
            ->assertSessionMissing('account_switch_marker');
        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();

        $this->post(route('login'), ['email' => $second->email, 'password' => 'test-password'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($second);
        $response = $this->get(route('dashboard'))->assertOk()->assertDontSee('First Account');
        $this->assertSame(2, substr_count($response->getContent(), 'title="Second Account"'));
        $this->assertSame(2, substr_count($response->getContent(), 'title="General Secretary"'));
    }
}
