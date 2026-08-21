@extends('admin.layouts.app')

@section('title', 'Thêm khung giờ món')

@section('content')
    <form method="POST" action="{{ route('admin.dish-time-slots.store') }}" class="admin-form">
        @csrf
        @include('admin.dish-time-slots.form', ['slot' => $slot, 'branches' => $branches])
    </form>
@endsection
