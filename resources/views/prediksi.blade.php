@extends('layouts.app')
@section('title', 'Prediksi Penjualan')

@section('content')
@php $D = \App\Services\SeruniData::class; @endphp

<section class="page-head">
  <h1>Prediksi <span class="orange">penjualan</span></h1>
  <p>Pilih lokasi dan menu. Grafik membandingkan penjualan aktual dengan prediksi model, lalu memproyeksikan
  @if (count($proyeksi) === 2) {{ $D::tglPendek($proyeksi[0]) }} sampai {{ $D::tglPendek($proyeksi[1]) }} @else 7 hari ke depan @endif
  dan menyimpulkan apakah penjualannya naik atau turun.</p>
</section>

<section class="band cream" id="prediksi" data-url="{{ route('prediksi.data') }}" data-lokasi="{{ $pilihLokasi }}" data-menu="{{ $pilihMenu }}">
  <span class="picker-label" id="lbl-lokasi">1. Pilih lokasi</span>
  <div class="pills" role="group" aria-labelledby="lbl-lokasi">
    @foreach ($lokasi as $l)
      <button type="button" class="pill" data-lokasi="{{ $l }}" aria-pressed="{{ $l === $pilihLokasi ? 'true' : 'false' }}">{{ $l }}</button>
    @endforeach
  </div>

  <span class="picker-label" id="lbl-menu">2. Pilih menu</span>
  <div class="tiles" role="group" aria-labelledby="lbl-menu" style="margin-bottom:34px">
    @foreach ($menu as $m)
      <button type="button" class="tile" data-menu="{{ $m }}" aria-pressed="{{ $m === $pilihMenu ? 'true' : 'false' }}">
        <img src="{{ asset($D::gambar($m)) }}" alt=""><span>{{ $D::label($m) }}</span>
      </button>
    @endforeach
  </div>

  <div id="galat" class="warn" role="alert" hidden></div>

  <div id="hasil" class="result">
    <div class="stack">
      <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <img id="foto" src="" alt="" width="64" height="64" style="object-fit:cover;background:var(--orange);border:3px solid var(--black)">
        <h2 id="judul" style="margin:0;font-family:'Syne',sans-serif;font-weight:800;font-size:clamp(1.4rem,3vw,2rem);text-transform:uppercase;line-height:1.1">Memuat…</h2>
      </div>

      <div id="peringatan" class="warn" hidden>Menu ini sangat jarang terjual di lokasi ini, jadi prediksinya mendekati nol dan arah tren kurang bermakna.</div>

      <div class="chart" id="grafik" aria-live="polite"></div>
      <div id="kosong-grafik" class="warn" hidden>Belum ada penjualan tercatat untuk kombinasi ini.</div>
      <div class="legend">
        <span><i></i>Aktual</span><span><i class="d"></i>Prediksi</span><span><i class="t"></i>Stok</span>
        <label><input type="checkbox" id="cek-stok"> Tampilkan stok</label>
      </div>

      <div class="card" id="rincian">
        <h3>Cek tanggal tertentu</h3>
        <p class="sub">Pilih tanggal untuk melihat penjualan aktual, prediksi model, dan apakah arahnya naik atau turun dibanding catatan sebelumnya.</p>
        <label class="picker-label" for="tgl">Tanggal</label>
        <input type="date" id="tgl" class="date">
        <div id="rincian-isi" style="margin-top:18px" aria-live="polite"></div>
      </div>

      <div>
        <div class="seg" role="group" aria-label="Pilih tabel">
          <button type="button" data-tab="proyeksi" aria-pressed="true">Proyeksi</button>
          <button type="button" data-tab="uji" aria-pressed="false">Data uji</button>
        </div>
        <div class="scroll-x"><table class="tbl"><thead id="thead"></thead><tbody id="tbody"></tbody></table></div>
      </div>
    </div>

    <div class="stack">
      <div>
        <h3 style="font-family:'Syne',sans-serif;font-weight:800;margin-bottom:12px">Arah proyeksi</h3>
        <div class="stamp" id="stempel">
          <div class="ico" id="st-ico" aria-hidden="true"></div>
          <div><div class="kata" id="st-kata"></div><div class="ket" id="st-ket"></div></div>
        </div>
        <p class="note">Dianggap naik atau turun bila perubahannya minimal 10% dan 0,3 cup per hari. Di bawah itu disebut stabil.</p>
      </div>

      <div class="card">
        <h3>Rekomendasi stok</h3>
        <p class="sub">Kebutuhan = prediksi dibulatkan ke atas.</p>
        <div class="kv"><span>Stok biasa (7 catatan terakhir)</span><b id="stok-biasa"></b></div>
        <div class="kv"><span>Kebutuhan tertinggi</span><b id="stok-puncak"></b></div>
        <div class="kv"><span>Status</span><b><span class="badge" id="stok-status"></span></b></div>
      </div>

      <div class="card">
        <h3>Seberapa bisa dipercaya</h3>
        <p class="sub">Diukur pada hari yang tidak dipakai untuk melatih model.</p>
        <div class="kv"><span>Selisih rata-rata, validasi</span><b id="mae-val"></b></div>
        <div class="kv"><span>Selisih rata-rata, uji</span><b id="mae-test"></b></div>
        <div class="kv"><span>Arah naik/turun yang tepat</span><b id="arah-akurasi"></b></div>
        <p class="note" id="arah-catatan" hidden>Perubahan naik atau turun pada kombinasi ini terlalu sedikit (kurang dari 8), jadi persentase di atas hanya indikatif.</p>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('js/seruni-chart.js') }}"></script>
<script src="{{ asset('js/prediksi.js') }}"></script>
@endpush
