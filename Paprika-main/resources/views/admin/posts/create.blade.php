@extends('admin.layouts.app')

@section('title', 'Thêm bài viết')

@section('content')
    <form method="POST" action="{{ route('admin.posts.store') }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @include('admin.posts.form')
    </form>
@endsection
