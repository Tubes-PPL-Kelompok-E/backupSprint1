<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // READ
    public function index(Request $request)
    {
        $users = User::withTrashed()
            ->with('roles')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")))
            ->when($request->role, fn ($q, $r) => $q->role($r))
            ->when($request->instansi, fn ($q, $i) => $q->where('instansi', $i))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Pilihan filter instansi diambil dari data akun yang ada
        $daftarInstansi = User::withTrashed()->whereNotNull('instansi')->distinct()->orderBy('instansi')->pluck('instansi');

        return view('users.index', compact('users', 'daftarInstansi'));
    }

    // CREATE
    public function create()
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->safe()->only(['name', 'email', 'instansi', 'password']));
        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'Akun berhasil dibuat.');
    }

    // UPDATE (nama, peran, instansi)
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        // Admin tidak boleh menurunkan perannya sendiri
        if ($user->is($request->user()) && $request->role !== 'admin_pmi') {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah peran akun Anda sendiri.']);
        }

        $user->update($request->safe()->only(['name', 'instansi']));
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->with('success', 'Data akun berhasil diperbarui.');
    }

    // UPDATE (reset kata sandi)
    public function resetPassword(ResetPasswordRequest $request, User $user)
    {
        $user->update(['password' => $request->password]);

        return redirect()->route('users.index')->with('success', 'Kata sandi berhasil direset.');
    }

    // DELETE (soft delete = nonaktifkan akun)
    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Akun berhasil dinonaktifkan.');
    }

    // Aktifkan kembali akun nonaktif
    public function restore(int $id)
    {
        User::onlyTrashed()->findOrFail($id)->restore();

        return redirect()->route('users.index')->with('success', 'Akun berhasil diaktifkan kembali.');
    }
}