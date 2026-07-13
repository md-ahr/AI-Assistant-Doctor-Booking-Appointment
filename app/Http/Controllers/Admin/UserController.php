<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/users/index', [
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'is_admin', 'created_at']),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->create($data);
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('admin.users.index');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($request->user()->is($user) && ! $data['is_admin']) {
            abort(403, 'You cannot demote your own account.');
        }

        if ($user->is_admin && ! $data['is_admin'] && $this->isLastAdmin($user)) {
            abort(403, 'You cannot demote the last admin.');
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => $data['is_admin'],
        ]);

        return redirect()->route('admin.users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (request()->user()->is($user)) {
            abort(403, 'You cannot delete your own account.');
        }

        if ($user->is_admin && $this->isLastAdmin($user)) {
            abort(403, 'You cannot delete the last admin.');
        }

        $user->delete();

        return redirect()->route('admin.users.index');
    }

    private function isLastAdmin(User $user): bool
    {
        if (! $user->is_admin) {
            return false;
        }

        return User::query()->where('is_admin', true)->count() === 1;
    }
}
