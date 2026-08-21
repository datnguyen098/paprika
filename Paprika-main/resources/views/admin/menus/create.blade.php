@extends('admin.layouts.app')

@section('title', 'Thêm menu')

@section('content')
    <form method="POST" action="{{ route('admin.menus.store') }}" class="admin-form-card">
        @csrf
        @include('admin.menus.form')
    </form>
@endsection
