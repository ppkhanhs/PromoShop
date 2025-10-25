<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CassandraDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(protected CassandraDataService $dataService)
    {
    }

    public function index(): View
    {
        $users = $this->dataService->fetchUsers();

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validateUser($request);

        $this->dataService->saveUser($payload);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã tạo người dùng mới.');
    }

    public function edit(string $user): View
    {
        $item = $this->dataService->fetchUserById($user);
        if (!$item) {
            abort(404);
        }

        return view('admin.users.form', [
            'user' => $item,
        ]);
    }

    public function update(Request $request, string $user): RedirectResponse
    {
        $payload = $this->validateUser($request, $user);

        $this->dataService->saveUser($payload, $user);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', 'Đã cập nhật người dùng.');
    }

    public function destroy(string $user): RedirectResponse
    {
        $this->dataService->deleteUser($user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã xóa người dùng.');
    }

    protected function validateUser(Request $request, ?string $userId = null): array
    {
        $rules = [
            'user_id' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:6'],
            'role' => ['nullable', 'string', 'max:50'],
        ];

        if ($userId) {
            $rules['user_id'][] = function ($attribute, $value, $fail) use ($userId) {
                if ($value !== $userId) {
                    $fail('Không thể thay đổi mã người dùng.');
                }
            };
        }

        $data = $request->validate($rules);

        $payload = [
            'user_id' => strtoupper($data['user_id']),
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'role' => $data['role'] ?? 'customer',
        ];

        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        return $payload;
    }
}

