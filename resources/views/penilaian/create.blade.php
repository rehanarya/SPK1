<x-app-layout title="Pengajuan Pembiayaan Baru" page-title="Pengajuan Pembiayaan Baru" :periode-aktif="$periodeAktif">

    <div class="section-header">
        <h1 class="text-h1">Pengajuan Pembiayaan Baru</h1>
        <div class="breadcrumb-meta">
            <a href="{{ route('pengajuan.index') }}">Pengajuan</a>
            <span style="margin: 0 6px; color: var(--color-text-muted);">›</span>
            <span>Tambah pengajuan</span>
        </div>
    </div>

    @if ($errors->any() && $errors->count() > 1)
        <x-alert type="danger">
            <strong>Beberapa data belum terisi dengan benar.</strong> Periksa pesan di bawah setiap kolom.
        </x-alert>
    @endif

    <form
        method="POST"
        action="{{ route('pengajuan.store') }}"
        novalidate
        autocomplete="off"
    >
        @csrf

        {{-- ── Seksi 1: Identitas Nasabah ───────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header">
                <div>
                    <h2 class="text-h2">Bagian 1 — Data Nasabah</h2>
                    <p class="text-meta" style="margin: 4px 0 0 0;">Pilih nasabah dan tetapkan tanggal pengajuan minggu ini.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6" style="min-width: 0;">
                        <x-form-field name="id_nasabah" label="Nama Nasabah" required :errors="$errors">
                            <div class="position-relative" style="width: 100%; max-width: 100%; min-width: 0;">
                                <input
                                    type="text"
                                    id="nasabah_search"
                                    data-filters-select="#id_nasabah"
                                    class="form-control mb-2"
                                    placeholder="Ketik nama atau nomor anggota..."
                                    autocomplete="off"
                                >
                                <select id="id_nasabah" name="id_nasabah" size="6"
                                        class="form-select form-select-listbox @error('id_nasabah') is-invalid @enderror" required>
                                    <option value="">— Pilih nasabah —</option>
                                    @foreach ($nasabahList as $nasabah)
                                        <option value="{{ $nasabah->id_nasabah }}"
                                            data-search="{{ strtolower($nasabah->nama_nasabah . ' ' . $nasabah->no_anggota) }}"
                                            title="{{ $nasabah->nama_nasabah }} ({{ $nasabah->no_anggota }})"
                                            {{ (string) old('id_nasabah') === (string) $nasabah->id_nasabah ? 'selected' : '' }}>
                                            {{ $nasabah->nama_nasabah }} ({{ $nasabah->no_anggota }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <x-slot:helper>
                                Tidak menemukan nama? <a href="{{ route('nasabah.create') }}">Tambahkan nasabah baru</a>.
                            </x-slot:helper>
                        </x-form-field>
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form-field name="periode" label="Minggu Pengajuan" :errors="$errors">
                            <input
                                type="text"
                                class="form-control"
                                value="{{ $periodeAktif->kode_periode }} · {{ $periodeAktif->tanggal_mulai->format('d M') }}–{{ $periodeAktif->tanggal_selesai->format('d M Y') }}"
                                disabled
                            >
                            <x-slot:helper>Pengajuan otomatis tercatat pada minggu yang sedang berjalan.</x-slot:helper>
                        </x-form-field>
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form-field name="tanggal_pengajuan" label="Tanggal Pengajuan" required :errors="$errors">
                            <input
                                type="date"
                                id="tanggal_pengajuan"
                                name="tanggal_pengajuan"
                                value="{{ old('tanggal_pengajuan', now()->format('Y-m-d')) }}"
                                min="{{ $periodeAktif->tanggal_mulai->format('Y-m-d') }}"
                                max="{{ $periodeAktif->tanggal_selesai->format('Y-m-d') }}"
                                class="form-control @error('tanggal_pengajuan') is-invalid @enderror"
                                required
                            >
                        </x-form-field>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Seksi 2: Faktor Penilaian ───────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header">
                <div>
                    <h2 class="text-h2">Bagian 2 — Faktor Penilaian Pembiayaan</h2>
                    <p class="text-meta" style="margin: 4px 0 0 0;">
                        Isi lima faktor di bawah ini berdasarkan data riil nasabah.
                        Tanda <x-criteria-badge type="benefit" /> berarti <em>semakin besar semakin baik</em>;
                        tanda <x-criteria-badge type="cost" /> berarti <em>semakin kecil semakin baik</em>.
                    </p>
                </div>
            </div>
            <div class="card-body">

                {{-- Laba Usaha (Benefit) --}}
                <x-form-field name="C1_laba_usaha" label="Laba Usaha Nasabah (Rp / bulan)" required badge="benefit" :errors="$errors"
                    helper="Keuntungan bersih yang diperoleh nasabah dari usahanya tiap bulan. Cukup ketik angka tanpa titik.">
                    <input
                        type="text"
                        id="C1_laba_usaha"
                        name="C1_laba_usaha"
                        value="{{ old('C1_laba_usaha') }}"
                        data-mask="rupiah"
                        inputmode="numeric"
                        placeholder="contoh: 6.500.000"
                        class="form-control @error('C1_laba_usaha') is-invalid @enderror"
                        required
                    >
                </x-form-field>

                {{-- Pendapatan Bersih (Benefit) --}}
                <x-form-field name="C2_pendapatan_bersih" label="Pendapatan Bersih Pribadi (Rp / bulan)" required badge="benefit" :errors="$errors"
                    helper="Sisa penghasilan nasabah setelah dikurangi kebutuhan pokok keluarga.">
                    <input
                        type="text"
                        id="C2_pendapatan_bersih"
                        name="C2_pendapatan_bersih"
                        value="{{ old('C2_pendapatan_bersih') }}"
                        data-mask="rupiah"
                        inputmode="numeric"
                        placeholder="contoh: 2.000.000"
                        class="form-control @error('C2_pendapatan_bersih') is-invalid @enderror"
                        required
                    >
                </x-form-field>

                {{-- Nilai Agunan (Benefit, Ordinal) --}}
                <x-form-field name="C3_nilai_agunan" label="Jaminan / Agunan yang Diberikan" required badge="benefit" :errors="$errors"
                    helper="Pilih jenis jaminan paling tinggi nilainya yang diserahkan nasabah.">
                    <select id="C3_nilai_agunan" name="C3_nilai_agunan" class="form-select @error('C3_nilai_agunan') is-invalid @enderror" required>
                        <option value="">— Pilih jenis jaminan —</option>
                        <option value="1" {{ old('C3_nilai_agunan') === '1' ? 'selected' : '' }}>Tanpa jaminan</option>
                        <option value="2" {{ old('C3_nilai_agunan') === '2' ? 'selected' : '' }}>BPKB sepeda motor</option>
                        <option value="3" {{ old('C3_nilai_agunan') === '3' ? 'selected' : '' }}>BPKB mobil</option>
                        <option value="4" {{ old('C3_nilai_agunan') === '4' ? 'selected' : '' }}>Sertifikat tanah / bangunan</option>
                    </select>
                </x-form-field>

                {{-- Besar Pembiayaan (Cost) --}}
                <x-form-field name="C4_besar_pembiayaan" label="Jumlah Pembiayaan yang Diajukan (Rp)" required badge="cost" :errors="$errors"
                    helper="Nominal pinjaman yang diminta nasabah pada pengajuan ini.">
                    <input
                        type="text"
                        id="C4_besar_pembiayaan"
                        name="C4_besar_pembiayaan"
                        value="{{ old('C4_besar_pembiayaan') }}"
                        data-mask="rupiah"
                        inputmode="numeric"
                        placeholder="contoh: 15.000.000"
                        class="form-control @error('C4_besar_pembiayaan') is-invalid @enderror"
                        required
                    >
                </x-form-field>

                {{-- Jangka Waktu (Cost) --}}
                <x-form-field name="C5_jangka_waktu" label="Lama Angsuran (bulan)" required badge="cost" :errors="$errors"
                    helper="Berapa bulan nasabah berencana melunasi pembiayaan. Maksimal 120 bulan.">
                    <input
                        type="number"
                        id="C5_jangka_waktu"
                        name="C5_jangka_waktu"
                        value="{{ old('C5_jangka_waktu') }}"
                        min="1"
                        max="120"
                        step="1"
                        placeholder="contoh: 24"
                        class="form-control @error('C5_jangka_waktu') is-invalid @enderror"
                        required
                    >
                </x-form-field>

            </div>
        </div>

        {{-- ── Action Bar ───────────────────────────────────────────────────────── --}}
        <div
            class="d-flex justify-content-between align-items-center"
            style="position: sticky; bottom: 0; background: var(--color-bg-app); padding: 16px 0; border-top: 1px solid var(--color-border); margin-top: 8px;"
        >
            <a href="{{ route('pengajuan.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary-strong">
                <i class="bi bi-check2-circle"></i> Simpan &amp; Nilai Kelayakan
            </button>
        </div>
    </form>

</x-app-layout>
