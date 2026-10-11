<section class="mobile-placeholder" aria-labelledby="mobile-placeholder-title">
    <header class="mobile-page-heading">
        <div>
            <p>{{ $eyebrow }}</p>
            <h1 id="mobile-placeholder-title">{{ $title }}</h1>
            <span>{{ $subtitle }}</span>
        </div>
        <span class="mobile-page-heading__icon" aria-hidden="true">
            <i data-lucide="{{ $icon }}"></i>
        </span>
    </header>

    <article class="mobile-card mobile-placeholder__card">
        <span class="mobile-placeholder__icon" aria-hidden="true">
            <i data-lucide="construction"></i>
        </span>
        <div>
            <span class="mobile-placeholder__eyebrow">Pengembangan berikutnya</span>
            <h2>Halaman ini akan dikembangkan</h2>
            <p>Fitur {{ $title }} versi mobile akan tersedia pada tahap pengembangan selanjutnya.</p>
        </div>
    </article>
</section>
