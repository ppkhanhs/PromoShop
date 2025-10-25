<?php

namespace App\Http\Controllers;

use App\Services\CassandraDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(protected CassandraDataService $dataService)
    {
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $remember = (bool) ($credentials['remember'] ?? false);
        unset($credentials['remember']);

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('client.home'))
                ->with('success', 'Đăng nhập thành công.');
        }

        throw ValidationException::withMessages([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ]);
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
        ];

        $result = $this->dataService->registerUser($payload);
        if (!$result) {
            throw ValidationException::withMessages([
                'email' => 'Không thể tạo tài khoản mới, vui lòng thử lại.',
            ]);
        }

        Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $request->session()->regenerate();

        return redirect()->route('client.home')
            ->with('success', 'Đăng ký tài khoản thành công.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.home')
            ->with('success', 'Đăng xuất thành công.');
    }
}

