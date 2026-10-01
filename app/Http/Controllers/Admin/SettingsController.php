<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\UpdateSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Support\Settings\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SettingsController extends Controller
{
    public function edit(Settings $settings): View
    {
        Gate::authorize('system.settings.view');

        $groups = $settings->groups();

        return view('admin.settings.edit', [
            'groups'  => $groups,
            'values'  => collect($groups)->map(fn ($group, $key) => $settings->forGroup($key))->all(),
            'canEdit' => Gate::allows('system.settings.edit'),
        ]);
    }

    public function update(UpdateSettingsRequest $request, string $group, UpdateSettings $action): RedirectResponse
    {
        Gate::authorize('system.settings.edit');

        $changed = $action->handle($request->user(), $group, $request->fields());

        return to_route('admin.settings.edit')
            ->withFragment($group)
            ->with('settings_group', $group)
            ->with('status', empty($changed) ? 'No changes.' : 'Settings saved.');
    }
}