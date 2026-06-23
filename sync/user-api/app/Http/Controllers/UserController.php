<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // GET ALL USERS
    public function index()
    {
        return response()->json([
            'message' => 'Daftar semua user berhasil diambil',
            'data' => User::all()
        ]);
    }

    // GET USER BY ID
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Detail user berhasil diambil',
            'data' => $user
        ]);
    }

    // CREATE USER
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        return response()->json([
            'message' => 'User berhasil didaftarkan',
            'data' => $user
        ]);
    }

    // GET USER + LOANS
    public function getUserWithLoans($id)
    {
        $user = User::with(['loans.book'])->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Detail user beserta data peminjaman berhasil diambil',
            'data' => $user
        ]);
    }

    // UPDATE USER
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => 'nullable|min:6'
        ]);

        $data = $request->all();
        if ($request->has('password') && $request->password !== null && $request->password !== '') {
            if (Hash::check($request->password, $user->password)) {
                unset($data['password']);
            } else {
                $data['password'] = bcrypt($request->password);
            }
        } else {
            unset($data['password']);
        }

        $user->fill($data);

        if (!$user->isDirty()) {
            return response()->json([
                'message' => 'tidak ada perubahan data',
                'data' => $user
            ], 200);
        }

        $user->save();

        return response()->json([
            'message' => 'User berhasil diperbarui',
            'data' => $user
        ]);
    }

    // DELETE USER
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        $user->delete();

        // Re-index all IDs sequentially starting from 1
        \Illuminate\Support\Facades\DB::statement('SET @count = 0');
        \Illuminate\Support\Facades\DB::statement('UPDATE users SET id = (@count:=@count+1) ORDER BY id ASC');
        $maxId = \Illuminate\Support\Facades\DB::table('users')->max('id') ?? 0;
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE users AUTO_INCREMENT = " . ($maxId + 1));

        return response()->json(['message' => 'User berhasil dihapus']);
    }
}