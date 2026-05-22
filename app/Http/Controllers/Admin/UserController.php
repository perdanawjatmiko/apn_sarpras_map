<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly AdminPageService $admins) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('admin/index', [
            'resource' => 'users',
            'title' => 'User',
            'search' => $search,
            'stats' => $this->admins->dashboard(),
            'records' => User::query()
                ->select(['id', 'name', 'phone', 'email', 'created_at'])
                ->when($search, fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'options' => [
                'koperasis' => [],
                'sarprases' => [],
                'statuses' => [],
                'cities' => [],
                'districts' => [],
                'villages' => [],
                'users' => [],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->admins->createUser($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', 'string', 'min:8'],
        ]));

        return back()->with('success', 'User dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->admins->updateUser($user, $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users')->ignore($user)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'string', 'min:8'],
        ]));

        return back()->with('success', 'User diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return back()->with('success', 'User dihapus.');
    }
}
