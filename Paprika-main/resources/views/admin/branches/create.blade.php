@extends('admin.layouts.app')

@section('title', 'Thêm cơ sở')

@section('content')
    <form method="POST" action="{{ route('admin.branches.store') }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @include('admin.branches.form')
    </form>
@endsection
