document.addEventListener('DOMContentLoaded', () => {
    // 1. Image Skeleton Shimmer Removal
    document.querySelectorAll('.img-shimmer-wrap img').forEach((img) => {
        const wrap = img.closest('.img-shimmer-wrap');
        const markLoaded = () => {
            if (!wrap) return;
            wrap.classList.add('is-loaded');
            wrap.classList.remove('skeleton-shimmer');
        };
        if (img.complete) {
            markLoaded();
        } else {
            img.addEventListener('load', markLoaded);
            img.addEventListener('error', markLoaded);
        }
    });

    // 2. Catalog Search & Filter Skeleton Placeholder
    const searchForm = document.getElementById('form-pencarian-barang');
    const gridBarang = document.getElementById('grid-daftar-barang');
    if (searchForm && gridBarang) {
        searchForm.addEventListener('submit', () => {
            const skeletonCard = `
                <div class="catalog-skeleton-card" aria-hidden="true">
                    <span class="skeleton-shimmer catalog-skeleton-media"></span>
                    <span class="skeleton-shimmer catalog-skeleton-line short"></span>
                    <span class="skeleton-shimmer catalog-skeleton-line long"></span>
                    <span class="skeleton-shimmer catalog-skeleton-line medium"></span>
                    <span class="skeleton-shimmer catalog-skeleton-line short"></span>
                </div>`;
            gridBarang.setAttribute('aria-busy', 'true');
            gridBarang.innerHTML = `${'<p class="visually-hidden" role="status">Memuat daftar barang...</p>'}${skeletonCard.repeat(6)}`;
        });
    }

    // 3. Submit Button Loading State
    document.querySelectorAll('form:not([data-no-loading])').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) return;
            const submitter = e.submitter;
            const btn = submitter && submitter.matches('button[type="submit"], input[type="submit"]')
                ? submitter
                : form.querySelector('button[type="submit"], input[type="submit"]');
            if (!btn || btn.disabled) return;

            const label = btn.getAttribute('data-loading-text') || 'Memproses...';
            form.setAttribute('aria-busy', 'true');
            btn.classList.add('is-loading');
            btn.setAttribute('aria-busy', 'true');
            btn.dataset.loadingOriginal = btn instanceof HTMLInputElement ? btn.value : btn.innerHTML;

            setTimeout(() => {
                if (!btn.isConnected) return;
                btn.disabled = true;
                const loadingMarkup = `<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>${label}`;
                if (btn instanceof HTMLInputElement) {
                    btn.value = label;
                } else {
                    btn.innerHTML = loadingMarkup;
                }
            }, 0);
        });
    });

    // 4. Toggle Password Visibility
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-toggle-password');
            const input = document.getElementById(targetId);
            const icon = btn.querySelector('i');
            if (!input) return;
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            if (icon) {
                icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
        });
    });

    // 5. Dynamic Label Form Laporan (Hilang vs Temuan)
    const jenisRadios = document.querySelectorAll('input[name="jenis_laporan"]');
    const labelLokasi = document.getElementById('label-lokasi');
    const labelTanggal = document.getElementById('label-tanggal');
    const hintDeskripsi = document.getElementById('hint-deskripsi');

    const updateFormLabels = (jenis) => {
        if (!labelLokasi || !labelTanggal || !hintDeskripsi) return;
        if (jenis === 'temuan') {
            labelLokasi.innerHTML = 'Lokasi Barang Ditemukan <span class="text-danger">*</span>';
            labelTanggal.innerHTML = 'Tanggal Ditemukan <span class="text-danger">*</span>';
            hintDeskripsi.textContent = 'Jelaskan ciri khusus barang dan sebutkan di mana barang disimpan / dapat diambil saat ini.';
        } else {
            labelLokasi.innerHTML = 'Lokasi Terakhir Terlihat <span class="text-danger">*</span>';
            labelTanggal.innerHTML = 'Tanggal Hilang <span class="text-danger">*</span>';
            hintDeskripsi.textContent = 'Jelaskan ciri khusus, warna, merek, atau isi barang agar mudah dikenali pemiliknya.';
        }
    };

    jenisRadios.forEach((radio) => {
        if (radio.checked) updateFormLabels(radio.value);
        radio.addEventListener('change', (e) => updateFormLabels(e.target.value));
    });

    // 6. Photo Upload Validation & Live Preview (JPG/PNG, Max 2 MB)
    const inputFoto = document.getElementById('input-foto');
    const previewWrap = document.getElementById('preview-foto-wrap');
    const previewImg = document.getElementById('preview-foto-img');
    const fotoError = document.getElementById('foto-error-msg');

    if (inputFoto && previewWrap && previewImg) {
        inputFoto.addEventListener('change', () => {
            if (fotoError) fotoError.classList.add('d-none');
            const file = inputFoto.files[0];
            if (!file) {
                previewWrap.classList.add('d-none');
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/png'];
            const allowedExts = ['jpg', 'jpeg', 'png'];
            const ext = file.name.split('.').pop().toLowerCase();
            const maxSize = 2 * 1024 * 1024; // 2 MB

            // Izinkan jika MIME type cocok ATAU ekstensi file valid (mengatasi Windows MIME kosong)
            const isValidFormat = allowedTypes.includes(file.type) || allowedExts.includes(ext);

            if (!isValidFormat) {
                inputFoto.value = '';
                previewWrap.classList.add('d-none');
                if (fotoError) {
                    fotoError.textContent = 'Format foto harus berupa JPG, JPEG, atau PNG.';
                    fotoError.classList.remove('d-none');
                }
                return;
            }

            if (file.size > maxSize) {
                inputFoto.value = '';
                previewWrap.classList.add('d-none');
                if (fotoError) {
                    fotoError.textContent = 'Ukuran foto maksimal 2 MB.';
                    fotoError.classList.remove('d-none');
                }
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                previewWrap.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });
    }

    // 7. Copy Contact Helper (Detail Barang)
    document.querySelectorAll('[data-copy-text]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const text = btn.getAttribute('data-copy-text');
            if (!text) return;
            try {
                await navigator.clipboard.writeText(text);
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check2"></i> Nomor Tersalin!';
                btn.classList.remove('btn-rf-primary');
                btn.classList.add('btn-success');
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-rf-primary');
                }, 2000);
            } catch (_) {
                // Fallback copy
                const tempInput = document.createElement('input');
                tempInput.value = text;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                btn.innerHTML = '<i class="bi bi-check2"></i> Tersalin!';
            }
        });
    });

    // 8. Action Confirmation
    document.querySelectorAll('[data-confirm]').forEach((el) => {
        el.addEventListener('click', (e) => {
            const msg = el.getAttribute('data-confirm');
            if (msg && !window.confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // 9. Page Motion and Navigation
    const processSection = document.querySelector('.process-section');
    const landingHero = document.querySelector('.landing-hero');
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const revealEls = document.querySelectorAll('.landing-reveal, .process-reveal, .catalog-reveal');

    const pageTransitionOverlay = document.querySelector('.page-transition-overlay');
    const pageTransitionStatus = document.querySelector('[data-page-transition-status]');
    let isPageTransitioning = false;

    if (document.querySelector('body > main') && !prefersReducedMotion) {
        document.documentElement.classList.add('page-transition-in');
        window.setTimeout(() => {
            document.documentElement.classList.remove('page-transition-in');
        }, 520);
    }

    window.addEventListener('pageshow', () => {
        document.documentElement.classList.remove('page-transition-out');
        isPageTransitioning = false;
        if (pageTransitionStatus) pageTransitionStatus.textContent = '';
        document.querySelectorAll('form[aria-busy="true"]').forEach((form) => {
            form.removeAttribute('aria-busy');
            form.querySelectorAll('.is-loading').forEach((btn) => {
                if (btn instanceof HTMLInputElement) {
                    btn.value = btn.dataset.loadingOriginal || btn.value;
                } else {
                    btn.innerHTML = btn.dataset.loadingOriginal || btn.innerHTML;
                }
                btn.disabled = false;
                btn.classList.remove('is-loading');
                btn.removeAttribute('aria-busy');
                delete btn.dataset.loadingOriginal;
            });
        });
    });

    if (pageTransitionOverlay) {
        document.addEventListener('click', (event) => {
            const link = event.target instanceof Element
                ? event.target.closest('a[href]')
                : null;

            if (
                !link
                || event.defaultPrevented
                || isPageTransitioning
                || event.button !== 0
                || event.metaKey
                || event.ctrlKey
                || event.shiftKey
                || event.altKey
                || (link.target && link.target !== '_self')
                || link.hasAttribute('download')
                || link.hasAttribute('data-no-page-transition')
                || link.getAttribute('href').trim().startsWith('#')
            ) {
                return;
            }

            const destination = new URL(link.href, window.location.href);
            const isHashNavigation = destination.pathname === window.location.pathname
                && destination.search === window.location.search
                && destination.hash;
            if (
                destination.origin !== window.location.origin
                || destination.href === window.location.href
                || isHashNavigation
            ) {
                return;
            }

            event.preventDefault();
            isPageTransitioning = true;
            document.documentElement.classList.remove('page-transition-in');
            if (pageTransitionStatus) pageTransitionStatus.textContent = 'Memuat halaman';
            document.documentElement.classList.add('page-transition-out');
            window.setTimeout(() => {
                window.location.assign(destination.href);
            }, prefersReducedMotion ? 0 : 220);
        });
    }

    if (revealEls.length > 0) {
        if (!prefersReducedMotion) {
            document.documentElement.classList.add('js-motion-ready');
        }

        if (prefersReducedMotion || !('IntersectionObserver' in window)) {
            revealEls.forEach((el) => el.classList.add('is-visible'));
        } else {
            const revealObserver = new IntersectionObserver(
                (entries, observer) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                },
                { threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
            );
            revealEls.forEach((el) => revealObserver.observe(el));
        }
    }

    if (processSection && !prefersReducedMotion) {
        const parallaxEls = processSection.querySelectorAll('[data-parallax-speed]');
        if (parallaxEls.length > 0) {
            let rafId = null;

            const updateParallax = () => {
                rafId = null;
                const rect = processSection.getBoundingClientRect();
                const viewportCenter = window.innerHeight * 0.5;
                if (rect.bottom < -120 || rect.top > window.innerHeight + 120) {
                    return;
                }
                const sectionCenter = rect.top + rect.height * 0.5;
                const delta = viewportCenter - sectionCenter;

                parallaxEls.forEach((el) => {
                    const speed = parseFloat(el.getAttribute('data-parallax-speed') || '0');
                    const rawOffset = delta * speed;
                    const clamped = Math.max(-18, Math.min(18, rawOffset));
                    el.style.setProperty('--parallax-y', `${clamped.toFixed(2)}px`);
                });
            };

            const onScrollOrResize = () => {
                if (rafId === null) {
                    rafId = window.requestAnimationFrame(updateParallax);
                }
            };

            updateParallax();
            window.addEventListener('scroll', onScrollOrResize, { passive: true });
            window.addEventListener('resize', onScrollOrResize, { passive: true });
        }
    }

    if (landingHero && !prefersReducedMotion && !window.matchMedia('(pointer: coarse)').matches) {
        let heroRafId = null;

        const updateHeroParallax = () => {
            heroRafId = null;
            const heroTop = landingHero.getBoundingClientRect().top;
            const offset = Math.max(-12, Math.min(12, heroTop * -0.025));
            landingHero.style.setProperty('--hero-parallax-y', `${offset.toFixed(2)}px`);
        };

        const scheduleHeroParallax = () => {
            if (heroRafId === null) {
                heroRafId = window.requestAnimationFrame(updateHeroParallax);
            }
        };

        updateHeroParallax();
        window.addEventListener('scroll', scheduleHeroParallax, { passive: true });
        window.addEventListener('resize', scheduleHeroParallax, { passive: true });
    }
});
