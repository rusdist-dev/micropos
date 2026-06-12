import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

// Chart.js tersedia global agar bisa diinisialisasi di dalam komponen Alpine.
window.Chart = Chart;

// Konfigurasi toko diinjeksikan server-side via partials/theme.blade.php
// (window.posSettings). Sediakan fallback agar aman bila belum tersedia.
window.posSettings = window.posSettings || {};

// Hex (#rrggbb / #rgb) -> {r,g,b}. Fallback ke teal-500 bila tidak valid.
window.hexToRgb = (hex) => {
    let h = String(hex || '').trim().replace('#', '');
    if (h.length === 3) h = h.split('').map((c) => c + c).join('');
    if (! /^[0-9a-fA-F]{6}$/.test(h)) h = '14b8a6';
    return { r: parseInt(h.slice(0, 2), 16), g: parseInt(h.slice(2, 4), 16), b: parseInt(h.slice(4, 6), 16) };
};

// Warna palet untuk chart. Primary mengikuti tema yang dikonfigurasi.
const primaryHex = window.posSettings.primaryColor || '#14b8a6';
const primaryRgb = window.hexToRgb(primaryHex);
window.posColors = {
    primary: primaryHex,
    primaryLight: '#99f6e4',
    warning: '#f59e0b',
    danger: '#ef4444',
    gray: '#9ca3af',
    // Channel "r, g, b" + helper rgba() untuk gradient/fill mengikuti warna konfigurasi.
    primaryChannels: `${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}`,
    primaryRgba: (alpha) => `rgba(${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}, ${alpha})`,
};

// Format Rupiah ringkas, dipakai lintas komponen Alpine.
window.rupiah = (v) => 'Rp ' + Number(v || 0).toLocaleString('id-ID');

/**
 * Pembungkus tipis di atas fetch() native (BUKAN Axios) untuk seluruh komunikasi
 * dengan API: menyisipkan header JSON + CSRF, mem-parse respons, dan melempar
 * Error berisi { status, errors } saat respons gagal.
 */
window.api = {
    csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    },
    async request(method, url, body = undefined) {
        const headers = { Accept: 'application/json' };
        const opts = { method, headers };

        if (body instanceof FormData) {
            opts.body = body; // biarkan browser set multipart boundary
        } else if (body !== undefined && body !== null) {
            headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        if (method !== 'GET' && method !== 'HEAD') {
            headers['X-CSRF-TOKEN'] = this.csrf();
        }

        const res = await fetch(url, opts);
        let data = null;
        try {
            data = await res.json();
        } catch (e) {
            // respons tanpa body JSON
        }

        if (!res.ok) {
            const err = new Error((data && data.message) || 'Terjadi kesalahan pada server');
            err.status = res.status;
            err.errors = (data && data.errors) || {};
            throw err;
        }
        return data;
    },
    get(url) {
        return this.request('GET', url);
    },
    post(url, body) {
        return this.request('POST', url, body);
    },
    put(url, body) {
        return this.request('PUT', url, body);
    },
    delete(url) {
        return this.request('DELETE', url);
    },
};

// Flash message lintas redirect (mis. setelah simpan form lalu pindah ke index).
window.flash = {
    set(message, type = 'success') {
        sessionStorage.setItem('pos-flash', JSON.stringify({ message, type }));
    },
    pop() {
        const v = sessionStorage.getItem('pos-flash');
        if (!v) return null;
        sessionStorage.removeItem('pos-flash');
        try {
            return JSON.parse(v);
        } catch (e) {
            return null;
        }
    },
};

// Toast global via Alpine store. Dipakai: $store.toasts.push('Pesan', 'success').
document.addEventListener('alpine:init', () => {
    // Cetak struk: pilih ukuran 'thermal' (80mm) atau 'a4'.
    Alpine.store('receipt', {
        money(v) {
            return 'Rp ' + Number(v || 0).toLocaleString('id-ID');
        },
        date(trx) {
            return trx.created_at ? new Date(trx.created_at).toLocaleString('id-ID') : new Date().toLocaleString('id-ID');
        },
        // Konfigurasi toko dari window.posSettings (server-side), dengan fallback.
        store() {
            const s = window.posSettings || {};
            return {
                name: s.storeName || 'MicroPOS',
                address: s.storeAddress || '',
                phone: s.storePhone || '',
                footer: s.receiptFooter || 'Terima kasih atas kunjungan Anda',
                logoUrl: s.logoUrl || '',
                color: s.primaryColor || '#0d9488',
            };
        },
        esc(v) {
            return String(v ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        },
        print(trx, size = 'thermal') {
            if (!trx) return;
            const html = size === 'a4' ? this.a4Html(trx) : this.thermalHtml(trx);
            const features = size === 'a4' ? 'width=820,height=920' : 'width=380,height=640';
            const w = window.open('', '_blank', features);
            if (!w) return;
            w.document.write(html);
            w.document.close();
            w.focus();
            // Beri jeda agar layout & font siap sebelum dialog cetak.
            setTimeout(() => w.print(), 250);
        },

        thermalHtml(trx) {
            const m = (v) => this.money(v);
            const e = (v) => this.esc(v);
            const store = this.store();
            const rows = (trx.items || [])
                .map((i) => `<tr><td>${e(i.item_name)}<br><span class="muted">${i.qty} x ${m(i.price_snapshot)}</span></td><td class="r">${m(i.subtotal)}</td></tr>`)
                .join('');
            const logo = store.logoUrl ? `<img src="${e(store.logoUrl)}" class="logo" alt="">` : '';
            const address = store.address ? `<div class="center muted">${e(store.address)}</div>` : '';
            const phone = store.phone ? `<div class="center muted">${e(store.phone)}</div>` : '';
            return `<!doctype html><html><head><meta charset="utf-8"><title>${e(trx.invoice_number) || 'Struk'}</title>
                <style>
                    @page { size: 80mm auto; margin: 3mm; }
                    * { font-family: 'Courier New', monospace; font-size: 12px; }
                    body { width: 72mm; margin: 0 auto; padding: 0; color: #000; }
                    h1 { font-size: 16px; text-align: center; margin: 0 0 2px; }
                    .center { text-align: center; }
                    .muted { color: #555; font-size: 11px; }
                    .logo { display: block; max-height: 48px; margin: 0 auto 4px; }
                    table { width: 100%; border-collapse: collapse; }
                    td { padding: 2px 0; vertical-align: top; }
                    .r { text-align: right; }
                    hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
                    .row { display: flex; justify-content: space-between; }
                    .bold { font-weight: bold; }
                </style></head><body>
                ${logo}
                <h1>${e(store.name)}</h1>
                ${address}
                ${phone}
                <hr>
                <div class="row"><span>No</span><span>${e(trx.invoice_number) || '-'}</span></div>
                <div class="row"><span>Tanggal</span><span>${this.date(trx)}</span></div>
                <div class="row"><span>Kasir</span><span>${e(trx.kasir_name) || '-'}</span></div>
                <div class="row"><span>Pelanggan</span><span>${e(trx.customer_name) || 'Umum'}</span></div>
                <hr>
                <table>${rows}</table>
                <hr>
                ${Number(trx.discount) > 0 ? `<div class="row"><span>Subtotal</span><span>${m(trx.subtotal)}</span></div>
                <div class="row"><span>Diskon</span><span>- ${m(trx.discount)}</span></div>` : ''}
                <div class="row bold"><span>TOTAL</span><span>${m(trx.total)}</span></div>
                <div class="row"><span>Bayar</span><span>${m(trx.payment_amount)}</span></div>
                <div class="row"><span>Kembali</span><span>${m(trx.change_amount)}</span></div>
                <hr>
                <div class="center muted">${e(store.footer)}</div>
                </body></html>`;
        },

        a4Html(trx) {
            const m = (v) => this.money(v);
            const e = (v) => this.esc(v);
            const store = this.store();
            const rows = (trx.items || [])
                .map((i, idx) => `<tr>
                    <td class="c">${idx + 1}</td>
                    <td>${e(i.item_name)}${i.price_type_used ? '<br><span class="muted">' + e(i.price_type_used) + '</span>' : ''}</td>
                    <td class="r">${m(i.price_snapshot)}</td>
                    <td class="c">${i.qty}</td>
                    <td class="r">${m(i.subtotal)}</td>
                </tr>`)
                .join('');
            const brand = store.logoUrl
                ? `<img src="${e(store.logoUrl)}" style="max-height:56px" alt="">`
                : `<div class="brand">${e(store.name)}</div>`;
            const subline = [store.address, store.phone].filter(Boolean).map(e).join('<br>');
            return `<!doctype html><html><head><meta charset="utf-8"><title>${e(trx.invoice_number) || 'Invoice'}</title>
                <style>
                    @page { size: A4; margin: 15mm; }
                    * { font-family: Arial, Helvetica, sans-serif; color: #111; box-sizing: border-box; }
                    body { margin: 0; font-size: 13px; }
                    .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid ${store.color}; padding-bottom: 12px; }
                    .brand { font-size: 24px; font-weight: 700; color: ${store.color}; }
                    .store-name { font-size: 15px; font-weight: 700; margin-top: 4px; }
                    .muted { color: #666; }
                    .title { text-align: right; }
                    .title h2 { margin: 0; font-size: 18px; letter-spacing: 1px; }
                    .meta { display: flex; justify-content: space-between; margin: 16px 0; }
                    .meta div { line-height: 1.6; }
                    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
                    th { background: #f7f7f7; text-align: left; padding: 4px 8px; font-size: 12px; border-bottom: 2px solid ${store.color}; }
                    td { padding: 2px 8px; border-bottom: 1px solid #eee; vertical-align: top; line-height: 1.25; }
                    th.r, td.r { text-align: right; } th.c, td.c { text-align: center; }
                    .muted { font-size: 11px; }
                    .totals { width: 280px; margin-left: auto; margin-top: 12px; }
                    .totals .row { display: flex; justify-content: space-between; padding: 4px 8px; }
                    .totals .grand { border-top: 2px solid ${store.color}; font-weight: 700; font-size: 15px; }
                    .foot { margin-top: 40px; text-align: center; color: #666; font-size: 12px; }
                </style></head><body>
                <div class="head">
                    <div>
                        ${brand}
                        ${store.logoUrl ? `<div class="store-name">${e(store.name)}</div>` : ''}
                        ${subline ? `<div class="muted">${subline}</div>` : ''}
                    </div>
                    <div class="title">
                        <h2>INVOICE</h2>
                        <div class="muted">${e(trx.invoice_number) || '-'}</div>
                    </div>
                </div>
                <div class="meta">
                    <div><b>Pelanggan</b><br>${e(trx.customer_name) || 'Pelanggan Umum'}</div>
                    <div style="text-align:right"><b>Tanggal:</b> ${this.date(trx)}<br><b>Kasir:</b> ${e(trx.kasir_name) || '-'}</div>
                </div>
                <table>
                    <thead><tr><th class="c">#</th><th>Item</th><th class="r">Harga</th><th class="c">Qty</th><th class="r">Subtotal</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
                <div class="totals">
                    ${Number(trx.discount) > 0 ? `<div class="row"><span>Subtotal</span><span>${m(trx.subtotal)}</span></div>
                    <div class="row"><span>Diskon</span><span>− ${m(trx.discount)}</span></div>` : ''}
                    <div class="row"><span>Total</span><span>${m(trx.total)}</span></div>
                    <div class="row"><span>Bayar</span><span>${m(trx.payment_amount)}</span></div>
                    <div class="row grand"><span>Kembalian</span><span>${m(trx.change_amount)}</span></div>
                </div>
                <div class="foot">${e(store.footer)}</div>
                </body></html>`;
        },
    });

    Alpine.store('toasts', {
        items: [],
        _id: 0,
        push(message, type = 'info', timeout = 3000) {
            const id = ++this._id;
            this.items.push({ id, message, type });
            setTimeout(() => this.remove(id), timeout);
        },
        remove(id) {
            this.items = this.items.filter((t) => t.id !== id);
        },
        success(m) {
            this.push(m, 'success');
        },
        error(m) {
            this.push(m, 'danger', 4500);
        },
    });
});

window.Alpine = Alpine;

Alpine.start();
