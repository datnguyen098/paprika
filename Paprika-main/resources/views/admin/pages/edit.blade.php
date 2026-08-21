@extends('admin.layouts.app')

@section('title', 'Sửa trang tĩnh')

@section('content')
    <form method="POST" action="{{ route('admin.pages.update', $page) }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.pages.form')
    </form>
@endsection
