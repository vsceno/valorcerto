<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Item;
use App\Models\Precificacao;
use App\Models\Tributo;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $empresa = Empresa::atual();

        if (! $empresa) {
            return view('dashboard.sem-empresa');
        }

        $custoFixoTotal = $empresa->custoFixoTotalMensal();

        $cargaProduto = (float) Tributo::query()
            ->where('empresa_id', $empresa->id)
            ->ativos()
            ->aplicaveisA('produto')
            ->sum('aliquota_efetiva');

        $cargaServico = (float) Tributo::query()
            ->where('empresa_id', $empresa->id)
            ->ativos()
            ->aplicaveisA('servico')
            ->sum('aliquota_efetiva');

        $precificacoes = Precificacao::query()
            ->where('empresa_id', $empresa->id)
            ->with('item')
            ->recentes()
            ->limit(8)
            ->get();

        $agregados = Precificacao::query()
            ->where('empresa_id', $empresa->id)
            ->selectRaw('COUNT(*) as total, AVG(preco_venda) as preco_medio, AVG(margem_contribuicao) as margem_media')
            ->first();

        return view('dashboard.index', [
            'empresa' => $empresa,
            'custoFixoTotal' => $custoFixoTotal,
            'cargaProduto' => $cargaProduto,
            'cargaServico' => $cargaServico,
            'totalItens' => Item::query()->where('empresa_id', $empresa->id)->ativos()->count(),
            'totalTributos' => Tributo::query()->where('empresa_id', $empresa->id)->ativos()->count(),
            'totalPrecificacoes' => (int) ($agregados->total ?? 0),
            'precoMedio' => (float) ($agregados->preco_medio ?? 0),
            'margemMedia' => (float) ($agregados->margem_media ?? 0),
            'precificacoes' => $precificacoes,
            'itensSemPreco' => Item::query()
                ->where('empresa_id', $empresa->id)
                ->ativos()
                ->whereDoesntHave('precificacoes')
                ->count(),
        ]);
    }
}
