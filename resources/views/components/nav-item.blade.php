@props(['href', 'icone', 'ativo' => false])

<a href="{{ $href }}"
   @class([
       'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
       'bg-marca-600 text-white shadow-sm' => $ativo,
       'text-marca-100/70 hover:bg-white/5 hover:text-white' => ! $ativo,
   ])>
    <i class="fa-solid {{ $icone }} w-5 text-center {{ $ativo ? 'text-white' : 'text-marca-100/50 group-hover:text-white' }}"></i>
    <span class="truncate">{{ $slot }}</span>
</a>
