'use strict';
/* Panel admin Solace Coffee: UC-02 Login, UC-08 Kelola Data Menu, UC-09 Daftar Pesanan, UC-10 Ubah Status Pesanan */
const API = new URL('api', document.baseURI).pathname.replace(/\/$/, '');
// perpindahan status yang diizinkan (sama dengan state machine di backend)
const TRANSISI = { menunggu: ['diproses', 'dibatalkan'], diproses: ['selesai', 'dibatalkan'], selesai: [], dibatalkan: [] };

let token = null, user = null, menu = [], orders = [];
const $ = (id) => document.getElementById(id);
const rp = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const pesan = (el, teks, jenis) => { $(el).innerHTML = teks ? `<div class="alert ${jenis || 'info'}">${esc(teks)}</div>` : ''; };

function saveSession() {
  try {
    if (token) { localStorage.setItem('sc_token', token); localStorage.setItem('sc_user', JSON.stringify(user)); }
    else { localStorage.removeItem('sc_token'); localStorage.removeItem('sc_user'); }
  } catch (e) { /* abaikan */ }
}

async function api(path, opt = {}) {
  const headers = { 'Content-Type': 'application/json' };
  if (token) headers.Authorization = 'Bearer ' + token;
  const res = await fetch(API + path, { method: opt.method || 'GET', headers, body: opt.body ? JSON.stringify(opt.body) : undefined });
  let data = null;
  try { data = await res.json(); } catch (e) { /* tanpa body */ }
  if (!res.ok) {
    if (res.status === 401 && token && !opt.noLogout) { adminLogout(); pesan('loginError', 'Sesi berakhir, silakan login kembali.', 'error'); }
    throw new Error((data && data.message) || 'Terjadi kesalahan');
  }
  return data;
}

/* ---------- login admin ---------- */
function tampilkan() {
  const admin = !!(user && user.role === 'admin');
  $('loginBox').style.display = admin ? 'none' : 'block';
  $('panel').style.display = admin ? 'block' : 'none';
  $('authArea').innerHTML = admin ? `<span>Halo, <b>${esc(user.nama)}</b></span><button class="btn" onclick="adminLogout()">Keluar</button>` : '';
}
async function adminLogin() {
  $('loginBtn').disabled = true;
  pesan('loginError', '');
  try {
    const r = await api('/users/login', { method: 'POST', body: { email: $('aEmail').value.trim(), password: $('aPass').value }, noLogout: true });
    if (r.user.role !== 'admin') throw new Error('Akun ini bukan admin');
    token = r.token; user = r.user; saveSession();
    tampilkan(); await muatMenu(); await muatPesanan();
  } catch (e) { pesan('loginError', e.message, 'error'); }
  $('loginBtn').disabled = false;
}
function adminLogout() { token = null; user = null; saveSession(); tampilkan(); }

function showTab(t) {
  $('viewMenu').style.display = t === 'menu' ? 'block' : 'none';
  $('viewPesanan').style.display = t === 'pesanan' ? 'block' : 'none';
  $('tabMenu').classList.toggle('active', t === 'menu');
  $('tabPesanan').classList.toggle('active', t === 'pesanan');
  if (t === 'pesanan') muatPesanan();
}

/* ---------- UC-08 Kelola Data Menu ---------- */
async function muatMenu() {
  try { menu = await api('/menu'); } catch (e) { pesan('menuMsg', e.message, 'error'); return; }
  $('menuRows').innerHTML = menu.map((m) => `
    <tr><td>${esc(m.nama_menu)}</td><td>${esc(m.kategori)}</td><td>${rp(m.harga)}</td><td>${esc(m.deskripsi)}</td>
    <td><div class="row-actions"><button class="btn small" onclick="editMenu(${m.id})">Ubah</button><button class="btn small btn-danger" onclick="hapusMenu(${m.id})">Hapus</button></div></td></tr>`).join('');
}
function resetForm() {
  $('mId').value = ''; $('mNama').value = ''; $('mHarga').value = ''; $('mDeskripsi').value = ''; $('mKategori').value = 'Kopi';
  $('formTitle').textContent = 'Tambah menu';
}
function editMenu(id) {
  const m = menu.find((x) => x.id === id);
  if (!m) return;
  $('mId').value = m.id; $('mNama').value = m.nama_menu; $('mKategori').value = m.kategori; $('mHarga').value = m.harga; $('mDeskripsi').value = m.deskripsi || '';
  $('formTitle').textContent = 'Ubah menu #' + m.id;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
async function simpanMenu() {
  const id = $('mId').value;
  const body = { nama_menu: $('mNama').value, kategori: $('mKategori').value, harga: parseInt($('mHarga').value, 10), deskripsi: $('mDeskripsi').value };
  $('mSimpan').disabled = true;
  try {
    if (id) await api('/menu/' + id, { method: 'PUT', body }); else await api('/menu', { method: 'POST', body });
    pesan('menuMsg', id ? 'Menu berhasil diperbarui' : 'Menu berhasil ditambahkan', 'info');
    resetForm(); await muatMenu();
  } catch (e) { pesan('menuMsg', e.message, 'error'); }
  $('mSimpan').disabled = false;
}
async function hapusMenu(id) {
  const m = menu.find((x) => x.id === id);
  if (!m || !confirm('Hapus menu "' + m.nama_menu + '"?')) return;
  try { await api('/menu/' + id, { method: 'DELETE' }); pesan('menuMsg', 'Menu berhasil dihapus', 'info'); await muatMenu(); }
  catch (e) { pesan('menuMsg', e.message, 'error'); }
}

/* ---------- UC-09 Lihat Daftar Pesanan dan UC-10 Ubah Status ---------- */
async function muatPesanan() {
  try { orders = await api('/pesanan'); } catch (e) { pesan('orderMsg', e.message, 'error'); return; }
  if (!orders.length) { $('orderRows').innerHTML = '<tr><td colspan="8">Belum ada pesanan masuk.</td></tr>'; return; }
  $('orderRows').innerHTML = orders.map((o) => {
    const next = TRANSISI[o.status];
    const aksi = next.length
      ? `<div class="row-actions"><select id="st${o.id}">${next.map((s) => `<option value="${s}">${s}</option>`).join('')}</select><button class="btn small btn-solid" onclick="ubahStatus(${o.id})">Simpan</button></div>`
      : '<span class="sub-label">Status akhir</span>';
    return `<tr><td>#${o.id}</td><td>${new Date(o.tanggal).toLocaleString('id-ID')}</td><td>${esc(o.nama_pelanggan)}</td>
      <td>${o.items.map((i) => esc(i.nama_menu) + ' &times; ' + i.jumlah).join('<br>')}</td><td>${rp(o.total_harga)}</td>
      <td>${o.metode ? esc(o.metode) + ' (' + esc(o.status_pembayaran) + ')' : '-'}</td><td><span class="badge ${o.status}">${o.status}</span></td><td>${aksi}</td></tr>`;
  }).join('');
}
async function ubahStatus(id) {
  try {
    await api('/pesanan/' + id + '/status', { method: 'PUT', body: { status: $('st' + id).value } });
    pesan('orderMsg', 'Status pesanan #' + id + ' diperbarui', 'info');
  } catch (e) { pesan('orderMsg', e.message, 'error'); }
  await muatPesanan();
}

/* ---------- mulai ---------- */
(async function init() {
  try { token = localStorage.getItem('sc_token'); user = JSON.parse(localStorage.getItem('sc_user') || 'null'); } catch (e) { token = null; user = null; }
  if (token && user && user.role === 'admin') {
    try { await api('/users/me', { noLogout: true }); } catch (e) { token = null; user = null; saveSession(); }
  } else { token = null; user = null; }
  tampilkan();
  if (user) { await muatMenu(); await muatPesanan(); }
})();
