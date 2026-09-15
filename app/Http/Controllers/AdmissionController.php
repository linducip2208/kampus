<?php

namespace App\Http\Controllers;

use App\Models\AdmissionPath;
use App\Models\University;
use App\Services\Admission\ApplicantPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    public function landing(): View
    {
        $university = University::query()->first();
        $paths = $university
            ? AdmissionPath::query()->where('university_id', $university->id)->where('status', 'active')->get()
            : collect();

        return view('admission.landing', compact('university', 'paths'));
    }

    public function register(Request $request, ApplicantPortalService $service): RedirectResponse
    {
        $university = University::query()->firstOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],
            'admission_path_id' => ['required', 'exists:admission_paths,id'],
        ]);
        $service->register($university->id, $data);
        Auth::attempt(['email' => $data['email'], 'password' => $data['password']]);
        $request->session()->regenerate();

        return redirect()->route('admission.dashboard')->with('success', 'Pendaftaran dibuat, lengkapi biodata Anda.');
    }
}
