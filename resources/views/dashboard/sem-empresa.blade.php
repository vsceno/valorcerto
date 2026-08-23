@extends('layouts.app')

@section('titulo', 'Bem-vindo ao ValorCerto')

@section('conteudo')
    <x-vazio icone="fa-building" titulo="Cadastre sua empresa para começar"
             descricao="O regime tributário e o volume projetado definem como os custos fixos são rateados e quais tributos entram no preço.">
        <x-botao href="{{ route('empresa.edit') }}" icone="fa-gear">Configurar empresa</x-botao>
    </x-vazio>
@endsection
