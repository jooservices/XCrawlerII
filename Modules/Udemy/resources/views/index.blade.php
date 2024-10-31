@extends('udemy::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('udemy.name') !!}</p>
@endsection
