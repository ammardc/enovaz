import QRCode from 'qrcode';

const canvas = document.querySelector('[data-qris]');
if (canvas) {
    QRCode.toCanvas(canvas, canvas.dataset.qris, { width: 230, margin: 1, color: { dark: '#241535', light: '#ffffff' } })
        .catch(() => { canvas.replaceWith(document.createTextNode('QR belum dapat ditampilkan. Hubungi bantuan.')); });
}
const timer = document.querySelector('[data-expiry]');
if (timer) {
    const update = () => {
        const seconds = Math.max(0, Math.floor((new Date(timer.dataset.expiry).getTime() - Date.now()) / 1000));
        timer.textContent = seconds ? `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}` : 'Waktu tampilan habis, cek status pembayaran';
    };
    update();
    setInterval(update, 1000);
}
const quantity = document.querySelector('[data-unit-price]');
if (quantity) {
    const update = () => {
        const count = Math.max(1, Number(quantity.value) || 1);
        document.querySelector('[data-quantity-label]').textContent = `${count} item`;
        document.querySelector('[data-total-target]').textContent = 'Rp' + (count * Number(quantity.dataset.unitPrice)).toLocaleString('id-ID');
    };
    quantity.addEventListener('input', update);
}
