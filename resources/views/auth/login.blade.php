<x-guest-layout>
    <section class="relative min-h-screen bg-[linear-gradient(135deg,#f8fbfa_0%,#eef7f3_46%,#dcece7_100%)]">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-l from-emerald-700 via-teal-500 to-amber-300"></div>

        <div class="mx-auto grid min-h-screen w-full max-w-7xl items-center gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[1.05fr_.95fr] lg:px-8">
            <div class="order-2 hidden lg:block">
                <div class="max-w-xl">
                    <a href="{{ route('store.home') }}" class="inline-flex items-center gap-3 rounded-2xl bg-white/80 px-3 py-2 shadow-sm ring-1 ring-emerald-100">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-700 text-white">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path d="m10.5 20.5 10-10a4.95 4.95 0 0 0-7-7l-10 10a4.95 4.95 0 0 0 7 7Z"/>
                                <path d="m8.5 8.5 7 7"/>
                            </svg>
                        </span>
                        <span>
                            <span class="block text-base font-black text-slate-950">صيدلية د. محمد رمضان</span>
                            <span class="block text-xs font-bold text-emerald-700">لوحة إدارة الصيدلية</span>
                        </span>
                    </a>

                    <div class="mt-10">
                        <p class="text-sm font-black uppercase tracking-wide text-emerald-700">دخول آمن للطاقم</p>
                        <h1 class="mt-3 max-w-2xl text-4xl font-black leading-tight text-slate-950 xl:text-5xl">
                            إدارة الطلبات والمخزون والحسابات من مكان واحد.
                        </h1>
                        <p class="mt-5 max-w-lg text-base font-semibold leading-8 text-slate-600">
                            واجهة دخول مخصصة لإدارة الصيدلية، مصممة للوصول السريع إلى لوحة التحكم مع حماية الحسابات وتجربة واضحة على كل الشاشات.
                        </p>
                    </div>

                    <div class="mt-8 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/80 bg-white/75 p-4 shadow-sm">
                            <div class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>
                                    <path d="m9 12 2 2 4-5"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-sm font-black text-slate-900">صلاحيات آمنة</div>
                            <div class="mt-1 text-xs font-semibold leading-5 text-slate-500">حسابات منفصلة للمدير والموظفين.</div>
                        </div>

                        <div class="rounded-2xl border border-white/80 bg-white/75 p-4 shadow-sm">
                            <div class="grid h-10 w-10 place-items-center rounded-xl bg-sky-50 text-sky-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                                    <path d="m3.3 7 8.7 5 8.7-5"/>
                                    <path d="M12 22V12"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-sm font-black text-slate-900">مخزون وطلبات</div>
                            <div class="mt-1 text-xs font-semibold leading-5 text-slate-500">متابعة المنتجات والبيع بسرعة.</div>
                        </div>

                        <div class="rounded-2xl border border-white/80 bg-white/75 p-4 shadow-sm">
                            <div class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <path d="M3 3v18h18"/>
                                    <path d="m7 15 4-4 3 3 5-7"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-sm font-black text-slate-900">تقارير فورية</div>
                            <div class="mt-1 text-xs font-semibold leading-5 text-slate-500">مبيعات وحسابات في لوحة واحدة.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="order-1 flex justify-center lg:justify-end">
                <div class="w-full max-w-md">
                    <div class="mb-5 flex items-center justify-center lg:hidden">
                        <a href="{{ route('store.home') }}" class="inline-flex items-center gap-3">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-700 text-white shadow-lg shadow-emerald-900/15">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <path d="m10.5 20.5 10-10a4.95 4.95 0 0 0-7-7l-10 10a4.95 4.95 0 0 0 7 7Z"/>
                                    <path d="m8.5 8.5 7 7"/>
                                </svg>
                            </span>
                            <span class="text-lg font-black">صيدلية د. محمد رمضان</span>
                        </a>
                    </div>

                    <div class="overflow-hidden rounded-[1.7rem] border border-white/80 bg-white shadow-2xl shadow-slate-900/10">
                        <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-5">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h2 class="text-2xl font-black text-slate-950">تسجيل الدخول</h2>
                                    <p class="mt-1 text-sm font-semibold text-slate-500">ادخل بيانات حساب الإدارة للمتابعة.</p>
                                </div>
                                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-700 text-white">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <rect x="3" y="11" width="18" height="10" rx="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('login') }}" class="space-y-5 p-6">
                            @csrf

                            <x-auth-session-status class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700" :status="session('status')" />

                            <div>
                                <label for="email" class="mb-2 block text-sm font-black text-slate-700">البريد الإلكتروني</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                                            <path d="m3 7 9 6 9-6"/>
                                        </svg>
                                    </span>
                                    <input
                                        id="email"
                                        class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-4 pr-12 text-right text-sm font-bold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-100"
                                        type="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        required
                                        autofocus
                                        autocomplete="username"
                                        placeholder="admin@drpharmacy.test"
                                    >
                                </div>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div>
                                <label for="password" class="mb-2 block text-sm font-black text-slate-700">كلمة المرور</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                            <path d="M21 2 11 12"/>
                                            <path d="M15.5 6.5 17 8"/>
                                            <path d="M12 15a6 6 0 1 1-3-3"/>
                                            <path d="M7 17h.01"/>
                                        </svg>
                                    </span>
                                    <input
                                        id="password"
                                        class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-4 pr-12 text-right text-sm font-bold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-100"
                                        type="password"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        placeholder="ادخل كلمة المرور"
                                    >
                                </div>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <label for="remember_me" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600">
                                    <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-emerald-700 shadow-sm focus:ring-emerald-500" name="remember">
                                    <span>تذكرني</span>
                                </label>

                                @if (Route::has('password.request'))
                                    <a class="text-sm font-bold text-emerald-700 transition hover:text-emerald-900" href="{{ route('password.request') }}">
                                        نسيت كلمة المرور؟
                                    </a>
                                @endif
                            </div>

                            <button type="submit" class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                                <span>تسجيل الدخول</span>
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                    <path d="m10 17 5-5-5-5"/>
                                    <path d="M15 12H3"/>
                                </svg>
                            </button>
                        </form>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-xs font-bold text-slate-500">
                        <span class="rounded-full bg-white/75 px-3 py-1.5 ring-1 ring-slate-200">اتصال محلي آمن</span>
                        <span class="rounded-full bg-white/75 px-3 py-1.5 ring-1 ring-slate-200">صلاحيات الإدارة</span>
                        <a href="{{ route('store.home') }}" class="rounded-full bg-white/75 px-3 py-1.5 text-emerald-700 ring-1 ring-emerald-100">العودة للمتجر</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-guest-layout>
