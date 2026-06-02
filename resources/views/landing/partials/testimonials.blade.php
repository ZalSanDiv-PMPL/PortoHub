<section id="testimoni" class="py-24 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div x-data="testimonialComponent()" class="bg-[#0A2B7A] rounded-3xl p-12 lg:p-20 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center min-h-[600px]">
            
            <!-- Left Side -->
            <div class="text-white space-y-12">
                <h2 class="text-4xl font-bold tracking-tight sm:text-5xl">
                    Apa Kata Mereka Tentang Kami
                </h2>
                
                <div class="space-y-4">
                    <template x-for="t in testimonials" :key="t.id">
                        <div 
                            @mouseover="activeId = t.id" 
                            @click="activeId = t.id"
                            :class="activeId === t.id ? 'bg-white/10 ring-1 ring-white/20 shadow-lg' : 'hover:bg-white/5 opacity-60 hover:opacity-100'"
                            class="flex items-center gap-4 p-4 rounded-xl cursor-pointer transition-all duration-300"
                        >
                            <img :src="t.avatar" :alt="t.name" class="h-14 w-14 rounded-full border-2 border-white/20">
                            <div>
                                <p class="text-lg text-slate-200 font-medium" x-text="t.short_quote"></p>
                                <p class="text-sm text-blue-300 mt-1 font-semibold">
                                    <span x-text="t.name"></span> 
                                    <span class="text-blue-400 font-normal opacity-70 ml-1" x-text="'- ' + t.role"></span>
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Right Side (Main Testimonial Card) -->
            <div class="relative w-full h-full min-h-[350px]">
                <template x-for="t in testimonials" :key="'card-'+t.id">
                    <div x-show="activeId === t.id" 
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 translate-y-4"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="bg-[#153B94] rounded-2xl p-8 lg:p-10 shadow-2xl border border-white/10 absolute inset-0 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-4 mb-8">
                                <img :src="t.avatar" :alt="t.name" class="h-16 w-16 rounded-full border-2 border-white/30">
                                <div>
                                    <h4 class="text-xl font-bold text-white" x-text="t.name"></h4>
                                    <div class="inline-block mt-1 px-3 py-1 rounded-full border border-blue-400 bg-blue-900/50 text-xs font-semibold text-blue-200">
                                        <span x-text="t.role"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="text-lg leading-relaxed text-blue-50 mb-10 italic">
                                "<span x-text="t.full_quote"></span>"
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-4 border-t border-white/20 pt-6 mt-auto">
                            <template x-for="(stat, index) in t.stats" :key="index">
                                <div>
                                    <p class="text-3xl font-extrabold text-white" x-text="stat.value"></p>
                                    <p class="text-[10px] font-bold tracking-wider text-blue-300 uppercase mt-1" x-text="stat.label"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </div>
</section>

<script>
function testimonialComponent() {
    return {
        activeId: 1,
        testimonials: [
            {
                id: 1,
                name: 'Pak Dian Purwanto',
                role: 'Guru RPL',
                short_quote: 'Review kode jadi jauh lebih rapi.',
                full_quote: 'PortoHub mengubah cara kami menjembatani kemampuan di kelas dengan ekspektasi industri. Siswa kami kini lulus dengan portofolio yang bukan sekadar deretan tautan, melainkan rekam jejak keahlian yang terverifikasi dan profesional.',
                stats: [
                    { value: '500+', label: 'Proyek Tervalidasi' },
                    { value: '1500+', label: 'Siswa Aktif' },
                    { value: '98%', label: 'Tingkat Kelulusan' }
                ],
                avatar: 'https://ui-avatars.com/api/?name=Pak+Budi&background=0D8ABC&color=fff'
            },
            {
                id: 2,
                name: 'Rizal Naufal Arviansyah',
                role: 'Siswa RPL',
                short_quote: 'Sangat membantu lamaran magang!',
                full_quote: 'Dulu bingung mau kumpulin link proyek ke mana, sekarang tinggal masukin ke PortoHub, hubungkan ke GitHub, dan tunggu divalidasi guru. Portofolio saya terlihat jauh lebih profesional di mata industri.',
                stats: [
                    { value: '3+', label: 'Proyek Portfolio' },
                    { value: '100%', label: 'Integrasi GitHub' },
                    { value: '5+', label: 'Feedback Diterima' }
                ],
                avatar: 'https://ui-avatars.com/api/?name=Wafi&background=0ea5e9&color=fff'
            },
            {
                id: 3,
                name: 'Pak Hendra',
                role: 'Tech Recruiter',
                short_quote: 'Sistem validasinya terpercaya.',
                full_quote: 'Saat merekrut talenta muda, kepastian bahwa kode yang mereka buat adalah murni karya sendiri sangatlah penting. Validasi guru dari PortoHub memberi kami rasa aman dan mempercepat proses screening.',
                stats: [
                    { value: '10+', label: 'Sekolah Mitra' },
                    { value: '2x', label: 'Lebih Cepat' },
                    { value: 'Top', label: 'Kualitas Talent' }
                ],
                avatar: 'https://ui-avatars.com/api/?name=Pak+Hendra&background=10b981&color=fff'
            }
        ]
    }
}
</script>
