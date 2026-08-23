@extends('layouts.app')

@section('titulo', $insumo->exists ? 'Editar insumo' : 'Novo insumo')
@section('subtitulo', 'Separe como você compra de como você consome — o sistema faz a conversão')

@section('conteudo')
    <form method="POST" action="{{ $insumo->exists ? route('insumos.update', $insumo) : route('insumos.store') }}"
          x-data="{
              preco: {{ (float) old('preco_compra', $insumo->preco_compra ?? 0) }},
              rendimento: {{ (float) old('rendimento', $insumo->rendimento ?? 1) }},
              perda: {{ (float) old('perda_percentual', $insumo->perda_percentual ?? 0) }},
              unidadeUso: @js(old('unidade_uso', $insumo->unidade_uso ?? 'UN')),
              unidadeCompra: @js(old('unidade_compra', $insumo->unidade_compra ?? 'UN')),
              get custoBase() { return this.rendimento > 0 ? this.preco / this.rendimento : 0 },
              get custoFinal() { return this.custoBase * (1 + this.perda / 100) },
              moeda(v) { return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', minimumFractionDigits: 4 }).format(v || 0) },
          }"
          class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($insumo->exists) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <x-card titulo="Identificação" icone="fa-tag">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-campo nome="nome" rotulo="Nome" :valor="$insumo->nome" obrigatorio
                                 ajuda="Ex.: Metalon 100x40x2mm, Chapa de aço 1,5mm, Mecanismo de giro tripé." />
                    </div>
                    <x-campo nome="codigo" rotulo="Código" :valor="$insumo->codigo" ajuda="Opcional, único na empresa." />
                    <x-campo nome="fornecedor" rotulo="Fornecedor" :valor="$insumo->fornecedor" />
                    <div class="sm:col-span-2">
                        <x-selecao nome="grupo" rotulo="Grupo" :opcoes="\App\Models\Insumo::GRUPOS"
                                   :valor="$insumo->grupo" obrigatorio />
                    </div>
                </div>
            </x-card>

            <x-card titulo="Como você compra" icone="fa-cart-shopping"
                    descricao="O preço e a unidade que aparecem na nota fiscal do fornecedor.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="preco_compra" class="block text-sm font-medium text-slate-700">
                            Preço de compra <span class="text-rose-500">*</span>
                        </label>
                        <div class="mt-1.5 flex rounded-lg shadow-sm">
                            <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">R$</span>
                            <input type="number" id="preco_compra" name="preco_compra" step="0.0001" min="0"
                                   x-model.number="preco"
                                   class="tabular block w-full min-w-0 rounded-r-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                        </div>
                        @error('preco_compra')
                            <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="unidade_compra" class="block text-sm font-medium text-slate-700">
                            Unidade de compra <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="unidade_compra" name="unidade_compra" list="unidades-compra"
                               x-model="unidadeCompra"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm uppercase shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                        <datalist id="unidades-compra">
                            <option value="VARA">Vara / barra</option>
                            <option value="CHAPA">Chapa</option>
                            <option value="ROLO">Rolo</option>
                            <option value="UN">Unidade</option>
                            <option value="KG">Quilo</option>
                            <option value="L">Litro</option>
                            <option value="HR">Hora</option>
                            <option value="KIT">Kit</option>
                        </datalist>
                        @error('unidade_compra')
                            <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <x-card titulo="Como você consome" icone="fa-ruler-combined"
                    descricao="Quanto rende uma unidade de compra e em que unidade o produto usa esse insumo.">
                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label for="rendimento" class="block text-sm font-medium text-slate-700">
                            Rendimento <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" id="rendimento" name="rendimento" step="0.0001" min="0.0001"
                               x-model.number="rendimento"
                               class="tabular mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                        @error('rendimento')
                            <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="unidade_uso" class="block text-sm font-medium text-slate-700">
                            Unidade de uso <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="unidade_uso" name="unidade_uso" list="unidades-uso"
                               x-model="unidadeUso"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm uppercase shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                        <datalist id="unidades-uso">
                            <option value="M">Metro linear</option>
                            <option value="M2">Metro quadrado</option>
                            <option value="UN">Unidade</option>
                            <option value="KG">Quilo</option>
                            <option value="L">Litro</option>
                            <option value="HR">Hora</option>
                        </datalist>
                        @error('unidade_uso')
                            <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="perda_percentual" class="block text-sm font-medium text-slate-700">
                            Perda <span class="text-rose-500">*</span>
                        </label>
                        <div class="mt-1.5 flex rounded-lg shadow-sm">
                            <input type="number" id="perda_percentual" name="perda_percentual" step="0.01" min="0" max="99.99"
                                   x-model.number="perda"
                                   class="tabular block w-full min-w-0 rounded-l-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                            <span class="inline-flex items-center rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">%</span>
                        </div>
                        @error('perda_percentual')
                            <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <p class="mt-4 text-sm text-slate-500">
                    <i class="fa-solid fa-circle-info text-marca-500"></i>
                    Uma vara de metalon de 6 metros tem rendimento <strong>6</strong> e unidade de uso <strong>M</strong>.
                    Uma chapa de 2 m × 1 m tem rendimento <strong>2</strong> e unidade de uso <strong>M2</strong>.
                    A perda cobre corte, refugo e sobra que não vira produto.
                </p>
            </x-card>

            <x-card titulo="Observações" icone="fa-align-left">
                <textarea name="observacoes" rows="3"
                          placeholder="Especificação técnica, condição comercial, validade do orçamento..."
                          class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">{{ old('observacoes', $insumo->observacoes) }}</textarea>
            </x-card>
        </div>

        <div class="space-y-6">
            {{-- Prévia do custo, atualizada ao vivo --}}
            <div class="overflow-hidden rounded-2xl bg-marca-950 shadow-lg shadow-marca-950/20 xl:sticky xl:top-24">
                <div class="px-6 py-5">
                    <p class="text-sm font-medium text-marca-100/70">Custo por unidade de uso</p>
                    <p class="tabular mt-1 text-3xl font-bold text-white" x-text="moeda(custoFinal)"></p>
                    <p class="mt-1 text-sm text-marca-100/60">por <span x-text="unidadeUso || 'UN'"></span></p>
                </div>
                <div class="border-t border-white/10 px-6 py-4">
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-marca-100/70">Preço ÷ rendimento</dt>
                            <dd class="tabular font-medium text-white" x-text="moeda(custoBase)"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-marca-100/70">Acréscimo por perda</dt>
                            <dd class="tabular font-medium text-amber-300" x-text="moeda(custoFinal - custoBase)"></dd>
                        </div>
                    </dl>
                    <p class="mt-4 rounded-lg bg-white/5 px-3 py-2 font-mono text-[11px] leading-relaxed text-slate-300">
                        (<span x-text="moeda(preco)"></span> ÷ <span x-text="rendimento"></span>)
                        × (1 + <span x-text="perda"></span>%)
                    </p>
                </div>
            </div>

            <x-card titulo="Situação" icone="fa-toggle-on">
                <x-interruptor nome="ativo" rotulo="Insumo ativo" :valor="$insumo->ativo ?? true"
                               ajuda="Insumos inativos não aparecem para montar novas fichas técnicas." />
            </x-card>

            <div class="flex flex-wrap gap-3">
                <x-botao variante="sucesso" icone="fa-floppy-disk" class="flex-1">
                    {{ $insumo->exists ? 'Salvar alterações' : 'Cadastrar insumo' }}
                </x-botao>
                <x-botao href="{{ route('insumos.index') }}" variante="neutro">Cancelar</x-botao>
            </div>
        </div>
    </form>
@endsection
