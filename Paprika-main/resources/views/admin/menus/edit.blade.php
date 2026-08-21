@extends('admin.layouts.app')

@section('title', 'Sửa menu')

@section('content')
    <form method="POST" action="{{ route('admin.menus.update', $menu) }}" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.menus.form')
    </form>
@endsection
