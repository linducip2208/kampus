<div class="min-h-screen bg-slate-50 lg:grid lg:grid-cols-[minmax(0,1.05fr)_minmax(420px,.95fr)]">
        <section class="relative hidden overflow-hidden bg-[#0b1220] p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-cyan-400/20 blur-3xl"></div>
            <div class="absolute -bottom-32 -right-12 h-96 w-96 rounded-full bg-blue-600/20 blur-3xl"></div>
            <div class="relative flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-cyan-400 font-bold text-[#0b1220]">C</span>
                <div>
                    <div class="text-lg font-bold">Campus ERP</div>
                    <div class="text-[10px] uppercase tracking-[.24em] text-slate-400">SIAKAD universitas</div>
                </div>
            </div>
            <div class="relative max-w-xl">
                <p class="mb-4 text-sm font-semibold uppercase tracking-[.2em] text-cyan-300">Ruang kendali kampus</p>
                <h1 class="text-5xl font-bold leading-tight">Data akademik yang siap dipertanggungjawabkan.</h1>
                <p class="mt-6 max-w-lg text-lg leading-relaxed text-slate-300">Satukan PMB, mahasiswa, KRS, perkuliahan, nilai, keuangan, dan pelaporan dalam fondasi universitas yang terukur.</p>
                <div class="mt-10 grid max-w-lg grid-cols-3 gap-3">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur"><div class="text-2xl">🎓</div><div class="mt-3 text-sm font-semibold">Akademik</div><div class="mt-1 text-xs text-slate-400">Lifecycle lengkap</div></div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur"><div class="text-2xl">🔐</div><div class="mt-3 text-sm font-semibold">Terukur</div><div class="mt-1 text-xs text-slate-400">Scope & audit</div></div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur"><div class="text-2xl">📊</div><div class="mt-3 text-sm font-semibold">Terintegrasi</div><div class="mt-1 text-xs text-slate-400">Satu sumber data</div></div>
                </div>
            </div>
            <p class="relative text-xs text-slate-500">© {{ date('Y') }} Campus ERP · Platform manajemen universitas</p>
        </section>
        <section class="flex min-h-screen items-center justify-center p-6 sm:p-10 lg:p-16">
            <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-7 shadow-xl shadow-slate-900/5 sm:p-10">
                <div class="mb-8 flex items-center gap-3 lg:hidden"><span class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-400 font-bold text-[#0b1220]">C</span><span class="font-bold text-slate-950">Campus ERP</span></div>
                {{ $this->content }}
                <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
                    <div class="mb-2 font-semibold text-slate-800">🧪 Demo Login</div>
                    <div class="space-y-1 font-mono leading-relaxed">
                        <div><strong>Admin:</strong> admin@kampus.test / password</div>
                        <div><strong>Rektor:</strong> rektor@kampus.test / password</div>
                        <div><strong>Finance:</strong> finance@kampus.test / password</div>
                        <div><strong>BAAK:</strong> baak@kampus.test / password</div>
                        <div><strong>Dosen:</strong> dosen@kampus.test / password</div>
                        <div><strong>Mahasiswa:</strong> mahasiswa@kampus.test / password</div>
                    </div>
                </div>
            </div>
        </section>
</div>
