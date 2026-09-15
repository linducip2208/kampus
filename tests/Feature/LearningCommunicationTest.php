<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\CourseAnnouncement;
use App\Models\Discussion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_student_and_assigned_lecturer_can_use_class_communication(): void
    {
        $this->seed();
        $section = ClassSection::query()->firstOrFail();
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();

        $this->actingAs($lecturer)
            ->post(route('lecturer.learning.announcements.store', $section), [
                'title' => 'Perubahan ruang perkuliahan',
                'body' => 'Pertemuan berikutnya dilaksanakan di laboratorium perangkat lunak.',
                'is_pinned' => '1',
                'publish_now' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $announcement = CourseAnnouncement::query()->firstOrFail();
        $this->assertTrue($announcement->is_pinned);
        $this->assertNotNull($announcement->published_at);

        $this->actingAs($student)
            ->get(route('portal.learning.index'))
            ->assertOk()
            ->assertSee('Perubahan ruang perkuliahan');

        $this->actingAs($student)
            ->post(route('portal.discussions.store', $section), [
                'title' => 'Diskusi kompleksitas algoritma',
                'body' => 'Bagaimana membandingkan kompleksitas dua solusi iteratif?',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $discussion = Discussion::query()->firstOrFail();

        $this->actingAs($student)
            ->get(route('portal.discussions.show', $discussion))
            ->assertOk()
            ->assertSee('Diskusi kompleksitas algoritma');

        $this->actingAs($lecturer)
            ->post(route('lecturer.discussions.reply', $discussion), [
                'body' => 'Bandingkan jumlah operasi dominan terhadap ukuran masukan.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($lecturer)
            ->get(route('lecturer.discussions.show', $discussion))
            ->assertOk()
            ->assertSee('Bandingkan jumlah operasi dominan');

        $this->actingAs($lecturer)
            ->post(route('lecturer.discussions.lock', $discussion), ['locked' => '1'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('discussions', ['id' => $discussion->id, 'status' => 'locked']);
        $this->assertDatabaseHas('discussion_posts', [
            'discussion_id' => $discussion->id,
            'user_id' => $lecturer->id,
        ]);

        $this->actingAs($student)
            ->post(route('portal.discussions.reply', $discussion), ['body' => 'Terima kasih, Pak.'])
            ->assertRedirect()
            ->assertSessionHasErrors('discussion');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'lms.discussion_status_changed',
            'entity_id' => $discussion->id,
        ]);
    }

    public function test_user_outside_class_cannot_open_discussion(): void
    {
        $this->seed();
        $section = ClassSection::query()->firstOrFail();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $staff = User::query()->where('email', 'baak@kampus.test')->firstOrFail();

        $this->actingAs($student)->post(route('portal.discussions.store', $section), [
            'title' => 'Topik kelas privat',
            'body' => 'Konten ini hanya untuk peserta kelas.',
        ]);
        $discussion = Discussion::query()->firstOrFail();

        $this->actingAs($staff)
            ->get(route('lecturer.discussions.show', $discussion))
            ->assertForbidden();
    }
}
