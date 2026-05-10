<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Http\Requests\Admin\UpdateBannerRequest;
use App\Models\Banner;
use App\Models\Category;
use App\Models\StoreSetting;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort_order')->paginate(15);
        $bannerAutoplay = StoreSetting::getBool('home_banner_autoplay', true);

        return view('admin.banners.index', compact('banners', 'bannerAutoplay'));
    }

    public function create()
    {
        return view('admin.banners.create', [
            'banner' => new Banner(),
            'products' => Product::where('is_active', true)->latest()->take(200)->get(),
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreBannerRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('banners', 'public');
            $data['image_path'] = $data['image'];
        } else {
            $data['image'] = 'images/placeholder.png';
            $data['image_path'] = 'images/placeholder.png';
        }

        Banner::create($data);

        return redirect()->route('admin.banners.index')->with('success', 'تم إنشاء البنر بنجاح');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', [
            'banner' => $banner,
            'products' => Product::where('is_active', true)->latest()->take(200)->get(),
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdateBannerRequest $request, Banner $banner)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($banner->image && !str_starts_with($banner->image, 'images/') && Storage::disk('public')->exists($banner->image)) {
                Storage::disk('public')->delete($banner->image);
            }

            $data['image'] = $request->file('image')->store('banners', 'public');
            $data['image_path'] = $data['image'];
        }

        $banner->update($data);

        return redirect()->route('admin.banners.index')->with('success', 'تم تعديل البنر بنجاح');
    }

    public function destroy(Banner $banner)
    {
        if ($banner->image && !str_starts_with($banner->image, 'images/') && Storage::disk('public')->exists($banner->image)) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        return back()->with('success', 'تم حذف البنر');
    }

    public function updateSliderAutoplay(Request $request)
    {
        StoreSetting::setValue('home_banner_autoplay', $request->boolean('home_banner_autoplay') ? '1' : '0');

        return back()->with('success', 'تم تحديث إعداد التشغيل التلقائي للبنرات');
    }
}
