<x-layouts.public>
    @php
        $projects = $projects ?? collect();
        $featuredProject = $featuredProject ?? null;
    @endphp

    @include('landing.partials.hero')
    <livewire:public.portfolio-gallery :limit="6" :isLandingPage="true" />
    @include('landing.partials.testimonials')
</x-layouts.public>
