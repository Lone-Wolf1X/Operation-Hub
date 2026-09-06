<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::orderBy('id', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['admin', 'maker', 'checker'])],
            'tenant_id' => 'required|string',
            'staff_id' => 'nullable|string',
            'branch' => 'nullable|string',
            'branch_sol' => 'nullable|string',
            'contact_number' => 'nullable|string'
        ]);

        $validated['password'] = Hash::make($validated['password']);
        
        $user = User::create($validated);
        return response()->json(['success' => true, 'data' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'maker', 'checker'])],
            'tenant_id' => 'required|string',
            'staff_id' => 'nullable|string',
            'branch' => 'nullable|string',
            'branch_sol' => 'nullable|string',
            'contact_number' => 'nullable|string'
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);
        return response()->json(['success' => true, 'data' => $user]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['success' => true, 'message' => 'User deleted successfully']);
    }
}
