@props(['banners', 'autoplay' => true])

@php $sliderId = 'hero-slider-' . uniqid(); @endphp

@if($banners->count())
<section class="neo-card p-3 md:p-4">
    <div id="{{ $sliderId }}" class="relative overflow-hidden rounded-2xl neo-inset select-none" dir="ltr">
        <div data-track class="flex transition-transform duration-500 ease-out">
            @foreach($banners as $banner)
                <article class="w-full shrink-0 relative h-[210px] md:h-[340px]">
                    <img src="{{ $banner->resolved_image }}" alt="{{ $banner->title }}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-l from-black/45 to-transparent"></div>
                    <div class="absolute inset-y-0 right-0 p-4 md:p-8 flex flex-col justify-center max-w-[85%] md:max-w-[55%] text-white" dir="rtl">
                        <h2 class="text-xl md:text-4xl font-extrabold leading-tight">{{ $banner->title }}</h2>
                        @if($banner->subtitle)
                            <p class="mt-2 text-sm md:text-lg text-white/95">{{ $banner->subtitle }}</p>
                        @endif
                        <div class="mt-4">
                            <a href="{{ $banner->resolved_url }}" class="neo-btn inline-block bg-white text-slate-800">تسوق الآن</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if($banners->count() > 1)
            <button type="button" data-prev class="absolute top-1/2 -translate-y-1/2 right-2 md:right-4 w-10 h-10 rounded-full neo-card text-lg font-bold" aria-label="السابق">&#8249;</button>
            <button type="button" data-next class="absolute top-1/2 -translate-y-1/2 left-2 md:left-4 w-10 h-10 rounded-full neo-card text-lg font-bold" aria-label="التالي">&#8250;</button>

            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2">
                @foreach($banners as $index => $banner)
                    <button type="button" data-dot="{{ $index }}" class="w-2.5 h-2.5 rounded-full bg-white/70 border border-white/80" aria-label="البنر {{ $index + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </div>
</section>

@if($banners->count() > 1)
<script>
(() => {
    const root = document.getElementById(@json($sliderId));
    if (!root) return;

    const autoplayEnabled = @json((bool) $autoplay);
    const track = root.querySelector('[data-track]');
    const slides = Array.from(track.children);
    const prevBtn = root.querySelector('[data-prev]');
    const nextBtn = root.querySelector('[data-next]');
    const dots = Array.from(root.querySelectorAll('[data-dot]'));
    let index = 0;
    let timer = null;
    let startX = 0;
    let deltaX = 0;
    const threshold = 45;

    const render = () => {
        track.style.transform = `translateX(-${index * 100}%)`;
        dots.forEach((dot, i) => {
            dot.classList.toggle('bg-slate-700', i === index);
            dot.classList.toggle('w-6', i === index);
            dot.classList.toggle('bg-white/70', i !== index);
        });
    };

    const goTo = (i) => {
        index = (i + slides.length) % slides.length;
        render();
    };

    const next = () => goTo(index + 1);
    const prev = () => goTo(index - 1);

    const stopAuto = () => timer && clearInterval(timer);
    const startAuto = () => {
        stopAuto();
        timer = setInterval(next, 4500);
    };

    nextBtn?.addEventListener('click', () => { next(); if (autoplayEnabled) startAuto(); });
    prevBtn?.addEventListener('click', () => { prev(); if (autoplayEnabled) startAuto(); });
    dots.forEach((dot, i) => dot.addEventListener('click', () => { goTo(i); if (autoplayEnabled) startAuto(); }));

    root.addEventListener('mouseenter', stopAuto);
    root.addEventListener('mouseleave', () => { if (autoplayEnabled) startAuto(); });

    root.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; deltaX = 0; }, { passive: true });
    root.addEventListener('touchmove', (e) => { deltaX = e.touches[0].clientX - startX; }, { passive: true });
    root.addEventListener('touchend', () => {
        if (Math.abs(deltaX) >= threshold) {
            if (deltaX < 0) next(); else prev();
            if (autoplayEnabled) startAuto();
        }
    });

    render();
    if (autoplayEnabled) startAuto();
})();
</script>
@endif
@endif
