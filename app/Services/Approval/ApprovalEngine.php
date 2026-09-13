<?php

namespace App\Services\Approval;

use App\Models\ApprovalAction;
use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalEngine
{
    public function request(Model $approvable, string $universityId, string $module, User $requester): ApprovalRequest
    {
        return DB::transaction(function () use ($approvable, $universityId, $module, $requester) {
            $workflow = ApprovalWorkflow::query()->where('university_id', $universityId)->where('module', $module)->where('is_active', true)->with('steps')->first();
            if (! $workflow || $workflow->steps->isEmpty()) throw ValidationException::withMessages(['approval' => "Workflow approval untuk {$module} belum dikonfigurasi."]);
            return ApprovalRequest::create(['approval_workflow_id' => $workflow->id, 'approvable_type' => $approvable::class, 'approvable_id' => $approvable->getKey(), 'requested_by' => $requester->id, 'status' => 'pending']);
        });
    }

    public function act(ApprovalRequest $request, User $actor, string $action, ?string $note = null): ApprovalRequest
    {
        return DB::transaction(function () use ($request, $actor, $action, $note) {
            $locked = ApprovalRequest::query()->with(['workflow.steps', 'actions'])->lockForUpdate()->findOrFail($request->id);
            if ($locked->status !== 'pending') throw ValidationException::withMessages(['approval' => 'Approval request sudah selesai.']);
            $step = $locked->workflow->steps->sortBy('step_order')->values()->get($locked->actions->count());
            if (! $step || ! $actor->hasRole($step->role_name) && ! $actor->hasRole('super_admin')) throw ValidationException::withMessages(['approval' => 'Anda tidak berwenang pada tahap approval ini.']);
            if (! in_array($action, ['approved', 'rejected'], true)) throw ValidationException::withMessages(['action' => 'Action approval tidak valid.']);
            ApprovalAction::create(['approval_request_id' => $locked->id, 'approval_step_id' => $step->id, 'acted_by' => $actor->id, 'action' => $action, 'note' => $note, 'acted_at' => now()]);
            $locked->status = $action === 'rejected' || $locked->actions->count() + 1 >= $locked->workflow->steps->count() ? $action : 'pending';
            $locked->save();
            return $locked->fresh(['approvable', 'actions']);
        });
    }
}
