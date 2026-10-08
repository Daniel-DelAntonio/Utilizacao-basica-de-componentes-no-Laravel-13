<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'busca' => ['nullable', 'string', 'max:255'],
            'function_name' => ['nullable', Rule::in(['Usuario', 'Administrador', 'Programador'])],
        ]);

        $usuario = User::query()
            ->when($filters['function_name'] ?? null, fn ($query, $functionName) => $query->where('function_name', $functionName))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('usuario.index', compact('usuario'));
    }

    public function create()
    {
        return view('usuario.formUsuario');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255', 'unique:users,login'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'function_name' => ['required', 'string', Rule::in(['Usuario', 'Administrador', 'Programador'])],
            'system_unit_id' => ['nullable', 'integer', 'min:1'],
            'password' => ['required', 'string', 'min:3', 'confirmed'],
        ]);

        User::create($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(User $user)
    {
        return view('usuario.formUsuario', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'login' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'login')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'function_name' => ['required', 'string', Rule::in(['Usuario', 'Administrador', 'Programador'])],
            'system_unit_id' => ['nullable', 'integer', 'min:1'],
            'password' => ['nullable', 'string', 'min:3', 'confirmed'],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuário excluído com sucesso.');
    }
}
