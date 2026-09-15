<?php

namespace App\Services\Approval;

use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalBuilderService
{
    public function createWorkflow(string $universityId, string $module, string $name, array $roleNames, User $actor): ApprovalWorkflow
    {
        return DB::transaction(function () use ($universityId, $module, $name, $roleNames, $actor) {
            if (trim($name) === '' || trim($module) === '') {
                throw ValidationException::withMessages(['workflow' => 'Nama dan modul workflow wajib diisi.']);
            }
            if ($roleNames === []) {
                throw ValidationException::withMessages(['steps' => 'Minimal satu tahap approval wajib ditentukan.']);
            }
            $roles = Role::query()->whereIn('name', $roleNames)->pluck('name')->all();
            if (count($roles) !== count(array_unique($roleNames))) {
                throw ValidationException::withMessages(['steps' => 'Role approval tidak dikenal.']);
            }
            if (ApprovalWorkflow::query()->where('university_id', $universityId)->where('module', $module)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['module' => 'Workflow aktif untuk modul ini sudah tersedia.']);
            }

            $workflow = ApprovalWorkflow::query()->create([
                'university_id' => $universityId,
                'module' => trim($module),
                'name' => trim($name),
                'is_active' => true,
            ]);
            foreach (array_values(array_unique($roleNames)) as $index => $roleName) {
                ApprovalStep::query()->create([
                    'approval_workflow_id' => $workflow->id,
                    'step_order' => $index + 1,
                    'label' => 'Tahap '.($index + 1).' - '.$roleName,
                    'role_name' => $roleName,
                ]);
            }

            AuditLog::query()->create([
                'user_id' => $actor->id, 'event' => 'approval.workflow_created', 'module' => 'approval',
                'entity_type' => ApprovalWorkflow::class, 'entity_id' => $workflow->id,
                'new_values' => ['module' => $module, 'steps' => count($roleNames)],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);

            return $workflow->load('steps');
        });
    }

    public function deactivate(ApprovalWorkflow $workflow, User $actor): ApprovalWorkflow
    {
        return DB::transaction(function () use ($workflow) {
            $locked = ApprovalWorkflow::query()->lockForUpdate()->findOrFail($workflow->id);
            $locked->forceFill(['is_active' => false])->save();

            return $locked->fresh('steps');
        });
    }
}
