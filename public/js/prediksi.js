/* Halaman Prediksi Penjualan: pilih lokasi + menu -> grafik, arah tren, rekomendasi stok */
(function () {
  var root = document.getElementById('prediksi');
  if (!root) return;

  var state = { lokasi: root.dataset.lokasi, menu: root.dataset.menu, tab: 'proyeksi', stok: false, data: null, tgl: null };
  var el = function (id) { return document.getElementById(id); };
  var esc = function (v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var fmt = function (v, d) { return v == null ? '–' : Number(v).toLocaleString('id-ID', { minimumFractionDigits: d || 0, maximumFractionDigits: d == null ? 1 : d }); };
  var sign = function (v) { return (v > 0 ? '+' : '') + fmt(v, 1); };
  var panah = { Naik: '▲', Turun: '▼', Stabil: '▬' };

  function syncButtons() {
    document.querySelectorAll('button[data-lokasi]').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.lokasi === state.lokasi); });
    document.querySelectorAll('button[data-menu]').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.menu === state.menu); });
  }

  function load() {
    syncButtons();
    var box = el('hasil');
    box.classList.add('loading');
    var q = '?lokasi=' + encodeURIComponent(state.lokasi) + '&menu=' + encodeURIComponent(state.menu);
    history.replaceState(null, '', q);
    fetch(root.dataset.url + q, { headers: { Accept: 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error('Gagal memuat data (' + r.status + ')'); return r.json(); })
      .then(function (d) { state.data = d; render(); })
      .catch(function (e) {
        el('galat').hidden = false;
        el('galat').textContent = e.message + '. Muat ulang halaman atau pilih kombinasi lain.';
      })
      .then(function () { box.classList.remove('loading'); });
  }

  function dayRange(seri) {
    var days = [], t0 = new Date(seri[0].tanggal + 'T00:00:00Z'), t1 = new Date(seri[seri.length - 1].tanggal + 'T00:00:00Z');
    for (var t = t0; t <= t1; t = new Date(t.getTime() + 864e5)) days.push(t.toISOString().slice(0, 10));
    return days;
  }

  function gambarUlang() { if (state.data) render(); }

  function dir(a, b) { return a > b ? 'Naik' : (a < b ? 'Turun' : 'Tetap'); }

  /* Rincian satu tanggal: aktual, prediksi, dan arah naik/turun dibanding catatan sebelumnya */
  function rincian() {
    var d = state.data, box = el('rincian-isi');
    var tgl = el('tgl'), awal = d.seri[0].tanggal, akhir = d.seri[d.seri.length - 1].tanggal;
    tgl.min = awal; tgl.max = akhir;
    if (!state.tgl || state.tgl < awal || state.tgl > akhir) {
      var terakhir = d.seri.filter(function (p) { return p.aktual != null; }).pop();
      state.tgl = terakhir ? terakhir.tanggal : awal;
    }
    tgl.value = state.tgl;
    var p = d.seri.filter(function (q) { return q.tanggal === state.tgl; })[0];
    if (!p) {
      var ada = d.seri.map(function (q) { return q.tanggal; });
      var sblm = ada.filter(function (t) { return t < state.tgl; }).pop(), ssdh = ada.filter(function (t) { return t > state.tgl; })[0];
      box.innerHTML = '<div class="warn">Tidak ada catatan <b>' + esc(d.label) + '</b> di <b>' + esc(d.lokasi) + '</b> pada tanggal ini (lokasi tidak berjualan atau tidak dicatat).' +
        (sblm || ssdh ? ' Tanggal terdekat: ' + [sblm, ssdh].filter(Boolean).map(function (t) { return '<button class="btn-info" type="button" data-pilih="' + t + '">' + esc(shortDate(t)) + '</button>'; }).join(' · ') : '') + '</div>';
      box.querySelectorAll('[data-pilih]').forEach(function (b) { b.addEventListener('click', function () { state.tgl = b.dataset.pilih; rincian(); gambarUlang(); }); });
      return;
    }
    var proy = p.dataset === 'Proyeksi', sebelum = p.baseline, h = '';
    var predBulat = p.prediksi == null ? null : Math.round(p.prediksi);
    var aA = (p.aktual != null && sebelum != null) ? dir(p.aktual, sebelum) : null;
    var aP = (predBulat != null && sebelum != null) ? dir(predBulat, sebelum) : null;
    var kotak = function (judul, arah, ket) {
      return '<div class="box ' + (arah ? arah.toLowerCase() : '') + '"><small>' + judul + '</small><b>' + (arah ? panah[arah] + ' ' + arah : '–') + '</b><span>' + ket + '</span></div>';
    };
    h += '<p style="font-weight:700;margin-bottom:4px">' + esc(p.hari + ', ' + p.label) + ' · ' + proyek(p.dataset) + '</p>';
    h += '<div class="verdict">';
    if (proy) {
      h += kotak('Aktual', null, 'belum terjadi, ini tanggal proyeksi');
    } else {
      h += kotak('Aktual', aA, p.aktual == null ? 'tidak ada' : (sebelum == null ? fmt(p.aktual) + ' cup (catatan pertama, belum ada pembanding)' : fmt(sebelum) + ' → ' + fmt(p.aktual) + ' cup'));
    }
    h += kotak('Prediksi', aP, p.prediksi == null ? 'model belum memprediksi tanggal ini' : (sebelum == null ? fmt(p.prediksi, 1) + ' cup' : fmt(sebelum, proy ? 1 : 0) + ' → ' + fmt(p.prediksi, 1) + ' cup'));
    h += '</div>';
    if (aA && aP) h += '<div class="cocok ' + (aA === aP ? 'ya' : '') + '">' + (aA === aP ? '✔ Arah prediksi tepat' : '✘ Arah prediksi meleset: aktual ' + aA.toLowerCase() + ', prediksi ' + aP.toLowerCase()) + '</div>';
    else if (proy && aP) h += '<div class="cocok">Proyeksi: ' + aP.toLowerCase() + ' dibanding ' + (p.baseline != null ? fmt(p.baseline, 1) + ' cup' : 'hari sebelumnya') + '. Siapkan stok sekitar ' + Math.ceil(p.prediksi) + ' cup.</div>';
    var baris = function (k, v) { return '<div class="kv"><span>' + k + '</span><b>' + v + '</b></div>'; };
    if (p.aktual != null && p.prediksi != null) h += baris('Selisih (aktual − prediksi)', sign(p.aktual - p.prediksi) + ' cup');
    if (!proy && p.stok != null) h += baris('Stok awal / sisa', fmt(p.stok) + ' / ' + fmt(p.sisa) + ' cup');
    h += '<p class="note">Arah dihitung dari penjualan tercatat sebelumnya untuk titik ini; prediksi dibulatkan ke cup utuh sebelum dibandingkan' + (!proy && p.sisa === 0 ? '. Stok habis pada hari ini, jadi aktual bisa lebih rendah dari permintaan sebenarnya' : '') + '.</p>';
    box.innerHTML = h;
  }

  function render() {
    var d = state.data;
    rincian();   // menetapkan state.tgl sebelum grafik digambar
    el('galat').hidden = true;
    el('judul').textContent = d.label + ' di ' + d.lokasi;
    el('foto').src = d.gambar; el('foto').alt = d.label;
    el('peringatan').hidden = !d.jarang;

    // --- stempel arah tren ---
    var t = d.tren, st = el('stempel');
    st.className = 'stamp ' + t.arah.toLowerCase();
    el('st-ico').textContent = panah[t.arah];
    el('st-kata').textContent = t.arah;
    el('st-ket').textContent = 'Rata-rata proyeksi ' + fmt(t.proyeksi, 1) + ' cup/hari, dibanding ' + fmt(t.acuan, 1) +
      ' cup/hari pada 7 hari terakhir data (' + (t.persen > 0 ? '+' : '') + fmt(t.persen, 0) + '%, dari ' + t.n_acuan + ' catatan).';

    // --- stok ---
    var s = d.stok;
    el('stok-biasa').textContent = fmt(s.biasa, 1) + ' cup';
    el('stok-puncak').textContent = s.puncak + ' cup' + (s.hari_puncak ? ' (' + s.hari_puncak + ')' : '');
    var bd = el('stok-status'); bd.textContent = s.status; bd.className = 'badge' + (s.status === 'Tambah stok' ? ' tambah' : '');

    // --- akurasi ---
    var a = d.akurasi, r = d.arah;
    el('mae-val').textContent = a.Validation.mae == null ? '–' : fmt(a.Validation.mae, 2) + ' cup';
    el('mae-test').textContent = a.Testing.mae == null ? '–' : fmt(a.Testing.mae, 2) + ' cup';
    el('arah-akurasi').textContent = r.pct == null ? 'tidak ada data' : r.pct + '% (' + r.benar + ' dari ' + r.n + ')';
    el('arah-catatan').hidden = r.andal;

    // --- grafik: sumbu harian penuh agar tanggal tak tercatat jadi celah ---
    var days = dayRange(d.seri), byDate = {};
    d.seri.forEach(function (p) { byDate[p.tanggal] = p; });
    var x = days.map(function (dt) { var p = byDate[dt]; return { label: p ? p.label : shortDate(dt), date: dt, p: p || null }; });
    var firstProy = x.findIndex(function (o) { return o.p && o.p.dataset === 'Proyeksi'; });
    var val = function (k) { return x.map(function (o) { return o.p && o.p[k] != null ? Number(o.p[k]) : null; }); };
    var idx = function (name) {
      var sp = d.split[name]; if (!sp) return null;
      var a1 = days.indexOf(sp[0]), b1 = days.indexOf(sp[1]);
      return a1 < 0 ? null : { from: a1, to: b1 };
    };
    var bands = [];
    [['Training', 'rgba(255,255,255,0)', 'Latih (in-sample)'], ['Validation', 'rgba(255,122,0,.10)', 'Validasi'], ['Testing', 'rgba(255,122,0,.22)', 'Uji']].forEach(function (b) {
      var i = idx(b[0]); if (i) bands.push({ from: i.from, to: i.to, fill: b[1], label: b[2], edge: b[0] !== 'Training' });
    });
    if (firstProy > 0) bands.push({ from: firstProy, to: x.length - 1, fill: 'rgba(17,17,17,.10)', label: 'Proyeksi', edge: true });
    var seriesCfg = [
      { name: 'Aktual', color: '#111', width: 2.5, values: val('aktual') },
      { name: 'Prediksi', color: '#FF7A00', width: 2.5, dash: '7 5', values: val('prediksi'), hollowFrom: firstProy > -1 ? firstProy : null }
    ];
    if (state.stok) seriesCfg.unshift({ name: 'Stok', color: '#9a9a9a', type: 'step', values: val('stok') });
    SeruniChart.line(el('grafik'), {
      x: x, series: seriesCfg, bands: bands, yTitle: 'cup', label: 'Aktual dan prediksi ' + d.label + ' di ' + d.lokasi,
      tooltip: function (i) {
        var o = x[i], p = o.p; if (!p) return '<b>' + esc(shortDate(o.date)) + '</b><br>tidak tercatat';
        return '<b>' + esc(p.hari + ', ' + p.label) + '</b><br>Aktual: ' + fmt(p.aktual) + '<br>Prediksi: ' + fmt(p.prediksi, 1) + (p.stok != null ? '<br>Stok: ' + fmt(p.stok, 1) : '');
      },
      mark: days.indexOf(state.tgl),
      onSelect: function (i) { if (x[i].p) { state.tgl = x[i].date; el('tgl').value = state.tgl; rincian(); gambarUlang(); } }
    });
    el('kosong-grafik').hidden = d.seri.some(function (p) { return p.aktual != null; });
    tabel();
  }

  function shortDate(iso) {
    var m = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], p = iso.split('-');
    return parseInt(p[2], 10) + ' ' + m[parseInt(p[1], 10) - 1];
  }

  function tabel() {
    var d = state.data, rows;
    document.querySelectorAll('[data-tab]').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.tab === state.tab); });
    if (state.tab === 'proyeksi') {
      var proy = d.seri.filter(function (p) { return p.dataset === 'Proyeksi'; }), prev = null;
      var last = d.seri.filter(function (p) { return p.aktual != null; }).pop();
      prev = last ? last.aktual : null;
      el('thead').innerHTML = '<tr><th>Tanggal</th><th>Hari</th><th class="n">Prediksi</th><th class="n">Siapkan stok</th><th>Arah</th></tr>';
      rows = proy.map(function (p, i) {
        var arah = prev == null ? 'Stabil' : (p.prediksi - prev >= 0.3 ? 'Naik' : p.prediksi - prev <= -0.3 ? 'Turun' : 'Stabil');
        prev = p.prediksi;
        return '<tr><td><button class="row" data-i="' + p.tanggal + '">' + esc(p.label) + '</button></td><td>' + esc(p.hari) + '</td><td class="n">' + fmt(p.prediksi, 1) +
          '</td><td class="n">' + Math.ceil(p.prediksi) + '</td><td>' + panah[arah] + ' ' + arah + '</td></tr>';
      });
    } else {
      var uji = d.seri.filter(function (p) { return p.dataset === 'Validation' || p.dataset === 'Testing'; }).reverse();
      el('thead').innerHTML = '<tr><th>Tanggal</th><th>Data</th><th class="n">Aktual</th><th class="n">Prediksi</th><th class="n">Selisih</th></tr>';
      rows = uji.map(function (p) {
        return '<tr><td><button class="row" data-i="' + p.tanggal + '">' + esc(p.label) + '</button></td><td>' + (p.dataset === 'Testing' ? 'Uji' : 'Validasi') +
          '</td><td class="n">' + fmt(p.aktual) + '</td><td class="n">' + fmt(p.prediksi, 1) + '</td><td class="n">' + sign(p.aktual - p.prediksi).replace('.', ',') + '</td></tr>';
      });
    }
    el('tbody').innerHTML = rows.join('') || '<tr><td colspan="5">Belum ada data.</td></tr>';
    el('tbody').querySelectorAll('button.row').forEach(function (b) {
      b.addEventListener('click', function () {
        var p = state.data.seri.filter(function (q) { return q.tanggal === b.dataset.i; })[0];
        if (p) detail(p);
      });
    });
  }

  function detail(p) {
    var d = state.data, proy = p.dataset === 'Proyeksi', h = '';
    var baris = function (k, v) { return '<div class="kv"><span>' + k + '</span><b>' + v + '</b></div>'; };
    h += baris('Lokasi', esc(d.lokasi)) + baris('Menu', esc(d.label));
    h += baris('Jenis data', proyek(p.dataset));
    if (!proy) h += baris('Aktual terjual', fmt(p.aktual) + ' cup');
    h += baris('Prediksi model', p.prediksi == null ? '–' : fmt(p.prediksi, 2) + ' cup');
    if (!proy && p.prediksi != null && p.aktual != null) h += baris('Selisih (aktual − prediksi)', sign(p.aktual - p.prediksi).replace('.', ',') + ' cup');
    if (proy) h += baris('Siapkan stok (dibulatkan ke atas)', Math.ceil(p.prediksi) + ' cup');
    else if (p.stok != null) h += baris('Stok awal / sisa', fmt(p.stok) + ' / ' + fmt(p.sisa) + ' cup');
    var cat = proy ? 'Proyeksi dihitung berurutan: prediksi hari ini dipakai sebagai masukan hari berikutnya, sehingga makin jauh makin kurang pasti.'
      : (p.sisa === 0 ? 'Stok habis pada hari ini, jadi penjualan aktual bisa lebih rendah dari permintaan sebenarnya.' : 'Data ini sudah tercatat, bukan proyeksi.');
    SeruniModal.open(p.hari + ', ' + p.label, h + '<p class="note">' + cat + '</p>');
  }
  function proyek(ds) { return { Training: 'Data latih', Validation: 'Data validasi', Testing: 'Data uji', Proyeksi: 'Proyeksi ke depan', Awal: 'Data awal (belum ada riwayat sebelumnya)' }[ds] || ds; }

  document.querySelectorAll('button[data-lokasi]').forEach(function (b) { b.addEventListener('click', function () { state.lokasi = b.dataset.lokasi; load(); }); });
  document.querySelectorAll('button[data-menu]').forEach(function (b) { b.addEventListener('click', function () { state.menu = b.dataset.menu; load(); }); });
  document.querySelectorAll('[data-tab]').forEach(function (b) { b.addEventListener('click', function () { state.tab = b.dataset.tab; tabel(); }); });
  el('tgl').addEventListener('change', function (e) { if (e.target.value) { state.tgl = e.target.value; if (state.data) { rincian(); gambarUlang(); } } });
  el('cek-stok').addEventListener('change', function (e) { state.stok = e.target.checked; if (state.data) render(); });
  load();
})();
