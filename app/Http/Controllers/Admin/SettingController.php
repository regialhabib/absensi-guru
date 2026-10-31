<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Services\SchoolSettingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(private SchoolSettingService $settings) {}

    public function edit(): Response
    {
        return Inertia::render('admin/settings/edit', $this->settings->get());
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $schedules = $validated['schedules'];
        unset($validated['schedules']);

        $this->settings->update($validated, $schedules);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengaturan berhasil disimpan.']);

        return back();
    }
}
