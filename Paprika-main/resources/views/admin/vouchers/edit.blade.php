@extends('admin.layouts.app')

@section('title', 'Sửa voucher')

@section('content')
    <form method="POST" action="{{ route('admin.vouchers.update', $voucher) }}" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.vouchers.form')
    </form>
@endsection
