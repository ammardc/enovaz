// Tanpa skrip inline (sesuai Content-Security-Policy). Perilaku dipasang lewat atribut data-*.

// Tombol salin
document.querySelectorAll('[data-copy]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const text = btn.getAttribute('data-copy');
        try {
            await navigator.clipboard.writeText(text);
            const old = btn.textContent;
            btn.textContent = 'Disalin';
            setTimeout(() => (btn.textContent = old), 1500);
        } catch {
            const target = document.getElementById(btn.getAttribute('data-copy-target'));
            if (target) {
                const range = document.createRange();
                range.selectNodeContents(target);
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            }
        }
    });
});

// Stepper jumlah
document.querySelectorAll('[data-qty]').forEach((wrap) => {
    const input = wrap.querySelector('input');
    const max = parseInt(input.max, 10) || 1;
    const price = parseInt(wrap.getAttribute('data-price'), 10) || 0;
    const out = document.getElementById(wrap.getAttribute('data-subtotal'));
    const fmt = (n) => 'Rp' + n.toLocaleString('id-ID');
    const update = () => {
        let v = parseInt(input.value, 10) || 1;
        v = Math.max(1, Math.min(max, v));
        input.value = v;
        if (out) out.textContent = fmt(v * price);
    };
    wrap.querySelectorAll('[data-step]').forEach((b) =>
        b.addEventListener('click', () => {
            input.value = (parseInt(input.value, 10) || 1) + parseInt(b.getAttribute('data-step'), 10);
            update();
        }),
    );
    input.addEventListener('change', update);
});

// Hitung mundur tagihan
document.querySelectorAll('[data-expires]').forEach((el) => {
    const end = new Date(el.getAttribute('data-expires')).getTime();
    const tick = () => {
        const left = end - Date.now();
        if (left <= 0) {
            el.textContent = 'Tagihan kedaluwarsa';
            clearInterval(timer);
            return;
        }
        const m = Math.floor(left / 60000);
        const s = Math.floor((left % 60000) / 1000);
        el.textContent = `Berlaku ${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    };
    const timer = setInterval(tick, 1000);
    tick();
});

// Konfirmasi sebelum tindakan sensitif
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
    });
});

// Mencegah kirim ganda
document.querySelectorAll('form[data-once]').forEach((form) => {
    form.addEventListener('submit', () => {
        form.querySelectorAll('button[type="submit"]').forEach((b) => {
            b.disabled = true;
            if (b.dataset.loading) b.textContent = b.dataset.loading;
        });
    });
});

// Kirim formulir saat pilihan berubah
document.querySelectorAll('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form.submit()));

// Menu ponsel
const menuBtn = document.getElementById('menu-toggle');
const menu = document.getElementById('mobile-menu');
if (menuBtn && menu) menuBtn.addEventListener('click', () => menu.classList.toggle('hidden'));

// Tombol tutup (mis. notifikasi pembelian)
document.querySelectorAll('[data-dismiss]').forEach((btn) => {
    btn.addEventListener('click', () => btn.parentElement.remove());
});

// Pilihan nominal cepat mengisi input (mis. isi saldo)
document.querySelectorAll('[data-fill]').forEach((radio) => {
    radio.addEventListener('change', () => {
        const target = document.getElementById(radio.getAttribute('data-fill'));
        if (target) target.value = radio.value;
    });
});

// Tooltip untuk elemen ber-atribut data-tip (grafik dashboard). Tanpa inline script, aman untuk CSP.
(() => {
    const targets = document.querySelectorAll('[data-tip]');
    if (!targets.length) return;
    const tip = document.createElement('div');
    tip.className = 'tooltip';
    tip.setAttribute('role', 'tooltip');
    tip.hidden = true;
    document.body.appendChild(tip);

    const show = (el) => {
        tip.textContent = el.getAttribute('data-tip');
        tip.hidden = false;
        const r = el.getBoundingClientRect();
        const t = tip.getBoundingClientRect();
        const left = Math.min(Math.max(8, r.left + r.width / 2 - t.width / 2), window.innerWidth - t.width - 8);
        const top = r.top - t.height - 8 < 8 ? r.bottom + 8 : r.top + 18;
        tip.style.left = `${left}px`;
        tip.style.top = `${top}px`;
    };
    const hide = () => { tip.hidden = true; };
    targets.forEach((el) => {
        el.addEventListener('pointerenter', () => show(el));
        el.addEventListener('pointerleave', hide);
        el.addEventListener('focus', () => show(el));
        el.addEventListener('blur', hide);
    });
    window.addEventListener('scroll', hide, { passive: true });
})();

// Kontainer geser (mis. grafik di layar sempit) mulai dari ujung kanan = data terbaru
document.querySelectorAll('[data-scroll-end]').forEach((el) => { el.scrollLeft = el.scrollWidth; });

// CAPTCHA: ganti gambar (sekaligus tantangan baru di server)
document.querySelectorAll('[data-captcha-refresh]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const img = btn.parentElement.querySelector('[data-captcha-img]');
        if (img) img.src = img.src.replace(/\?.*$/, '') + '?r=' + Date.now();
        const input = document.getElementById('captcha');
        if (input) { input.value = ''; input.focus(); }
    });
});
