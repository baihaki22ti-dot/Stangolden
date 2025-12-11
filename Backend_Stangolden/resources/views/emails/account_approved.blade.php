<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Akun Disetujui</title>
</head>
<body>

<h2>Akun Disetujui</h2>

<p>
    Halo, <strong>{{ $user->name ?? $user->email }}</strong>.
</p>

<p>
    Akun Anda telah disetujui dan kini dapat digunakan untuk mengakses platform STANGOLDEN.
</p>

@isset($expiresAt)
<p>
    Masa aktif sampai:
    <strong>{{ \Carbon\Carbon::parse($expiresAt)->toFormattedDateString() }}</strong>
</p>
@endisset

@php
    $akses = [];
    if (!empty($upkp)) $akses[] = 'UPKP';
    if (!empty($tugasBelajar)) $akses[] = 'Tugas Belajar';
@endphp

@if(count($akses))
<p>Akses modul: <strong>{{ implode(', ', $akses) }}</strong></p>
@endif

@if(isset($url))
<p>
    <a href="{{ $url }}" 
       style="padding: 12px 20px; background: #2563eb; color: white; text-decoration:none; border-radius: 8px;">
       Masuk ke Aplikasi
    </a>
</p>

<p>Jika tombol di atas tidak berfungsi, gunakan tautan berikut:</p>
<p>{{ $url }}</p>
@endif

<br>

<p>Terima kasih,<br>
<strong>STANGOLDEN Team</strong></p>

</body>
</html>
