@extends('layouts.app')
@section('title', 'Insight & Kesimpulan')

@section('content')
<section class="page-head">
  <h1>Insight &amp; <span class="orange">kesimpulan</span></h1>
  <p>Temuan utama dari data dan model, apa yang belum bisa dijawab, dan jawaban atas pertanyaan yang paling sering muncul. Angkanya dihitung dari hasil yang tersimpan di database.</p>
</section>

<section class="band cream">
  <h2>Temuan <span class="orange">utama</span></h2>
  <div class="grid g3" style="margin-top:24px">
    @foreach ($temuan as $t)
      <div class="card reveal"><h3>{{ $t[0] }}</h3><p class="sub" style="margin:0;font-size:.9rem;line-height:1.65;color:#333">{{ $t[1] }}</p></div>
    @endforeach
  </div>
</section>

<section class="band white">
  <div class="grid g2">
    <div class="faq">
      <h2>Tanya <span class="orange">jawab</span></h2>
      <p class="lead">Klik pertanyaan untuk membuka jawabannya.</p>
      @foreach ($tanya as $qi => $q)
        <details @if ($qi === 0) open @endif>
          <summary>{{ $q[0] }}</summary>
          <div>{{ $q[1] }}</div>
        </details>
      @endforeach
    </div>
    <div>
      <h2>Yang masih <span class="orange">terbatas</span></h2>
      <p class="lead">Baca hasil model dengan catatan berikut.</p>
      <ul class="list-plain card">
        @foreach ($batas as $b)
          <li>{{ $b }}</li>
        @endforeach
      </ul>
    </div>
  </div>
</section>
@endsection
