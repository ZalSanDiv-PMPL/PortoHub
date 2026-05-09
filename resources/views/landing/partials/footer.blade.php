<footer class="border-t border-slate-200 bg-white">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.3fr_0.7fr] lg:px-8">
        <div>
            <div class="flex items-center gap-3">
                <x-application-logo class="h-10 w-10 text-blue-700" />
                <div>
                    <p class="text-lg font-extrabold tracking-tight text-slate-950">PortoHub</p>
                    <p class="text-xs text-slate-500">Documented portfolio validation for RPL students</p>
                </div>
            </div>
            <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600">
                PortoHub memfasilitasi siswa dalam mendokumentasikan karya terbaik mereka secara aman, sekaligus menyediakan verifikasi resmi dari guru untuk menjamin keaslian proyek bagi pihak industri dan recruiter.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-6 text-sm">
            <div>
                <p class="font-semibold text-slate-950">Navigasi</p>
                <div class="mt-3 space-y-2 text-slate-600">
                    <a href="#platform" class="block transition hover:text-blue-700">About</a>
                    <a href="#fitur" class="block transition hover:text-blue-700">Team</a>
                    <a href="#testimoni" class="block transition hover:text-blue-700">Contact</a>
                    <a href="#top" class="block transition hover:text-blue-700">Privacy Policy</a>
                    <a href="#top" class="block transition hover:text-blue-700">Terms of Use</a>
                    <a href="#top" class="block transition hover:text-blue-700">Help</a>
                </div>
            </div>
            <div>
                <p class="font-semibold text-slate-950">Sosial</p>
                <div class="mt-3 space-y-2 text-slate-600">
                    <a href="https://github.com" class="block transition hover:text-blue-700" target="_blank" rel="noreferrer">GitHub</a>
                    <a href="https://instagram.com" class="block transition hover:text-blue-700" target="_blank" rel="noreferrer">Instagram</a>
                    <a href="https://linkedin.com" class="block transition hover:text-blue-700" target="_blank" rel="noreferrer">LinkedIn</a>
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-200 py-6 text-center text-sm text-slate-500">
        © {{ date('Y') }} PortoHub. All rights reserved.
    </div>
</footer>
