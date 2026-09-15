<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Department;
use App\Models\User;
use App\Services\Organization\FacilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrganizationFacilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_building_room_laboratory_lifecycle(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $campus = Campus::query()->firstOrFail();

        $building = app(FacilityService::class)->createBuilding($campus->id, ['name' => 'Gedung Rektorat', 'code' => 'GR', 'floors' => 5], $admin);
        $this->assertSame('GR', $building->code);

        $room = app(FacilityService::class)->createRoom($building, ['name' => 'Ruang Rapat 1', 'code' => 'R101', 'kind' => 'office', 'capacity' => 20], $admin);
        $this->assertSame(20, $room->capacity);

        $department = Department::query()->firstOrFail();
        $lab = app(FacilityService::class)->createLaboratory($department->id, ['name' => 'Lab Jaringan', 'code' => 'LAB-NET', 'room_id' => $room->id], $admin);
        $this->assertSame($room->id, $lab->room_id);

        $maintenance = app(FacilityService::class)->setStatus($room, 'maintenance', $admin);
        $this->assertSame('maintenance', $maintenance->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'facility.laboratory_created']);

        try {
            app(FacilityService::class)->createBuilding($campus->id, ['name' => 'Duplikat', 'code' => 'GR'], $admin);
            $this->fail('Kode gedung duplikat seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('code', $e->errors());
        }
    }

    public function test_organization_admin_page_renders(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.organization.index'))->assertOk();
    }
}
