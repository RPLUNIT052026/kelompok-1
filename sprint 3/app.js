'use strict';
/* Frontend pelanggan Solace Coffee, terhubung ke REST API.
   UC-01 Registrasi, UC-02 Login, UC-03 Lihat Menu, UC-04 Kelola Keranjang, UC-05 Checkout,
   UC-06 Pilih Metode Pembayaran, UC-07 Lokasi (statis di index.html), UC-11 Lihat Status Pesanan */

const API = new URL('api', document.baseURI).pathname.replace(/\/$/, '');
const KATEGORI = ['Kopi', 'Non-Kopi', 'Snack', 'Makanan Berat', 'Dessert'];
const METODE = [
  { id: 'QRIS', sub: 'Scan dan bayar dengan aplikasi apa saja' },
  { id: 'DANA', sub: 'Bayar dengan saldo DANA' },
  { id: 'OVO', sub: 'Bayar dengan saldo OVO' },
  { id: 'GoPay', sub: 'Bayar dengan saldo GoPay' },
  { id: 'Transfer Bank', sub: 'BCA, Mandiri, BNI, BRI' },
];

let token = null, user = null;
let menu = [], cart = [], orders = [];
let activeKategori = 'Kopi';
let pending = null;      // pesanan yang sedang dibayar
let payMethod = null;
let afterLogin = null;   // aksi lanjutan setelah login (misalnya checkout)

const $ = (id) => document.getElementById(id);
const rp = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const idOpt = (m) => 'opt-' + m.replace(/\s/g, '_');

/* ---------------- sesi dan akses API ---------------- */
function loadSession() {
  try {
    token = localStorage.getItem('sc_token');
    user = JSON.parse(localStorage.getItem('sc_user') || 'null');
  } catch (e) { token = null; user = null; }
  if (!token || !user) { token = null; user = null; }
}
function saveSession() {
  try {
    if (token) { localStorage.setItem('sc_token', token); localStorage.setItem('sc_user', JSON.stringify(user)); }
    else { localStorage.removeItem('sc_token'); localStorage.removeItem('sc_user'); }
  } catch (e) { /* penyimpanan browser tidak tersedia */ }
}
function saveCart() { try { localStorage.setItem('sc_cart', JSON.stringify(cart)); } catch (e) { /* abaikan */ } }
function loadCart() { try { cart = JSON.parse(localStorage.getItem('sc_cart') || '[]'); } catch (e) { cart = []; } if (!Array.isArray(cart)) cart = []; }

async function api(path, opt = {}) {
  const headers = { 'Content-Type': 'application/json' };
  if (token) headers.Authorization = 'Bearer ' + token;
  const res = await fetch(API + path, { method: opt.method || 'GET', headers, body: opt.body ? JSON.stringify(opt.body) : undefined });
  let data = null;
  try { data = await res.json(); } catch (e) { /* tanpa body */ }
  if (!res.ok) {
    if (res.status === 401 && token && !opt.noLogout) logout(true);
    const err = new Error((data && data.message) || 'Terjadi kesalahan, silakan coba lagi');
    err.status = res.status;
    throw err;
  }
  return data;
}

/* ---------------- modal dan drawer ---------------- */
function openModal() { $('modalOverlay').classList.add('open'); }
function closeModal() { $('modalOverlay').classList.remove('open'); }
function openDrawer() { $('drawer').classList.add('open'); $('overlay').classList.add('open'); }
function closeDrawer() { $('drawer').classList.remove('open'); $('overlay').classList.remove('open'); }

/* ---------------- UC-01 Registrasi dan UC-02 Login ---------------- */
function renderAuth() {
  const a = $('authArea');
  if (user) {
    a.innerHTML = `<span>Halo, <b>${esc(user.nama)}</b></span>` +
      (user.role === 'admin' ? '<a class="btn" href="admin.html">Panel Admin</a>' : '') +
      '<button class="btn" onclick="logout()">Keluar</button>';
  } else {
    a.innerHTML = `<button class="btn" onclick="openAuth('login')">Masuk</button><button class="btn btn-solid" onclick="openAuth('daftar')">Daftar</button>`;
  }
}

function openAuth(mode, cb, info) {
  afterLogin = cb || null;
  const daftar = mode === 'daftar';
  $('modalBody').innerHTML = `
    <div class="auth-tabs">
      <button class="btn ${!daftar ? 'btn-solid' : ''}" onclick="openAuth('login', afterLogin)">Masuk</button>
      <button class="btn ${daftar ? 'btn-solid' : ''}" onclick="openAuth('daftar', afterLogin)">Daftar</button>
    </div>
    ${info ? `<div class="alert info">${esc(info)}</div>` : ''}
    <div id="authError"></div>
    ${daftar ? '<div class="field"><label for="fNama">Nama</label><input id="fNama" autocomplete="name"></div>' : ''}
    <div class="field"><label for="fEmail">Email</label><input id="fEmail" type="email" autocomplete="email"></div>
    <div class="field"><label for="fPass">Password${daftar ? ' (minimal 6 karakter)' : ''}</label><input id="fPass" type="password" autocomplete="${daftar ? 'new-password' : 'current-password'}"></div>
    <button class="confirm-btn" id="authSubmit" onclick="submitAuth(${daftar})">${daftar ? 'Daftar' : 'Masuk'}</button>
    <button class="close-modal" onclick="closeModal()">Batal</button>`;
  openModal();
}

async function submitAuth(daftar) {
  const email = $('fEmail').value.trim(), password = $('fPass').value;
  const btn = $('authSubmit');
  btn.disabled = true;
  $('authError').innerHTML = '';
  try {
    if (daftar) await api('/users/register', { method: 'POST', body: { nama: $('fNama').value, email, password }, noLogout: true });
    const r = await api('/users/login', { method: 'POST', body: { email, password }, noLogout: true });
    token = r.token; user = r.user;
    saveSession();
    renderAuth();
    closeModal();
    refreshOrders();
    const cb = afterLogin; afterLogin = null;
    if (cb) cb();
  } catch (e) {
    $('authError').innerHTML = `<div class="alert error">${esc(e.message)}</div>`;
    btn.disabled = false;
  }
}

function logout(expired) {
  token = null; user = null; orders = [];
  saveSession();
  renderAuth();
  refreshOrders();
  if (expired === true) openAuth('login', null, 'Sesi Anda sudah berakhir, silakan login kembali.');
}

/* ---------------- UC-03 Lihat Menu ---------------- */
async function loadMenu() {
  try { menu = await api('/menu'); }
  catch (e) { $('menuGrid').innerHTML = '<div class="empty-msg">Menu belum dapat dimuat. Pastikan server berjalan, lalu muat ulang halaman.</div>'; return; }
  renderMenu();
}
function setKategori(k) { activeKategori = k; renderMenu(); }
function renderMenu() {
  const cats = KATEGORI.filter((k) => menu.some((m) => m.kategori === k));
  if (!cats.length) { $('tabs').innerHTML = ''; $('menuGrid').innerHTML = '<div class="empty-msg">Belum ada menu.</div>'; return; }
  if (!cats.includes(activeKategori)) activeKategori = cats[0];
  $('tabs').innerHTML = cats.map((c) => `<button class="tab ${c === activeKategori ? 'active' : ''}" onclick="setKategori('${c}')">${c}</button>`).join('');
  $('menuGrid').innerHTML = menu.filter((m) => m.kategori === activeKategori).map((m) => `
    <div class="card">
      <h3>${esc(m.nama_menu)}</h3>
      <div class="desc">${esc(m.deskripsi || '')}</div>
      <div class="row"><span class="price">${rp(m.harga)}</span><button class="add-btn" onclick="addToCart(${m.id})">Tambah</button></div>
    </div>`).join('');
}

/* ---------------- UC-04 Kelola Keranjang ---------------- */
function addToCart(id) {
  const m = menu.find((x) => x.id === id);
  if (!m) return;
  const item = cart.find((c) => c.id === id);
  if (item) item.qty++; else cart.push({ id: m.id, name: m.nama_menu, price: m.harga, qty: 1 });
  saveCart(); renderCart(); openDrawer();
}
function changeQty(id, d) {
  const item = cart.find((c) => c.id === id);
  if (!item) return;
  item.qty += d;
  if (item.qty <= 0) cart = cart.filter((c) => c.id !== id);
  saveCart(); renderCart();
}
function removeItem(id) { cart = cart.filter((c) => c.id !== id); saveCart(); renderCart(); }
function renderCart() {
  $('cartCount').textContent = cart.reduce((s, c) => s + c.qty, 0);
  $('totalPrice').textContent = rp(cart.reduce((s, c) => s + c.price * c.qty, 0));
  $('checkoutBtn').disabled = cart.length === 0;
  $('drawerItems').innerHTML = cart.length
    ? cart.map((c) => `
      <div class="cart-item">
        <div><div class="name">${esc(c.name)}</div><div class="sub-label">${rp(c.price)}</div></div>
        <div class="qty">
          <button onclick="changeQty(${c.id}, -1)" aria-label="Kurangi">-</button>
          <span>${c.qty}</span>
          <button onclick="changeQty(${c.id}, 1)" aria-label="Tambah">+</button>
          <button onclick="removeItem(${c.id})" aria-label="Hapus">&times;</button>
        </div>
      </div>`).join('')
    : '<div class="empty-msg">Keranjang masih kosong. Yuk pilih menu favoritmu.</div>';
}
// buang item keranjang yang menunya sudah tidak tersedia; harga mengikuti daftar terbaru
function validateCart() {
  cart = cart.filter((c) => menu.some((m) => m.id === c.id)).map((c) => ({ ...c, price: menu.find((m) => m.id === c.id).harga }));
  saveCart(); renderCart();
}

/* ---------------- UC-05 Checkout dan UC-06 Pilih Metode Pembayaran ---------------- */
function checkout() {
  if (!cart.length) return;
  closeDrawer();
  if (!user) { openAuth('login', checkout, 'Silakan login untuk melanjutkan checkout.'); return; }   // include UC-02
  pending = null;
  openPay(null);
}

function openPay(order, errorMsg) {
  const items = order
    ? order.items.map((i) => ({ nama: i.nama_menu, jumlah: i.jumlah, subtotal: i.subtotal }))
    : cart.map((c) => ({ nama: c.name, jumlah: c.qty, subtotal: c.price * c.qty }));
  const total = order ? order.total_harga : cart.reduce((s, c) => s + c.price * c.qty, 0);
  pending = order || null;
  payMethod = null;
  $('modalBody').innerHTML = `
    <h2>${order ? 'Bayar pesanan #' + order.id : 'Checkout'}</h2>
    <div class="sub">Periksa pesanan Anda, lalu pilih metode pembayaran.</div>
    <div class="summary">
      ${items.map((i) => `<div class="sum-row"><span>${esc(i.nama)} &times; ${i.jumlah}</span><span>${rp(i.subtotal)}</span></div>`).join('')}
      <div class="sum-row total"><span>Total</span><span>${rp(total)}</span></div>
    </div>
    ${errorMsg ? `<div class="alert error">${esc(errorMsg)}</div>` : ''}
    <div id="payList">
      ${METODE.map((m) => `
        <label class="pay-option" id="${idOpt(m.id)}">
          <input type="radio" name="pay" value="${m.id}" onchange="selectPay('${m.id}')">
          <div><div class="label">${m.id}</div><div class="sub-label">${m.sub}</div></div>
        </label>`).join('')}
    </div>
    <div class="qris-box" id="qrisBox"><div class="qr"></div><div class="sub">Pindai kode QRIS di atas menggunakan aplikasi pembayaran favoritmu</div></div>
    <div class="field-error" id="payError" style="display:none"></div>
    <button class="confirm-btn" id="payBtn" onclick="doPay()">Bayar sekarang</button>
    <button class="close-modal" onclick="closeModal()">Tutup</button>`;
  openModal();
}

function selectPay(id) {
  payMethod = id;
  document.querySelectorAll('.pay-option').forEach((el) => el.classList.remove('selected'));
  $(idOpt(id)).classList.add('selected');
  $('qrisBox').classList.toggle('show', id === 'QRIS');
  $('payError').style.display = 'none';
}

async function doPay() {
  if (!payMethod) { $('payError').textContent = 'Pilih metode pembayaran terlebih dahulu'; $('payError').style.display = 'block'; return; }
  const btn = $('payBtn');
  btn.disabled = true; btn.textContent = 'Memproses...';
  try {
    if (!pending) {   // checkout dari keranjang: buat pesanan dulu (status menunggu)
      pending = await api('/pesanan', { method: 'POST', body: { items: cart.map((c) => ({ menu_id: c.id, jumlah: c.qty })) } });
      cart = []; saveCart(); renderCart();
    }
    const r = await api('/pembayaran', { method: 'POST', body: { pesanan_id: pending.id, metode: payMethod } });
    showSuccess(pending.id, payMethod, r.referensi);
    pending = null;
    refreshOrders();
  } catch (e) {
    if (pending && e.status === 402) {   // pembayaran ditolak: pesanan tetap menunggu, boleh coba lagi
      refreshOrders();
      openPay(pending, e.message + '. Silakan pilih metode lain atau coba lagi.');
    } else {
      btn.disabled = false; btn.textContent = 'Bayar sekarang';
      $('payError').textContent = e.message; $('payError').style.display = 'block';
      if (pending) refreshOrders();
    }
  }
}

function showSuccess(id, metode, referensi) {
  $('modalBody').innerHTML = `
    <div class="success">
      <div class="ok">&#10003;</div>
      <h2>Pembayaran berhasil</h2>
      <div class="sub">Pesanan #${id} &middot; dibayar via ${esc(metode)}${referensi ? '<br>Referensi: ' + esc(referensi) : ''}</div>
      <button class="close-modal" onclick="closeModal(); location.hash='pesanan'">Lihat status pesanan</button>
    </div>`;
}

/* ---------------- UC-11 Lihat Status Pesanan ---------------- */
async function refreshOrders() {
  const el = $('orders');
  if (!user) { el.innerHTML = '<div class="empty-msg">Masuk ke akun Anda untuk melihat status pesanan.</div>'; return; }
  try { orders = await api('/pesanan/user/' + user.id); }
  catch (e) { el.innerHTML = '<div class="empty-msg">Pesanan belum dapat dimuat.</div>'; return; }
  if (!orders.length) { el.innerHTML = '<div class="empty-msg">Belum ada pesanan.</div>'; return; }
  el.innerHTML = orders.map((o) => `
    <div class="order">
      <div class="order-head"><b>Pesanan #${o.id}</b><span class="badge ${o.status}">${o.status}</span></div>
      <div class="order-items">${o.items.map((i) => esc(i.nama_menu) + ' &times; ' + i.jumlah).join(', ')}</div>
      <div class="order-head"><span>${rp(o.total_harga)}${o.metode ? ' &middot; ' + esc(o.metode) : ''}</span><span class="sub-label">${new Date(o.tanggal).toLocaleString('id-ID')}</span></div>
      ${o.status === 'menunggu' ? `<div class="order-actions"><button class="btn btn-solid" onclick="payOrder(${o.id})">Bayar sekarang</button><button class="btn" onclick="cancelOrder(${o.id})">Batalkan</button></div>` : ''}
    </div>`).join('');
}
function payOrder(id) { const o = orders.find((x) => x.id === id); if (o) openPay(o); }
async function cancelOrder(id) {
  if (!confirm('Batalkan pesanan #' + id + '?')) return;
  try { await api('/pesanan/' + id + '/batal', { method: 'PUT' }); } catch (e) { alert(e.message); }
  refreshOrders();
}

/* ---------------- mulai ---------------- */
async function init() {
  loadSession();
  loadCart();
  if (token) {   // pastikan sesi tersimpan masih berlaku
    try { await api('/users/me', { noLogout: true }); } catch (e) { token = null; user = null; saveSession(); }
  }
  renderAuth();
  renderCart();
  await loadMenu();
  validateCart();
  refreshOrders();
}
init();
