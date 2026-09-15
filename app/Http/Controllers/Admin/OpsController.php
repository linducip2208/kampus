<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\IntegrationEndpoint;
use App\Models\JournalEntry;
use App\Models\LetterRequest;
use App\Services\Reporting\ReportingService;
use App\Services\Security\UniversityScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpsController extends Controller
{
    public function index(Request $request, UniversityScope $scope, ReportingService $reporting): View
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak', 'finance', 'auditor']), 403);

        $letters = $scope->relation(LetterRequest::query()->with(['template', 'enrollment.studentProfile'])->latest(), $request->user(), 'template')->paginate(15, ['*'], 'letter_page');
        $assets = $scope->direct(Asset::query()->latest(), $request->user())->paginate(15, ['*'], 'asset_page');
        $journals = $scope->direct(JournalEntry::query()->withCount('lines')->latest(), $request->user())->paginate(15, ['*'], 'journal_page');
        $endpoints = $scope->direct(IntegrationEndpoint::query()->withCount('logs')->latest(), $request->user())->get();
        $summary = $reporting->executiveSummary($request->user());

        return view('admin.ops', compact('letters', 'assets', 'journals', 'endpoints', 'summary'));
    }
}
