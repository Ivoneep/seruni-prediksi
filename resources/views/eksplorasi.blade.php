@extends('layouts.app')
@section('title', 'Eksplorasi Data')

@section('content')
@php
  $D = \App\Services\SeruniData::class;
  $maksMenu = max(array_column($perMenu, 'total') ?: [1]);
  $maksLok = max(array_column($perLokasi, 'total') ?: [1]);
  $maksHari = max(array_column($perHari, 'rata') ?: [1]) ?: 1;
  $rataHari = array_map(fn ($h) => $h['rata'], $perHari);
  $puncak = array_search(max($rataHari), $rataHari);
  $rataAda = array_map(fn ($h) => $h['rata'], array_filter($perHari, fn ($h) => $h['n'] > 0));
  $terendah = array_search(min($rataAda), $rataAda);
  $r = abs($korelasi);
  $kuat = $r >= 0.7 ? 'kuat' : ($r >= 0.4 ? 'sedang' : 'lemah');
@endphp

<section class="page-head">
  <h1>Eksplorasi <span class="orange">data</span></h1>
  <p>Gambaran data penjualan bersih sebelum dimodelkan: {{ number_format($ringkas['baris'], 0, ',', '.') }} baris, {{ $ringkas['hari'] }} hari ({{ $D::tglPendek($ringkas['awal']) }} sampai {{ $D::tglPendek($ringkas['akhir']) }}), {{ $ringkas['lokasi'] }} lokasi, {{ $ringkas['menu'] }} menu.</p>
</section>

<section class="band cream">
  <div class="grid g4">
    <div class="card stat hl"><div class="num">{{ number_format($ringkas['terjual'], 0, ',', '.') }}</div><div class="cap">cup terjual</div></div>
    <div class="card stat"><div class="num"><small>Rp</small> {{ number_format($ringkas['omzet'] / 1000000, 1, ',', '.') }} <small>juta</small></div><div class="cap">total penjualan</div></div>
    <div class="card stat"><div class="num">{{ number_format($ringkas['terjual'] / max($ringkas['hari'], 1), 0, ',', '.') }}</div><div class="cap">rata-rata cup per hari, semua lokasi</div></div>
    <div class="card stat"><div class="num">{{ number_format($korelasi, 2, ',', '.') }}</div><div class="cap">korelasi stok dan terjual ({{ $kuat }})</div></div>
  </div>
</section>

<section class="band white" id="eda">
  <h2>Diagram penjualan <span class="orange">per titik</span></h2>
  <p class="lead">Satu titik adalah satu pasangan lokasi dan menu, jadi ada {{ count($lokasi) * count($menu) }} titik. Pilih lokasi, lalu menu, untuk membuka diagram batang penjualan hariannya.</p>

  <span class="picker-label" id="lbl-eda-lokasi">1. Pilih lokasi</span>
  <div class="pills" role="group" aria-labelledby="lbl-eda-lokasi">
    @foreach ($lokasi as $l)
      <button type="button" class="pill" data-eda-lokasi="{{ $l }}" aria-pressed="false">{{ $l }}</button>
    @endforeach
  </div>

  <span class="picker-label" id="lbl-eda-menu">2. Pilih menu</span>
  <div class="tiles" role="group" aria-labelledby="lbl-eda-menu" style="margin-bottom:30px">
    @foreach ($menu as $m)
      <button type="button" class="tile" data-eda-menu="{{ $m }}" aria-pressed="false">
        <img src="{{ asset($D::gambar($m)) }}" alt=""><span>{{ $D::label($m) }}</span>
      </button>
    @endforeach
  </div>

  <div id="eda-kosong" class="empty">Pilih lokasi dan menu di atas, maka diagram batang penjualan hariannya muncul di sini.</div>

  <div id="eda-hasil" hidden>
    <h3 id="eda-judul" style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.5rem;text-transform:uppercase;margin-bottom:16px"></h3>
    <div class="grid g4" style="margin-bottom:24px">
      <div class="card stat hl"><div class="num" id="eda-total"></div><div class="cap">total cup terjual</div></div>
      <div class="card stat"><div class="num" id="eda-hari"></div><div class="cap">hari tercatat</div></div>
      <div class="card stat"><div class="num" id="eda-rata"></div><div class="cap">rata-rata cup per hari tercatat</div></div>
      <div class="card stat"><div class="num" id="eda-maks"></div><div class="cap" id="eda-maks-cap"></div></div>
    </div>
    <div class="chart" id="eda-grafik"></div>
    <p class="note">Tidak ada batang berarti tanggal itu tidak tercatat untuk titik ini. Garis hitam tipis berarti tercatat tetapi tidak ada yang terjual.</p>
  </div>
</section>

<section class="band cream">
  <div class="grid g2">
    <div class="card">
      <h3>Terjual per menu</h3>
      <p class="sub">Total cup selama periode data, dengan persentase hari stok habis.</p>
      <div class="bars">
        @foreach ($perMenu as $m)
          <div class="bar-row">
            <div class="top"><span>{{ $m['label'] }}</span><span>{{ number_format($m['total'], 0, ',', '.') }} cup · habis {{ number_format($m['pct_habis'], 0, ',', '.') }}%</span></div>
            <div class="track"><div class="fill" style="width: {{ round($m['total'] / $maksMenu * 100, 1) }}%"></div></div>
          </div>
        @endforeach
      </div>
    </div>
    <div class="card">
      <h3>Terjual per lokasi</h3>
      <p class="sub">Lokasi dengan lebih banyak hari tercatat otomatis punya total lebih besar; lihat juga rata-rata per hari.</p>
      <div class="bars">
        @foreach ($perLokasi as $l)
          <div class="bar-row">
            <div class="top"><span>{{ $l['lokasi'] }}</span><span>{{ number_format($l['total'], 0, ',', '.') }} cup · {{ $l['n_hari'] }} hari · {{ number_format($l['rata_hari'], 0, ',', '.') }}/hari</span></div>
            <div class="track"><div class="fill k" style="width: {{ round($l['total'] / $maksLok * 100, 1) }}%"></div></div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

<section class="band white">
  <div class="grid g2">
    <div>
      <h2>Pola <span class="orange">mingguan</span></h2>
      <p class="lead">Rata-rata total cup per hari menurut nama hari. {{ $puncak }} paling ramai, {{ $terendah }} paling sepi. Karena itu hari dalam minggu dipakai sebagai fitur model.</p>
      <div class="bars">
        @foreach ($perHari as $nama => $h)
          <div class="bar-row">
            <div class="top"><span>{{ $nama }}</span><span>{{ number_format($h['rata'], 0, ',', '.') }} cup · {{ $h['n'] }} hari</span></div>
            <div class="track"><div class="fill {{ $nama === $puncak ? '' : 'k' }}" style="width: {{ round($h['rata'] / $maksHari * 100, 1) }}%"></div></div>
          </div>
        @endforeach
      </div>
    </div>
    <div>
      <h2>Stok dan <span class="orange">terjual</span></h2>
      <p class="lead">Korelasi Pearson stok awal dan jumlah terjual: <b>{{ number_format($korelasi, 2, ',', '.') }}</b> ({{ $kuat }}). Hati-hati membacanya: penjualan tidak bisa melebihi stok, jadi saat stok habis angka terjual mungkin lebih rendah dari permintaan sebenarnya.</p>
      <button class="btn-info" type="button" data-modal="info-stok" data-title="Kenapa stok habis penting">Kenapa stok habis penting?</button>
      <template id="info-stok">
        <p>Jika sebuah menu habis pukul 14.00, catatan hanya menunjukkan jumlah stok yang terjual, bukan berapa cup yang sebenarnya akan dibeli sampai malam.</p>
        <p>Akibatnya model cenderung menaksir permintaan terlalu rendah untuk menu yang sering habis. Tabel di samping menunjukkan seberapa sering tiap menu habis; yang persentasenya tertinggi paling terdampak.</p>
      </template>
      <div class="scroll-x" style="margin-top:20px">
        <table class="tbl">
          <thead><tr><th>Menu</th><th class="n">Rata stok</th><th class="n">Rata terjual</th><th class="n">Habis</th></tr></thead>
          <tbody>
          @foreach ($perMenu as $m)
            <tr><td>{{ $m['label'] }}</td><td class="n">{{ number_format($m['rata_stok'], 1, ',', '.') }}</td><td class="n">{{ number_format($m['rata'], 1, ',', '.') }}</td><td class="n">{{ number_format($m['pct_habis'], 0, ',', '.') }}%</td></tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>


@endsection

@push('scripts')
<script src="{{ asset('js/seruni-chart.js') }}"></script>
<script>
  (function () {
    var titik = {!! json_encode($titik, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    var label = {!! json_encode($labelMenu, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    var rentang = {!! json_encode($rentang) !!};
    var bln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    var hr = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    var lab = function (t) { var p = t.split('-'); return parseInt(p[2], 10) + ' ' + bln[parseInt(p[1], 10) - 1]; };
    var hari = function (t) { return hr[new Date(t + 'T00:00:00Z').getUTCDay()]; };
    var fmt = function (v, d) { return Number(v).toLocaleString('id-ID', { maximumFractionDigits: d == null ? 0 : d }); };
    var days = [];
    for (var t = new Date(rentang[0] + 'T00:00:00Z'), e = new Date(rentang[1] + 'T00:00:00Z'); t <= e; t = new Date(t.getTime() + 864e5)) days.push(t.toISOString().slice(0, 10));

    var st = { lokasi: null, menu: null };
    var $ = function (id) { return document.getElementById(id); };

    function tampil() {
      document.querySelectorAll('button[data-eda-lokasi]').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.edaLokasi === st.lokasi); });
      document.querySelectorAll('button[data-eda-menu]').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.edaMenu === st.menu); });
      var siap = st.lokasi && st.menu;
      $('eda-hasil').hidden = !siap; $('eda-kosong').hidden = !!siap;
      if (!siap) return;

      var d = (titik[st.lokasi] || {})[st.menu] || {};
      var nilai = days.map(function (x) { return d[x] != null ? d[x] : null; });
      var ada = nilai.filter(function (v) { return v != null; });
      var total = ada.reduce(function (a, b) { return a + b; }, 0);
      var maks = ada.length ? Math.max.apply(null, ada) : 0;
      var tglMaks = days[nilai.indexOf(maks)];
      $('eda-judul').textContent = label[st.menu] + ' di ' + st.lokasi;
      $('eda-total').textContent = fmt(total);
      $('eda-hari').textContent = ada.length;
      $('eda-rata').textContent = ada.length ? fmt(total / ada.length, 1) : '0';
      $('eda-maks').textContent = fmt(maks);
      $('eda-maks-cap').textContent = maks > 0 ? 'terbanyak dalam sehari, pada ' + hari(tglMaks) + ', ' + lab(tglMaks) : 'belum ada penjualan';

      SeruniChart.line($('eda-grafik'), {
        x: days.map(function (x) { return { label: lab(x) }; }),
        series: [{ name: 'Terjual', color: '#FF7A00', type: 'bar', values: nilai }],
        yTitle: 'cup terjual', label: 'Diagram batang penjualan harian ' + label[st.menu] + ' di ' + st.lokasi,
        yMax: maks < 4 ? 4 : null,
        tooltip: function (i) {
          var x = days[i];
          return '<b>' + hari(x) + ', ' + lab(x) + '</b><br>' + (nilai[i] == null ? 'tidak tercatat' : nilai[i] + ' cup');
        }
      });
    }
    document.querySelectorAll('button[data-eda-lokasi]').forEach(function (b) { b.addEventListener('click', function () { st.lokasi = b.dataset.edaLokasi; tampil(); }); });
    document.querySelectorAll('button[data-eda-menu]').forEach(function (b) { b.addEventListener('click', function () { st.menu = b.dataset.edaMenu; tampil(); }); });
  })();
</script>
@endpush
