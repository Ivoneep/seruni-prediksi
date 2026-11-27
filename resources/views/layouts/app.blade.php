<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Prediksi Penjualan')  | SERU.NI</title>
  <meta name="description" content="Prediksi penjualan harian SERU.NI per lokasi dan menu dengan Random Forest: lihat grafik, arah naik atau turun, dan rekomendasi stok.">
  <link rel="stylesheet" href="{{ asset('css/seruni.css') }}">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
  $menuNav = [
    ['beranda', 'Beranda'],
    ['eksplorasi', 'Eksplorasi'],
    ['prediksi', 'Prediksi'],
    ['model', 'Model'],
    ['insight', 'Insight'],
  ];
@endphp

@hasSection('navhero')
<nav class="navbar" id="navbar" data-hero="1">
@else
<nav class="navbar scrolled" id="navbar" data-hero="0">
@endif
  <a href="{{ route('beranda') }}" class="nav-logo">SERU<span class="dot-ni">.NI</span></a>
  <div class="nav-links">
    @foreach ($menuNav as $m)
      <a href="{{ route($m[0]) }}" @if (request()->routeIs($m[0])) class="active" aria-current="page" @endif>{{ $m[1] }}</a>
    @endforeach
  </div>
  <button class="hamburger" id="hamburger" aria-label="Buka menu" aria-expanded="false">
    <span></span><span></span><span></span>
  </button>
</nav>
<div class="mobile-menu" id="mobileMenu">
  @foreach ($menuNav as $m)
    <a href="{{ route($m[0]) }}" @if (request()->routeIs($m[0])) class="active" @endif>{{ $m[1] }}</a>
  @endforeach
</div>

<main>
@yield('content')
</main>

<footer class="footer">
  <div class="footer-grid">
    <div class="footer-brand">
      <div class="footer-logo">SERU<span class="dot-ni">.NI</span></div>
      <p>Proyek Data Mining: prediksi penjualan harian kopi Seru.ni di tiap titik Seruling dengan Random Forest Regression.</p>
    </div>
    <div class="footer-nav-col">
      <h4>HALAMAN</h4>
      <div class="footer-links">
        <a href="{{ route('beranda') }}">Beranda</a>
        <a href="{{ route('eksplorasi') }}">Eksplorasi Data</a>
        <a href="{{ route('prediksi') }}">Prediksi Penjualan</a>
        <a href="{{ route('model') }}">Perbandingan Model</a>
        <a href="{{ route('insight') }}">Insight &amp; Kesimpulan</a>
      </div>
    </div>
    <div class="footer-menu-col">
      <h4>SERU.NI</h4>
      <div class="footer-links">
        <a href="https://instagram.com/kopiseruni" target="_blank" rel="noopener">@kopiseruni</a>
        <a href="https://wa.me/6281234567890" target="_blank" rel="noopener">Pesan lewat WhatsApp</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-copy">Hasil model adalah perkiraan dari data penjualan yang tercatat, bukan jaminan angka penjualan.</div>
  </div>
</footer>

<div class="modal" id="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
  <div class="dialog">
    <button class="x" type="button" aria-label="Tutup">&#10005;</button>
    <h3 id="modal-title"></h3>
    <div id="modal-body"></div>
  </div>
</div>

<script src="{{ asset('js/seruni.js') }}"></script>
@stack('scripts')
</body>
</html>
