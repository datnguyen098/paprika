@extends('admin.layouts.app')

@section('title', 'Sửa danh mục')

@section('content')
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.categories.form')
    </form>
@endsection
