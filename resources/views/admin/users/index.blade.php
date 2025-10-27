@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Người dùng')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">Người dùng</h1>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Thêm người dùng
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Mã người dùng</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->get('user_id') }}</td>
                            <td>{{ $user->get('name') }}</td>
                            <td>{{ $user->get('email') }}</td>
                            <td>{{ ucfirst($user->get('role', 'customer')) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.users.edit', $user->get('user_id')) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                                <form action="{{ route('admin.users.destroy', $user->get('user_id')) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Xóa người dùng này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Chưa có người dùng nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

