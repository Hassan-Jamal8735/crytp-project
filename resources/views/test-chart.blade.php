@extends('layouts.user_type.auth')

@section('content')
   @livewire(\App\Http\Livewire\PriceChart::class)
@endsection