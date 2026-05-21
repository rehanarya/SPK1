<x-app-layout title="Detail Penilaian" page-title="Detail Penilaian Nasabah">
    @php $p = $hasil->pengajuan; @endphp

    <div class="section-header">
        <h1 class="text-h1">Detail Penilaian — {{ $p?->nasabah?->nama_nasabah ?? '—' }}</h1>
        <div class="breadcrumb-meta">
            <a href="{{ route('hasil.index') }}">Hasil Penilaian</a>
            <span style="margin: 0 6px; color: var(--color-text-muted);">›</span>
            <span>Detail #{{ $hasil->id_hasil }}</span>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="text-h3">Data Penilaian Nasabah</h3></div>
                <div class="card-body" style="padding: 0;">
                    <table class="table-finansial">
                        <thead>
                            <tr>
                                <th>Faktor Penilaian</th>
                                <th>Sifat</th>
                                <th class="col-right">Nilai Nasabah</th>
                                <th class="col-right">Bobot</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kriteria as $k)
                                @php
                                    $field = match ($k->kode_kriteria) {
                                        'C1' => 'C1_laba_usaha',
                                        'C2' => 'C2_pendapatan_bersih',
                                        'C3' => 'C3_nilai_agunan',
                                        'C4' => 'C4_besar_pembiayaan',
                                        'C5' => 'C5_jangka_waktu',
                                    };
                                    $nilai = $p?->{$field};
                                    $sifatLabel = $k->tipe === 'benefit' ? 'Semakin besar, semakin baik' : 'Semakin kecil, semakin baik';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="text-body-strong">{{ $k->nama_kriteria }}</div>
                                        <div class="text-meta">{{ $sifatLabel }}</div>
                                    </td>
                                    <td><x-criteria-badge :type="$k->tipe" /> {{ ucfirst($k->tipe) }}</td>
                                    <td class="col-nominal">{{ is_numeric($nilai) ? number_format($nilai, 0, ',', '.') : '—' }}</td>
                                    <td class="col-nominal">{{ number_format(abs($k->bobot_normalisasi) * 100, 1, ',', '.') }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header"><h3 class="text-h3">Ringkasan Skor</h3></div>
                <div class="card-body">
                    <dl style="margin: 0; display: grid; grid-template-columns: 160px 1fr; gap: 12px 16px;">
                        <dt class="text-label">Skor Kelayakan</dt>
                        <dd class="font-numeric text-h2" style="margin: 0;">{{ number_format($hasil->vektor_S, 2, ',', '.') }}</dd>
                        <dt class="text-label">Nilai Prioritas</dt>
                        <dd class="font-numeric text-h2" style="margin: 0;">{{ number_format($hasil->vektor_V * 100, 2, ',', '.') }}%</dd>
                        <dt class="text-label">Urutan</dt>
                        <dd class="font-numeric" style="margin: 0;">{{ $hasil->ranking ?? '—' }}</dd>
                        <dt class="text-label">Status</dt>
                        <dd style="margin: 0;"><x-status-badge :status="$hasil->status" /></dd>
                        <dt class="text-label">Minggu Penilaian</dt>
                        <dd class="font-numeric" style="margin: 0;">{{ $p?->periode?->kode_periode ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card" style="margin-top: 16px;">
                <div class="card-header"><h3 class="text-h3">Riwayat Keputusan</h3></div>
                <div class="card-body" style="padding: 0;">
                    @if ($hasil->logKeputusan->isEmpty())
                        <x-empty-state icon="bi-clipboard" title="Belum ada keputusan" body="Tetapkan keputusan akhir dari menu Penetapan Keputusan." />
                    @else
                        <table class="table-finansial">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Petugas</th>
                                    <th>Keputusan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hasil->logKeputusan->sortByDesc('timestamp') as $k)
                                    <tr>
                                        <td class="text-meta">{{ $k->timestamp?->format('d/m H:i') ?? '—' }}</td>
                                        <td>{{ $k->pengguna?->nama ?? '—' }}</td>
                                        <td><x-status-badge :status="$k->keputusan_akhir" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
