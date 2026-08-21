@extends('admin.layouts.app')

@section('title', 'Sửa bài viết')

@section('content')
    <form method="POST" action="{{ route('admin.posts.update', $post) }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.posts.form')
    </form>
@endsection
