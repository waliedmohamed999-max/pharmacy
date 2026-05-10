<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;

class PageController extends Controller
{
    private function ensurePagesTable(): ?RedirectResponse
    {
        if (Schema::hasTable('pages')) {
            return null;
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('error', 'جدول الصفحات غير موجود بعد. شغّل أمر migrate أولًا.');
    }

    public function index()
    {
        if ($redirect = $this->ensurePagesTable()) {
            return $redirect;
        }

        $query = Page::query();

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        $pages = $query->orderBy('sort_order')->latest('id')->paginate(15)->withQueryString();

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        if ($redirect = $this->ensurePagesTable()) {
            return $redirect;
        }

        return view('admin.pages.create', [
            'page' => new Page(),
        ]);
    }

    public function store(StorePageRequest $request)
    {
        if ($redirect = $this->ensurePagesTable()) {
            return $redirect;
        }

        Page::create($request->validated());

        return redirect()->route('admin.pages.index')->with('success', 'تم إنشاء الصفحة بنجاح');
    }

    public function edit(Page $page)
    {
        if ($redirect = $this->ensurePagesTable()) {
            return $redirect;
        }

        return view('admin.pages.edit', compact('page'));
    }

    public function update(UpdatePageRequest $request, Page $page)
    {
        if ($redirect = $this->ensurePagesTable()) {
            return $redirect;
        }

        $page->update($request->validated());

        return redirect()->route('admin.pages.index')->with('success', 'تم تعديل الصفحة بنجاح');
    }

    public function destroy(Page $page)
    {
        if ($redirect = $this->ensurePagesTable()) {
            return $redirect;
        }

        $page->delete();

        return back()->with('success', 'تم حذف الصفحة');
    }
}
