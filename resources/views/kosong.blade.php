@extends('layouts.app')
@section('title', 'Data belum diimpor')
@section('content')
<section class="page-head">
  <h1>Data belum <span class="orange">masuk</span></h1>
  <p>Database masih kosong, jadi belum ada yang bisa ditampilkan.</p>
</section>
<section class="band cream">
  <div class="card kosong-box">
    <h3>Isi database dengan dua perintah</h3>
    <p class="sub">Jalankan dari folder proyek Laravel:</p>
    <p><code>php artisan migrate</code></p>
    <p style="margin-top:10px"><code>php artisan seruni:import</code></p>
    <p class="note">Perintah kedua membaca file di <code>storage/app/seruni</code>. File itu dibuat oleh <code>python/export_model.py</code> dari data penjualan Anda; panduan lengkap ada di README.</p>
  </div>
</section>
@endsection
