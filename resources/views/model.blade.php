@extends('layouts.app')
@section('title', 'Perbandingan Model')

@section('content')
@php
  $D = \App\Services\SeruniData::class;
@endphp

<section class="page-head">
  <h1>Perbandingan <span class="orange">model</span></h1>
  <p>Random Forest dibandingkan dengan tebakan sederhana, dan diuji pada hari-hari yang tidak dipakai saat pelatihan.</p>
</section>

<section class="band cream">
  <div class="grid g2">
    <div class="card">
      <h3>Pembagian data</h3>
      <p class="sub">Dibagi menurut urutan tanggal (70% : 15% : 15%) agar model tidak melihat masa depan.</p>
      @foreach (['Training' => 'Latih', 'Validation' => 'Validasi', 'Testing' => 'Uji'] as $k => $v)
        @if (isset($split[$k]))
          <div class="kv"><span>{{ $v }}</span><b>{{ $D::tglPendek($split[$k][0]) }} – {{ $D::tglPendek($split[$k][1]) }} · {{ $split[$k][2] }} baris</b></div>
        @endif
      @endforeach
    </div>
    <div class="card">
      <h3>Model terpilih</h3>
      <p class="sub">{{ $model }}</p>
      <div class="kv"><span>Fitur masukan</span><b style="max-width:60%">{{ implode(', ', $fitur) }}</b></div>
      <div class="kv"><span>Target</span><b>Jumlah terjual per hari</b></div>
    </div>
  </div>
</section>

<section class="band white">
  <h2>Hasil <span class="orange">evaluasi</span></h2>
  <p class="lead">MAE adalah rata-rata selisih absolut dalam cup. R² mendekati 1 berarti lebih baik. Nilai di data latih selalu paling bagus karena model sudah melihatnya; pegang angka Validasi dan Uji.</p>
  <div class="scroll-x">
    <table class="tbl">
      <thead><tr><th>Data</th><th class="n">Baris</th><th class="n">MAE</th><th class="n">RMSE</th><th class="n">R²</th><th class="n">WAPE</th></tr></thead>
      <tbody>
      @foreach ($akhir as $a)
        <tr class="{{ $a['Dataset'] === 'Testing' ? 'best' : '' }}"><td>{{ ['Training' => 'Latih', 'Validation' => 'Validasi', 'Testing' => 'Uji'][$a['Dataset']] ?? $a['Dataset'] }}</td><td class="n">{{ $a['n'] }}</td><td class="n">{{ number_format($a['MAE'], 2, ',', '.') }}</td><td class="n">{{ number_format($a['RMSE'], 2, ',', '.') }}</td><td class="n">{{ number_format($a['R2'], 2, ',', '.') }}</td><td class="n">{{ number_format($a['WAPE'], 0, ',', '.') }}%</td></tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <p class="note">WAPE = total selisih dibagi total penjualan aktual. Nilainya tinggi karena sebagian besar menu terjual hanya 0 sampai 3 cup per hari, sehingga selisih satu cup sudah besar secara persentase.</p>
</section>

<section class="band cream">
  <h2>Lawan <span class="orange">pembanding</span></h2>
  <p class="lead">Model baru berguna bila lebih baik dari tebakan sederhana. Empat pendekatan diuji pada data yang sama; MAE terkecil ditandai.</p>
  <div class="grid g2">
    @foreach (['Validation' => 'Validasi', 'Testing' => 'Uji'] as $ds => $judul)
      @php
        $rows = array_values(array_filter($pembanding, fn ($p) => $p['Dataset'] === $ds));
        $min = $rows ? min(array_column($rows, 'MAE')) : 0;
      @endphp
      <div>
        <h3 style="font-family:'Syne',sans-serif;font-weight:800;margin-bottom:10px">Data {{ strtolower($judul) }}</h3>
        <div class="scroll-x">
          <table class="tbl">
            <thead><tr><th>Pendekatan</th><th class="n">MAE</th><th class="n">RMSE</th><th class="n">Arah tepat</th></tr></thead>
            <tbody>
            @foreach ($rows as $p)
              <tr class="{{ $p['MAE'] == $min ? 'best' : '' }}"><td>{{ $p['Model'] }}</td><td class="n">{{ number_format($p['MAE'], 2, ',', '.') }}</td><td class="n">{{ number_format($p['RMSE'], 2, ',', '.') }}</td><td class="n">{{ number_format($p['Arah_NaikTurun'], 0, ',', '.') }}%</td></tr>
            @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endforeach
  </div>
  <p class="note">"Arah tepat" dihitung hanya pada hari yang benar-benar berubah naik atau turun dibanding penjualan tercatat sebelumnya. Pendekatan "Lag" selalu menebak sama dengan hari sebelumnya, jadi tidak pernah menebak naik atau turun.</p>
</section>


@endsection
