@extends('store.layouts.app')

@section('content')
<article class="neo-card p-5 md:p-7 space-y-5">
    <header class="border-b border-slate-200 pb-3">
        <h1 class="text-2xl md:text-3xl font-black">{{ $page->title }}</h1>
        @if($page->excerpt)
            <p class="text-slate-600 mt-2">{{ $page->excerpt }}</p>
        @endif
    </header>

    @if($page->content)
        <div class="prose max-w-none leading-8 whitespace-pre-line">{!! nl2br(e($page->content)) !!}</div>
    @else
        <div class="text-slate-500">لا يوجد محتوى لهذه الصفحة حاليًا.</div>
    @endif
</article>
@endsection
