<?php

namespace Tests\Feature;

use App\Domain\Operations\Models\Meeting;
use App\Domain\Operations\Models\Task;
use App\Domain\Reports\Models\ReportRun;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class OperationsAndReportsTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $permissions): User
    {
        $user = User::create(['name' => 'Tester '.Str::random(5), 'email' => Str::uuid().'@example.test', 'password' => 'password1234', 'status' => 'active']);
        $role = Role::create(['name' => 'Role '.Str::random(5), 'slug' => 'role-'.Str::uuid(), 'status' => 'active']);
        foreach ($permissions as $slug) {
            $role->permissions()->attach(Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => str($slug)->before('.')])->id);
        }
        $user->roles()->attach($role);

        return $user;
    }

    private function createTask(User $manager, array $extra = []): Task
    {
        $this->actingAs($manager)->post(route('operations.tasks.store'), $extra + [
            'title' => 'Deliver field supplies', 'category' => 'community', 'priority' => 'high',
            'assigned_user_id' => $manager->id, 'starts_on' => '2026-10-28', 'due_on' => '2026-11-02',
            'description' => 'Prepare and deliver supplies.', 'expected_outcome' => 'Supplies delivered.',
        ])->assertRedirect();

        return Task::latest('id')->firstOrFail();
    }

    public function test_task_permissions_assignment_progress_comments_stale_forms_and_archive(): void
    {
        $viewer = $this->user(['tasks.view']);
        $manager = $this->user(['tasks.view', 'tasks.manage']);
        $this->actingAs($viewer)->get(route('operations.tasks.index'))->assertOk();
        $this->get(route('operations.tasks.create'))->assertForbidden();
        $task = $this->createTask($manager, ['assigned_user_id' => $viewer->id]);
        $this->assertSame('TSK-00001', $task->number);
        $this->assertStringStartsWith('encrypted:v1:', (string) \DB::table('operation_tasks')->where('id', $task->id)->value('description'));
        $this->actingAs($viewer)->post(route('operations.tasks.comments', $task), ['body' => 'Work has started.'])->assertRedirect();
        $this->assertSame('Work has started.', $task->comments()->firstOrFail()->body);
        $this->actingAs($manager)->post(route('operations.tasks.status', $task), ['revision' => $task->revision, 'status' => 'completed', 'progress_percent' => 90])->assertSessionHasErrors('progress_percent');
        $this->post(route('operations.tasks.status', $task), ['revision' => $task->revision, 'status' => 'completed', 'progress_percent' => 100, 'completion_notes' => 'Done'])->assertRedirect();
        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);
        $this->put(route('operations.tasks.update', $task), ['revision' => 1, 'title' => 'Stale', 'category' => 'other', 'priority' => 'low', 'due_on' => '2026-11-03'])->assertSessionHasErrors('revision');
        $this->delete(route('operations.tasks.archive', $task))->assertRedirect();
        $this->assertSoftDeleted('operation_tasks', ['id' => $task->id]);
        $this->post(route('operations.tasks.restore', $task->id))->assertRedirect();
        $this->assertNotSoftDeleted('operation_tasks', ['id' => $task->id]);
    }

    public function test_meeting_quorum_minutes_attendance_and_action_items(): void
    {
        $manager = $this->user(['meetings.view', 'meetings.manage', 'tasks.view', 'tasks.manage']);
        $attendee = $this->user(['meetings.view', 'tasks.view']);
        $this->actingAs($manager)->post(route('operations.meetings.store'), [
            'title' => 'October coordination', 'type' => 'general', 'meeting_date' => '2026-10-28',
            'starts_at' => '10:00', 'ends_at' => '11:00', 'location' => 'Main office', 'agenda' => 'Review work.',
            'chair_user_id' => $manager->id, 'quorum_required' => 2, 'attendees' => [$manager->id, $attendee->id],
        ])->assertRedirect();
        $meeting = Meeting::firstOrFail();
        $this->assertSame('MTG-00001', $meeting->number);
        $payload = ['revision' => $meeting->revision, 'minutes' => 'The team reviewed all work.', 'decisions' => 'Deliver supplies.', 'attended' => [$manager->id], 'action_items' => [['title' => 'Deliver supplies', 'assigned_user_id' => $attendee->id, 'due_on' => '2026-11-02', 'priority' => 'high']]];
        $this->post(route('operations.meetings.minutes', $meeting), $payload)->assertSessionHasErrors('attended');
        $payload['attended'][] = $attendee->id;
        $this->post(route('operations.meetings.minutes', $meeting), $payload)->assertRedirect();
        $meeting->refresh();
        $this->assertSame('held', $meeting->status);
        $this->assertSame(2, $meeting->attendees()->wherePivot('attended', true)->count());
        $this->assertStringStartsWith('encrypted:v1:', (string) \DB::table('operation_meetings')->where('id', $meeting->id)->value('minutes'));
        $task = Task::firstOrFail();
        $this->assertSame($meeting->id, $task->source_meeting_id);
        $this->assertSame($attendee->id, $task->assigned_user_id);
        $this->post(route('operations.meetings.minutes', $meeting), $payload)->assertStatus(422);
    }

    public function test_report_permissions_retained_snapshot_print_and_real_xlsx(): void
    {
        $manager = $this->user(['tasks.view', 'tasks.manage', 'meetings.view', 'reports.view', 'reports.generate']);
        $restricted = $this->user(['reports.view', 'reports.generate']);
        $this->createTask($manager);
        $this->actingAs($restricted)->post(route('records.reports.store'), ['type' => 'operations', 'from' => '2026-10-01', 'to' => '2026-10-31'])->assertForbidden();
        $this->actingAs($manager)->post(route('records.reports.store'), ['type' => 'operations', 'from' => '2026-10-01', 'to' => '2026-10-31'])->assertRedirect();
        $report = ReportRun::firstOrFail();
        $this->assertSame('RPT-00001', $report->number);
        $this->assertSame(1, $report->snapshot['metrics'][0]['value']);
        $this->createTask($manager, ['title' => 'Second task']);
        $this->get(route('records.reports.show', $report))->assertOk()->assertSee('Tasks created')->assertSee('1');
        $this->get(route('records.reports.export', [$report, 'format' => 'print']))->assertOk()->assertSee('Retained snapshot');
        $response = $this->get(route('records.reports.export', [$report, 'format' => 'xlsx']))->assertOk()->assertDownload('rpt-00001.xlsx');
        $path = tempnam(sys_get_temp_dir(), 'report-xlsx-');
        file_put_contents($path, $response->getContent());
        $sheet = IOFactory::load($path)->getActiveSheet();
        unlink($path);
        $this->assertSame('Operations Activity', $sheet->getCell('B1')->getValue());
        $this->assertSame('Tasks created', $sheet->getCell('A7')->getValue());
    }

    public function test_dashboard_shows_real_upcoming_tasks(): void
    {
        $manager = $this->user(['tasks.view', 'tasks.manage']);
        $task = $this->createTask($manager, ['title' => 'Dashboard task']);
        $this->actingAs($manager)->get(route('dashboard'))->assertOk()->assertSee('Upcoming Tasks')->assertSee($task->title)->assertDontSee('Task scheduling is in development');
    }
}
