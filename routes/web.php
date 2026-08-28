<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CustoFixoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\FichaTecnicaController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PrecificacaoController;
use App\Http\Controllers\TributoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// Autenticação.
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Operação: formar preços e manter o catálogo.
Route::middleware('auth')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('precificacao')->name('precificacao.')->group(function (): void {
        Route::get('/simulador', [PrecificacaoController::class, 'simulador'])->name('simulador');
        Route::post('/calcular', [PrecificacaoController::class, 'calcular'])->name('calcular');
        Route::post('/registrar', [PrecificacaoController::class, 'store'])->name('store');
        Route::get('/historico', [PrecificacaoController::class, 'index'])->name('index');
        Route::get('/{precificacao}', [PrecificacaoController::class, 'show'])->name('show');
    });

    Route::resource('itens', ItemController::class)->parameters(['itens' => 'item']);
    Route::resource('categorias', CategoriaController::class)->except(['show']);
    Route::resource('insumos', InsumoController::class)->except(['show']);

    // Ficha técnica: os insumos que formam uma unidade do produto.
    Route::prefix('itens/{item}/ficha-tecnica')->name('ficha-tecnica.')->group(function (): void {
        Route::get('/', [FichaTecnicaController::class, 'edit'])->name('edit');
        Route::post('/', [FichaTecnicaController::class, 'store'])->name('store');
        Route::post('/sincronizar', [FichaTecnicaController::class, 'sincronizarCusto'])->name('sincronizar');
        Route::put('/', [FichaTecnicaController::class, 'atualizar'])->name('atualizar');
        Route::delete('/{composicao}', [FichaTecnicaController::class, 'destroy'])->name('destroy');
    });

    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
});

// Base do cálculo e usuários: mudam o preço de tudo de uma vez, então ficam
// restritos ao administrador.
Route::middleware(['auth', 'can:administrar'])->group(function (): void {
    Route::resource('tributos', TributoController::class)->except(['show']);
    Route::resource('custos-fixos', CustoFixoController::class)->except(['show']);
    Route::resource('usuarios', UsuarioController::class)->except(['show']);

    Route::prefix('empresas')->name('empresa.')->group(function (): void {
        Route::get('/', [EmpresaController::class, 'index'])->name('index');
        Route::get('/nova', [EmpresaController::class, 'create'])->name('create');
        Route::post('/', [EmpresaController::class, 'store'])->name('store');
        Route::get('/{empresa}/editar', [EmpresaController::class, 'edit'])->name('edit');
        Route::put('/{empresa}', [EmpresaController::class, 'update'])->name('update');
        Route::delete('/{empresa}', [EmpresaController::class, 'destroy'])->name('destroy');
        Route::post('/{empresa}/selecionar', [EmpresaController::class, 'selecionar'])->name('selecionar');
        Route::get('/{empresa}/tributos-sugeridos', [EmpresaController::class, 'tributosSugeridos'])->name('tributos-sugeridos');
        Route::post('/{empresa}/aplicar-sugestao', [EmpresaController::class, 'aplicarSugestao'])->name('aplicar-sugestao');
    });
});
