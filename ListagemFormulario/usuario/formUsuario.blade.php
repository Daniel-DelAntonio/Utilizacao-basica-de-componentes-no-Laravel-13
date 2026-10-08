@extends('layouts.app')

@section('title', isset($user) ? 'Editar Usuário' : 'Novo Usuário')
@section('page-title', isset($user) ? 'Editar Usuário' : 'Novo Usuário')

@section('content')
<div class="bg-white rounded-md border border-slate-200 overflow-hidden">

    <div class="bg-[#141520] px-4 py-4 flex flex-wrap items-center justify-between gap-3">
    </div>

    <div class="bg-white overflow-hidden">
        <form action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}" method="POST" class="space-y-5 h-full p-8" id="formUsuario">
            @csrf
            @isset($user)
            @method('PUT')
            @endisset

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-black">Nome</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name ?? '') }}" required
                        class="mt-1 block w-full rounded-md border-slate-500 border-1">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="login" class="block text-sm font-medium text-black">Login</label>
                    <input id="login" name="login" type="text" value="{{ old('login', $user->login ?? '') }}" required
                        class="mt-1 block w-full rounded-md border-slate-500 border-1">
                    @error('login') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-black">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required
                        class="mt-1 block w-full rounded-md border-slate-500 border-1">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="function_name" class="block text-sm font-medium text-black">Função</label>
                    <!--<input id="function_name" name="function_name" type="text" value="{{ old('function_name', $user->function_name ?? '') }}"
                        class="mt-1 block w-full rounded-md border-slate-500 border-1">-->

                    <select id="function_name" name="function_name" required
                        class="mt-1 w-full rounded-md border-slate-500 border-1">
                        <option value="">Selecione</option>
                        <option value="Usuario" @selected(old('function_name', $user->function_name ?? '') === 'Usuario')>Usuário</option>
                        <option value="Administrador" @selected(old('function_name', $user->function_name ?? '') === 'Administrador')>Administrador</option>
                        <option value="Programador" @selected(old('function_name', $user->function_name ?? '') === 'Programador')>Programador</option>
                    </select>

                    @error('function_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="system_unit_id" class="block text-sm font-medium text-black">Unidade do sistema</label>
                    <!--<input id="system_unit_id" name="system_unit_id" type="number" min="1" value="{{ old('system_unit_id', $user->system_unit_id ?? '') }}"
                        class="mt-1 block w-full rounded-md border-slate-500 border-1">
                        @error('system_unit_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror-->
                    <select id="system_unit_id" name="system_unit_id" required
                        class="mt-1 w-full rounded-md border-slate-500 border-1">
                        <option value="">Selecione</option>
                        <option value="1">Unit A</option>
                        <option value="2">Unit B</option>
                    </select>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-black">
                        {{ isset($user) ? 'Nova senha' : 'Senha' }}
                    </label>
                    <input id="password" name="password" type="password" {{ isset($user) ? '' : 'required' }}
                        class="mt-1 block w-full rounded-md border-slate-500 border-1">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-black">Confirmar senha</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                        class="mt-1 block w-full rounded-md border-slate-500 border-1">
                </div>
            </div>
        </form>
    </div>

    <div class="bg-[#f1a839] overflow-hidden">
        <div class="flex justify-end gap-3 p-3">

            <button onclick="voltarPagina()" class="rounded-md bg-slate-200 px-4 py-2 text-sm text-black border-1 border-slate-500 hover:bg-slate-700">Cancelar</button>

            <button type="submit" form="formUsuario" class="rounded-md bg-slate-200 px-4 py-2 text-sm text-black border-1 border-slate-500 hover:bg-slate-700">
                {{ isset($user) ? 'Salvar usuário' : 'Salvar usuário' }}
            </button>


        </div>
    </div>
</div>

@endsection