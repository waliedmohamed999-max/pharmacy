@extends('admin.layouts.app')

@section('page-title', 'تعديل منتج')
@section('page-subtitle', 'تحديث المنتج وإدارة صوره')

@section('content')
<form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
    @method('PUT')
    @include('admin.products._form')
</form>
@endsection
