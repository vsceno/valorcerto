@props(['nome' => 'ativo', 'rotulo' => 'Ativo', 'ajuda' => null, 'valor' => true])

<label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-slate-50/60 p-4">
    <input type="hidden" name="{{ $nome }}" value="0">
    <input type="checkbox" name="{{ $nome }}" value="1"
           @checked((bool) old($nome, $valor))
           class="mt-0.5 h-4 w-4 rounded border-slate-300 text-marca-600 focus:ring-marca-500">
    <span class="text-sm">
        <span class="font-medium text-slate-700">{{ $rotulo }}</span>
        @if ($ajuda)
            <span class="mt-0.5 block text-slate-500">{{ $ajuda }}</span>
        @endif
    </span>
</label>
