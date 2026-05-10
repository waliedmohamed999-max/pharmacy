@extends('admin.layouts.app')

@section('page-title', 'إضافة تصنيف')
@section('page-subtitle', 'أنشئ تصنيفًا جديدًا مع صورة دائرية للواجهة')

@section('content')
<form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
    @include('admin.categories._form')
</form>
@endsection
