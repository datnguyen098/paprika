@extends('admin.layouts.app')

@section('title', 'Thêm danh mục')

@section('content')
    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @include('admin.categories.form')
    </form>
@endsection
