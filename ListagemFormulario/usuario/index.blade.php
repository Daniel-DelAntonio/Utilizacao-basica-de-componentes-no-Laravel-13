@extends('layouts.app')

@section('title', 'Lista de Usuários')
@section('page-title', 'Lista de Usuários')

@section('content')
<div class="bg-white rounded-md border border-slate-200 overflow-hidden">

    {{-- Toolbar --}}
    <div class="bg-[#141520] px-4 py-3 flex flex-wrap items-center justify-end gap-3">

        <div class="flex items-center gap-2">

            <a href="{{ route('users.create') }}">
                <button class="p-2 bg-slate-700 hover:bg-slate-600 rounded-md text-white" title="Novo">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
            </a>

            <details class="relative">
                <summary class="list-none cursor-pointer flex items-center gap-1.5 px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded-md text-white text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                    </svg>
                    Exportar
                </summary>
                <div class="absolute right-0 mt-1 w-32 bg-white border border-slate-200 rounded-md shadow-lg py-1 z-20">
                    <a href="{{ route('exports.csv', array_merge(['model' => 'usuarios'], request()->only(['busca', 'function_name', 'data_inicio', 'data_fim']))) }}"
                        class="block px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">CSV</a>
                    <a href="{{ route('exports.pdf', array_merge(['model' => 'usuarios'], request()->only(['busca', 'function_name', 'data_inicio', 'data_fim']))) }}"
                        class="block px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">PDF</a>
                    <a href="{{ route('exports.xlsx', array_merge(['model' => 'usuarios'], request()->only(['busca', 'function_name', 'data_inicio', 'data_fim']))) }}"
                        class="block px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Excel</a>
                </div>
            </details>

            <select class="bg-slate-700 text-white text-sm rounded-md px-2 py-2 border-0">
                <option>10</option>
                <option>25</option>
                <option>50</option>
            </select>
        </div>
    </div>

    {{-- Table --}}
    <table class="w-full text-sm">
        <thead>
            <tr class="  border-b border-slate-400 text-left text-slate-600">
                <th class="w-20 px-4 py-3"></th>
                <th class="px-4 py-3 font-semibold">Nome</th>
                <th class="px-4 py-3 font-semibold">Login</th>
                <th class="px-4 py-3 font-semibold">E-mail</th>
                <th class="px-4 py-3 font-semibold">Função</th>
                <th class="px-4 py-3 font-semibold">Unidade</th>
            </tr>
        </thead>
        <tbody>
            @forelse($usuario as $user)
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3 text-slate-500">
                        <a href="{{ route('users.edit', $user->id) }}" class="hover:text-brand-gold" title="Editar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </a>
                        <form method="POST" action="{{ route('users.destroy', $user->id) }}"
                            onsubmit="return confirm('Deseja realmente excluir este usuário?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="hover:text-red-500" title="Excluir">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </td>
                <td class="px-4 py-3">{{ $user->name }}</td>
                <td class="px-4 py-3">{{ $user->login }}</td>
                <td class="px-4 py-3">{{ $user->email }}</td>
                <td class="px-4 py-3">{{ $user->function_name ?? '-' }}</td>
                <td class="px-4 py-3">{{ $user->system_unit_id ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                    Nenhum usuário encontrado.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination footer --}}
    <div class="bg-[#f1a839] px-4 py-4 text-center">
        <p class="text-sm text-slate-900 font-medium mb-3">
            {{ $usuario->firstItem() ?? 0 }} a {{ $usuario->lastItem() ?? 0 }} de {{ $usuario->total() ?? 0 }} registros
        </p>
        <div class="flex justify-center">
            {{ $usuario->links() }}
        </div>
    </div>
</div>
@endsection