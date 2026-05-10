@extends('admin.layouts.app')

@section('page-title', 'تعديل تصنيف')
@section('page-subtitle', 'تحديث بيانات التصنيف وترتيبه')

@section('content')
<form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data">
    @method('PUT')
    @include('admin.categories._form')
</form>
@endsection
