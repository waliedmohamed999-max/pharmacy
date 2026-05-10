@extends('admin.layouts.app')

@section('page-title', 'تعديل الصفحة')
@section('page-subtitle', 'تحديث عنوان ومحتوى وإعدادات الصفحة')

@section('content')
<form method="POST" action="{{ route('admin.pages.update', $page) }}">
    @method('PUT')
    @include('admin.pages._form')
</form>
@endsection
