@extends('layouts.app')
@section('title', 'Beranda')
@section('navhero', '1')

@section('content')
@php
  $D = \App\Services\SeruniData::class;
  $tot = max(array_sum(array_column($perMenu, 'total')), 1);
  $maks = max(array_column($perMenu, 'total') ?: [1]);
@endphp

<section class="hero" id="home">
  <div class="hero-content reveal">
    <div class="hero-tag">PREDIKSI PENJUALAN</div>
    <h1>TAHU <span class="orange">STOK</span><br>SEBELUM<br><span class="outline-orange">HABIS</span></h1>
    <p class="hero-sub">Pilih lokasi dan menu, lihat grafik penjualannya, lalu cek apakah minggu depan naik atau turun.<br>Dihitung dari catatan harian Seruling dengan Random Forest.</p>
    <p class="hero-loc">Data {{ $D::tglPendek($ringkas['awal']) }} sampai {{ $D::tglPanjang($ringkas['akhir']) }}</p>
    <div class="hero-buttons">
      <a href="{{ route('prediksi') }}" class="btn-hero-primary">COBA PREDIKSI</a>
      <a href="{{ route('eksplorasi') }}" class="btn-hero-secondary">LIHAT DATA</a>
    </div>
  </div>
  <div class="hero-image reveal">
    <div class="hero-img-badges">
      <img src="{{ asset('img/landing.png') }}" alt="Kopi SERU.NI">
      <div class="badge-mulai">Proyeksi 7 hari</div>
      <div class="badge-bestseller">Naik / Turun</div>
    </div>
  </div>
</section>

<div style="background:#FF6B00;overflow:hidden;white-space:nowrap;padding:12px 0;border-top:3px solid #111;border-bottom:3px solid #111;" aria-hidden="true">
  <div class="marquee-track">
    @for ($i = 0; $i < 4; $i++)
    <span class="marquee-text">+ {{ $ringkas['lokasi'] }} TITIK SERULING + {{ $ringkas['menu'] }} MENU + {{ $ringkas['hari'] }} HARI DATA + RANDOM FOREST + PROYEKSI 7 HARI + CEK STOK &nbsp;&nbsp;&nbsp;&nbsp;</span>
    @endfor
  </div>
</div>

<section class="band cream">
  <div class="grid g4 reveal">
    <div class="card stat hl"><div class="num">{{ $ringkas['lokasi'] }}</div><div class="cap">titik jualan Seruling dalam data</div></div>
    <div class="card stat"><div class="num">{{ $ringkas['menu'] }}</div><div class="cap">menu minuman yang dicatat</div></div>
    <div class="card stat"><div class="num">{{ $ringkas['hari'] }}</div><div class="cap">hari pencatatan, {{ number_format($ringkas['baris'], 0, ',', '.') }} baris data</div></div>
    <div class="card stat"><div class="num">{{ number_format($ringkas['terjual'], 0, ',', '.') }}</div><div class="cap">cup terjual selama periode data</div></div>
  </div>
</section>

<section class="band white">
  <div class="grid g-main">
    <div class="reveal">
      <h2>Menu <span class="orange">terlaris</span></h2>
      <p class="lead">Total cup terjual per menu di semua lokasi. {{ reset($perMenu)['label'] }} menyumbang porsi terbesar, jadi model paling banyak belajar dari menu ini.</p>
      <div class="bars">
        @foreach ($perMenu as $m)
          <div class="bar-menu">
            <img src="{{ asset($D::gambar($m['menu'])) }}" alt="">
            <div class="bar-row">
              <div class="top"><span>{{ $m['label'] }}</span><span>{{ number_format($m['total'], 0, ',', '.') }} cup ({{ number_format($m['total'] / $tot * 100, 1, ',', '.') }}%)</span></div>
              <div class="track"><div class="fill" style="width: {{ round($m['total'] / $maks * 100, 1) }}%"></div></div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="stack reveal">
      <div class="card">
        <h3>Diproyeksi naik</h3>
        <p class="sub">Semua lokasi digabung, rata-rata 7 hari ke depan dibanding 7 hari terakhir data.</p>
        <div class="trend-list">
          @forelse ($naik as $t)
            <div class="trend-item"><img src="{{ asset($D::gambar($t['menu'])) }}" alt=""><span>{{ $t['label'] }}</span><span class="pct">&#9650; {{ number_format($t['persen'], 0, ',', '.') }}%<br><small style="font:500 .72rem 'Space Grotesk',sans-serif;color:#555">{{ number_format($t['acuan'], 1, ',', '.') }} &rarr; {{ number_format($t['proyeksi'], 1, ',', '.') }} cup/hari</small></span></div>
          @empty
            <p class="note">Tidak ada menu yang naik lebih dari 10%.</p>
          @endforelse
        </div>
      </div>
      <div class="card">
        <h3>Diproyeksi turun</h3>
        <p class="sub">Menu dengan proyeksi di bawah rata-rata 7 hari terakhir.</p>
        <div class="trend-list">
          @forelse ($turun as $t)
            <div class="trend-item"><img src="{{ asset($D::gambar($t['menu'])) }}" alt=""><span>{{ $t['label'] }}</span><span class="pct">&#9660; {{ number_format(abs($t['persen']), 0, ',', '.') }}%<br><small style="font:500 .72rem 'Space Grotesk',sans-serif;color:#555">{{ number_format($t['acuan'], 1, ',', '.') }} &rarr; {{ number_format($t['proyeksi'], 1, ',', '.') }} cup/hari</small></span></div>
          @empty
            <p class="note">Tidak ada menu yang turun lebih dari 10%.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</section>

@endsection
