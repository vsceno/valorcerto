@extends('layouts.app')

@section('titulo', 'Ficha técnica')
@section('subtitulo', $item->nome.' · custo de produção de 1 '.$item->unidade_medida)

@section('acoes')
    <x-botao href="{{ route('itens.show', $item) }}" variante="neutro" icone="fa-arrow-left">Voltar ao item</x-botao>
    @if ($item->composicoes->isNotEmpty())
        <x-botao href="{{ route('precificacao.simulador', ['item' => $item->id]) }}" icone="fa-calculator">
            Precificar
        </x-botao>
    @endif
@endsection

@section('conteudo')
    <div class="grid gap-6 xl:grid-cols-3"
         @if ($item->composicoes->isNotEmpty())
         x-data="{
            linhas: @js($item->composicoes->mapWithKeys(fn ($l) => [$l->id => [
                'quantidade' => (float) $l->quantidade,
                'custo' => round($l->insumo?->custoUnitarioUso() ?? 0, 6),
            ]])),
            original: @js($item->composicoes->mapWithKeys(fn ($l) => [$l->id => (float) $l->quantidade])),
            get total() {
                return Object.values(this.linhas).reduce((s, l) => s + (l.custo * (l.quantidade || 0)), 0)
            },
            get alterado() {
                return Object.entries(this.linhas).some(([id, l]) => Number(l.quantidade) !== Number(this.original[id]))
            },
            subtotal(id) { return this.linhas[id].custo * (this.linhas[id].quantidade || 0) },
            fatia(id) { return this.total > 0 ? (this.subtotal(id) / this.total) * 100 : 0 },
            moeda(v) { return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v || 0) },
            pct(v) { return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v || 0) + '%' },
         }"
         @endif>
        <div class="space-y-6 xl:col-span-2">

            {{-- Adicionar insumo --}}
            <x-card titulo="Adicionar insumo" icone="fa-plus"
                    descricao="A quantidade é sempre na unidade de uso do insumo (metro, m², unidade, hora).">
                @if ($insumosDisponiveis->isEmpty())
                    <x-vazio icone="fa-cubes-stacked" titulo="Nenhum insumo disponível"
                             descricao="Todos os insumos ativos já estão nesta ficha, ou você ainda não cadastrou nenhum.">
                        <x-botao href="{{ route('insumos.create') }}" icone="fa-plus">Cadastrar insumo</x-botao>
                    </x-vazio>
                @else
                    <form method="POST" action="{{ route('ficha-tecnica.store', $item) }}"
                          x-data="{
                              insumos: @js($insumosDisponiveis->mapWithKeys(fn ($i) => [$i->id => [
                                  'unidade' => $i->unidade_uso,
                                  'custo' => round($i->custoUnitarioUso(), 6),
                                  'conversao' => $i->conversao,
                              ]])),
                              selecionado: '',
                              quantidade: 1,
                              get info() { return this.insumos[this.selecionado] ?? null },
                              get subtotal() { return this.info ? this.info.custo * this.quantidade : 0 },
                              moeda(v) { return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v || 0) },
                          }"
                          class="space-y-4">
                        @csrf

                        <div class="grid gap-4 sm:grid-cols-12">
                            <div class="sm:col-span-6">
                                <label for="insumo_id" class="block text-sm font-medium text-slate-700">Insumo</label>
                                <select name="insumo_id" id="insumo_id" x-model="selecionado"
                                        class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                                    <option value="">Selecione...</option>
                                    @foreach ($insumosDisponiveis->groupBy('grupo') as $grupo => $lista)
                                        <optgroup label="{{ \Illuminate\Support\Str::before(\App\Models\Insumo::GRUPOS[$grupo] ?? $grupo, ' (') }}">
                                            @foreach ($lista as $opcao)
                                                <option value="{{ $opcao->id }}">
                                                    {{ $opcao->nome }} — R$ {{ number_format($opcao->custoUnitarioUso(), 4, ',', '.') }}/{{ $opcao->unidade_uso }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="quantidade" class="block text-sm font-medium text-slate-700">Quantidade</label>
                                <div class="mt-1.5 flex rounded-lg shadow-sm">
                                    <input type="number" name="quantidade" id="quantidade" step="0.0001" min="0.0001"
                                           x-model.number="quantidade"
                                           class="tabular block w-full min-w-0 rounded-l-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                                    <span class="inline-flex min-w-14 items-center justify-center rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500"
                                          x-text="info ? info.unidade : '—'"></span>
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <span class="block text-sm font-medium text-slate-700">Custo na peça</span>
                                <p class="tabular mt-1.5 rounded-lg bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-800"
                                   x-text="moeda(subtotal)"></p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <input type="text" name="observacao" placeholder="Observação (opcional): medida, corte, etapa..."
                                   class="min-w-56 flex-1 rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                            <x-botao icone="fa-plus" ::disabled="! selecionado">Adicionar à ficha</x-botao>
                        </div>

                        <p x-show="info" x-cloak class="text-sm text-slate-500">
                            <i class="fa-solid fa-arrows-left-right text-marca-500"></i>
                            Conversão do insumo: <span x-text="info?.conversao"></span>
                        </p>
                    </form>
                @endif
            </x-card>

            {{-- Linhas da ficha: todas as quantidades editáveis de uma vez --}}
            <x-card titulo="Composição de 1 {{ $item->unidade_medida }}" icone="fa-list-check"
                    descricao="Altere as quantidades direto na tabela. O custo recalcula na hora; salve quando terminar.">
                @if ($item->composicoes->isEmpty())
                    <x-vazio icone="fa-clipboard-list" titulo="Ficha técnica vazia"
                             descricao="Enquanto não houver insumos aqui, a precificação usa o custo variável digitado no cadastro do item." />
                @else
                    <div>
                        <form method="POST" action="{{ route('ficha-tecnica.atualizar', $item) }}" id="form-ficha">
                            @csrf @method('PUT')

                            <div class="-mx-5 overflow-x-auto sm:-mx-6">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                            <th class="px-5 pb-2 sm:px-6">Insumo</th>
                                            <th class="px-3 pb-2 text-center">Quantidade consumida</th>
                                            <th class="px-3 pb-2 text-right">Custo unitário</th>
                                            <th class="px-3 pb-2 text-right">Subtotal</th>
                                            <th class="px-3 pb-2 text-right">%</th>
                                            <th class="px-5 pb-2 text-right sm:px-6"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($item->composicoes as $linha)
                                            <tr class="align-top transition hover:bg-slate-50">
                                                <td class="px-5 py-3 sm:px-6">
                                                    <p class="font-medium text-slate-900">{{ $linha->insumo?->nome ?? 'Insumo removido' }}</p>
                                                    <p class="text-xs text-slate-500">
                                                        {{ \Illuminate\Support\Str::before($linha->insumo?->grupo_label ?? '', ' (') }}
                                                        @if ($linha->insumo?->exigeConversao())
                                                            · {{ $linha->insumo->conversao }}
                                                        @endif
                                                        @if ((float) ($linha->insumo?->perda_percentual ?? 0) > 0)
                                                            · perda @pct($linha->insumo->perda_percentual)
                                                        @endif
                                                    </p>
                                                    <input type="text" name="linhas[{{ $linha->id }}][observacao]"
                                                           value="{{ old("linhas.{$linha->id}.observacao", $linha->observacao) }}"
                                                           placeholder="Observação: medida, corte, etapa..."
                                                           class="mt-1.5 w-full max-w-xs rounded-md border-0 bg-transparent px-0 py-0.5 text-xs italic text-slate-500 placeholder:text-slate-300 focus:bg-white focus:px-2 focus:ring-1 focus:ring-marca-300">
                                                </td>

                                                <td class="px-3 py-3">
                                                    <div class="mx-auto flex w-40 items-center rounded-lg border border-slate-300 bg-white shadow-sm focus-within:border-marca-500 focus-within:ring-2 focus-within:ring-marca-200">
                                                        <input type="number" step="0.0001" min="0.0001"
                                                               name="linhas[{{ $linha->id }}][quantidade]"
                                                               x-model.number="linhas[{{ $linha->id }}].quantidade"
                                                               class="tabular w-full rounded-l-lg border-0 px-2 py-2 text-right text-sm focus:ring-0">
                                                        <span class="shrink-0 rounded-r-lg border-l border-slate-200 bg-slate-50 px-2.5 py-2 text-xs font-medium text-slate-500">
                                                            {{ $linha->insumo?->unidade_uso }}
                                                        </span>
                                                    </div>
                                                    @error("linhas.{$linha->id}.quantidade")
                                                        <p class="mt-1 text-center text-xs text-rose-600">{{ $message }}</p>
                                                    @enderror
                                                </td>

                                                <td class="tabular px-3 py-3 text-right text-slate-600">
                                                    R$ {{ number_format($linha->insumo?->custoUnitarioUso() ?? 0, 4, ',', '.') }}
                                                </td>
                                                <td class="tabular px-3 py-3 text-right font-semibold text-slate-900"
                                                    x-text="moeda(subtotal({{ $linha->id }}))"></td>
                                                <td class="tabular px-3 py-3 text-right text-slate-500"
                                                    x-text="pct(fatia({{ $linha->id }}))"></td>
                                                <td class="px-5 py-3 text-right sm:px-6">
                                                    <button type="button"
                                                            @click="$refs['remover{{ $linha->id }}'].submit()"
                                                            class="rounded-lg px-2 py-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                                            title="Remover da ficha">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t-2 border-slate-200">
                                            <td colspan="3" class="px-5 pt-3 font-semibold text-slate-700 sm:px-6">
                                                Custo de produção de 1 {{ $item->unidade_medida }}
                                            </td>
                                            <td class="tabular px-3 pt-3 text-right text-lg font-bold text-emerald-700"
                                                x-text="moeda(total)"></td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                                <x-botao variante="sucesso" icone="fa-floppy-disk" ::disabled="! alterado">
                                    Salvar quantidades
                                </x-botao>
                                <button type="button" x-show="alterado" x-cloak
                                        @click="Object.keys(linhas).forEach(id => linhas[id].quantidade = original[id])"
                                        class="text-sm font-medium text-slate-500 hover:text-slate-700">
                                    Descartar alterações
                                </button>
                                <p x-show="alterado" x-cloak class="text-sm text-amber-700">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    Alterações ainda não salvas.
                                </p>
                                <p x-show="! alterado" class="text-sm text-slate-400">
                                    Digite nas quantidades para simular o efeito no custo.
                                </p>
                            </div>
                        </form>

                        {{-- Formulários de remoção ficam fora do formulário principal --}}
                        @foreach ($item->composicoes as $linha)
                            <form method="POST" action="{{ route('ficha-tecnica.destroy', [$item, $linha]) }}"
                                  x-ref="remover{{ $linha->id }}" class="hidden"
                                  onsubmit="return confirm('Remover {{ addslashes($linha->insumo?->nome ?? 'este insumo') }} da ficha?')">
                                @csrf @method('DELETE')
                            </form>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Coluna lateral --}}
        <div class="space-y-6">
            <div class="overflow-hidden rounded-2xl bg-emerald-950 shadow-lg shadow-emerald-950/20 xl:sticky xl:top-24">
                <div class="px-6 py-5">
                    <p class="text-sm font-medium text-emerald-100/70">Custo variável unitário</p>

                    @if ($item->composicoes->isEmpty())
                        <p class="tabular mt-1 text-3xl font-bold text-white">@moeda($custoTotal)</p>
                    @else
                        <p class="tabular mt-1 text-3xl font-bold text-white" x-text="moeda(total)"></p>
                        <p x-show="alterado" x-cloak class="mt-1 text-sm text-amber-300">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            Simulando — salvo: @moeda($custoTotal)
                        </p>
                    @endif

                    <p class="mt-1 text-sm text-emerald-100/60">
                        {{ $item->composicoes->count() }} {{ $item->composicoes->count() === 1 ? 'insumo' : 'insumos' }}
                        · por {{ $item->unidade_medida }}
                    </p>
                </div>
                <div class="border-t border-white/10 px-6 py-4 text-sm text-emerald-100/70">
                    <i class="fa-solid fa-circle-check text-emerald-400"></i>
                    Este valor entra automaticamente na fórmula de precificação como
                    <strong class="text-white">Custo Variável Unitário</strong>.
                </div>
            </div>

            @if ($porGrupo->isNotEmpty())
                <x-card titulo="Peso por grupo" icone="fa-chart-pie">
                    @foreach ($porGrupo as $grupo => $valor)
                        @php $fatia = $custoTotal > 0 ? ($valor / $custoTotal) * 100 : 0; @endphp
                        <div class="py-2.5 first:pt-0">
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="truncate text-slate-600">
                                    {{ \Illuminate\Support\Str::before(\App\Models\Insumo::GRUPOS[$grupo] ?? $grupo, ' (') }}
                                </span>
                                <span class="tabular shrink-0 font-medium text-slate-900">@moeda($valor)</span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $fatia }}%"></div>
                            </div>
                            <p class="tabular mt-1 text-xs text-slate-400">@pct($fatia) do custo de produção</p>
                        </div>
                    @endforeach
                </x-card>
            @endif

            @if ($item->composicoes->isNotEmpty())
                @php $custoCadastro = (float) $item->custo_variavel_unitario; @endphp
                @if (abs($custoCadastro - $custoTotal) > 0.01)
                    <x-alerta tipo="atencao" titulo="Custo do cadastro está diferente">
                        O cadastro do item guarda @moeda($custoCadastro), mas a ficha apura @moeda($custoTotal).
                        A precificação usa o valor da ficha; sincronize se quiser alinhar os dois.
                        <form method="POST" action="{{ route('ficha-tecnica.sincronizar', $item) }}" class="mt-3">
                            @csrf
                            <x-botao variante="neutro" icone="fa-arrows-rotate" class="!px-3 !py-2 text-xs">
                                Sincronizar custo do cadastro
                            </x-botao>
                        </form>
                    </x-alerta>
                @endif
            @endif

            <x-alerta tipo="info" titulo="Por que separar compra de uso">
                Você compra metalon em vara de 6 m, mas a catraca consome 4,2 m. Cadastrando o
                rendimento, o sistema encontra o custo por metro e multiplica pelo consumo real —
                sem contas paralelas em planilha.
            </x-alerta>
        </div>
    </div>
@endsection
