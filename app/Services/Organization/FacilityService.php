<?php

namespace App\Services\Organization;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Laboratory;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FacilityService
{
    public function createBuilding(string $campusId, array $data, User $actor): Building
    {
        if (trim($data['name'] ?? '') === '' || trim($data['code'] ?? '') === '') {
            throw ValidationException::withMessages(['building' => 'Nama dan kode gedung wajib diisi.']);
        }
        if (Building::query()->where('campus_id', $campusId)->where('code', trim($data['code']))->exists()) {
            throw ValidationException::withMessages(['code' => 'Kode gedung sudah dipakai di kampus ini.']);
        }

        $building = Building::query()->create([
            'campus_id' => $campusId,
            'name' => trim($data['name']),
            'code' => trim($data['code']),
            'floors' => max(1, (int) ($data['floors'] ?? 1)),
            'status' => $data['status'] ?? 'active',
        ]);
        $this->audit($actor, $building, 'facility.building_created', ['code' => $building->code]);

        return $building->fresh();
    }

    public function createRoom(Building $building, array $data, User $actor): Room
    {
        if (trim($data['name'] ?? '') === '' || trim($data['code'] ?? '') === '') {
            throw ValidationException::withMessages(['room' => 'Nama dan kode ruangan wajib diisi.']);
        }
        if (! in_array($data['kind'] ?? 'classroom', ['classroom', 'lab', 'office', 'hall', 'library', 'other'], true)) {
            throw ValidationException::withMessages(['kind' => 'Jenis ruangan tidak valid.']);
        }
        if (Room::query()->where('building_id', $building->id)->where('code', trim($data['code']))->exists()) {
            throw ValidationException::withMessages(['code' => 'Kode ruangan sudah dipakai di gedung ini.']);
        }

        $room = Room::query()->create([
            'building_id' => $building->id,
            'name' => trim($data['name']),
            'code' => trim($data['code']),
            'kind' => $data['kind'] ?? 'classroom',
            'capacity' => max(0, (int) ($data['capacity'] ?? 0)),
            'floor' => max(1, (int) ($data['floor'] ?? 1)),
            'status' => $data['status'] ?? 'active',
        ]);
        $this->audit($actor, $room, 'facility.room_created', ['code' => $room->code]);

        return $room->fresh();
    }

    public function createLaboratory(string $departmentId, array $data, User $actor): Laboratory
    {
        return DB::transaction(function () use ($departmentId, $data, $actor) {
            if (trim($data['name'] ?? '') === '' || trim($data['code'] ?? '') === '') {
                throw ValidationException::withMessages(['laboratory' => 'Nama dan kode laboratorium wajib diisi.']);
            }
            if (Laboratory::query()->where('department_id', $departmentId)->where('code', trim($data['code']))->exists()) {
                throw ValidationException::withMessages(['code' => 'Kode laboratorium sudah dipakai di departemen ini.']);
            }

            $lab = Laboratory::query()->create([
                'department_id' => $departmentId,
                'room_id' => $data['room_id'] ?? null,
                'name' => trim($data['name']),
                'code' => trim($data['code']),
                'kind' => $data['kind'] ?? 'computer',
                'head_id' => $data['head_id'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);
            $this->audit($actor, $lab, 'facility.laboratory_created', ['code' => $lab->code]);

            return $lab->fresh();
        });
    }

    public function setStatus(object $facility, string $status, User $actor): object
    {
        return DB::transaction(function () use ($facility, $status, $actor) {
            if (! in_array($status, ['active', 'maintenance', 'inactive'], true)) {
                throw ValidationException::withMessages(['status' => 'Status fasilitas tidak valid.']);
            }
            $locked = $facility::query()->lockForUpdate()->findOrFail($facility->id);
            $locked->forceFill(['status' => $status])->save();
            $this->audit($actor, $locked, 'facility.status_changed', ['status' => $status]);

            return $locked->fresh();
        });
    }

    private function audit(User $actor, object $entity, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id, 'event' => $event, 'module' => 'organization',
            'entity_type' => $entity::class, 'entity_id' => $entity->id, 'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
