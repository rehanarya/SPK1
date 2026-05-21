<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\HasilPerhitungan;
use App\Models\Konfigurasi;
use App\Models\Pengajuan;
use App\Models\Periode;
use App\Services\WeightedProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Mengelola input pengajuan baru dan memicu kalkulasi WP otomatis.
 *
 * Alur:
 *   1. Petugas input nilai C1–C5 untuk nasabah + periode aktif
 *   2. Pada submit: simpan ke tabel pengajuan
 *   3. Hitung WP seluruh periode aktif (ulang semua agar V_i konsisten §4.4)
 *   4. Simpan hasil ke hasil_perhitungan
 *   5. Catat ke audit_log
 */
class PenilaianController extends Controller
{
    public function __construct(private WeightedProductService $wp) {}

    // ── Tampilan form input pengajuan baru ───────────────────────────────────

    public function create(): View|RedirectResponse
    {
        $periodeAktif = Periode::where('status', 'aktif')->first();

        if (!$periodeAktif) {
            return redirect()->route('pengajuan.index')
                ->with('warning', 'Tidak ada periode aktif. Hubungi Administrator.');
        }

        $nasabahList = \App\Models\Nasabah::orderBy('nama_nasabah')->get();
        $kriteriaList = \App\Models\Kriteria::orderBy('kode_kriteria')->get();

        return view('penilaian.create', compact('periodeAktif', 'nasabahList', 'kriteriaList'));
    }

    // ── Simpan pengajuan + trigger kalkulasi WP ──────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $periodeAktif = Periode::where('status', 'aktif')->firstOrFail();

        $data = $request->validate([
            'id_nasabah'           => ['required', 'exists:nasabah,id_nasabah'],
            'C1_laba_usaha'        => ['required', 'numeric', 'min:1'],
            'C2_pendapatan_bersih' => ['required', 'numeric', 'min:1'],
            'C3_nilai_agunan'      => ['required', 'integer', 'in:1,2,3,4'],
            'C4_besar_pembiayaan'  => ['required', 'numeric', 'min:1'],
            'C5_jangka_waktu'      => ['required', 'integer', 'min:1'],
            'tanggal_pengajuan'    => ['required', 'date'],
        ]);

        // Cegah duplikasi nasabah dalam satu periode
        $duplikat = Pengajuan::where('id_nasabah', $data['id_nasabah'])
            ->where('id_periode', $periodeAktif->id_periode)
            ->exists();

        if ($duplikat) {
            return back()->withInput()
                ->withErrors(['id_nasabah' => 'Nasabah ini sudah mengajukan pada periode aktif.']);
        }

        DB::transaction(function () use ($data, $periodeAktif) {
            // 1. Simpan pengajuan
            $pengajuan = Pengajuan::create(array_merge($data, [
                'id_periode' => $periodeAktif->id_periode,
            ]));

            // 2. Hitung ulang WP seluruh pengajuan periode ini agar V_i konsisten
            $this->hitungUlangPeriode($periodeAktif->id_periode);

            // 3. Catat audit
            AuditLog::create([
                'id_pengguna' => Auth::id(),
                'aksi'        => 'input_pengajuan',
                'modul'       => 'penilaian',
                'detail'      => ['id_pengajuan' => $pengajuan->id_pengajuan, 'id_nasabah' => $data['id_nasabah']],
                'ip_address'  => request()->ip(),
                'created_at'  => now(),
            ]);
        });

        return redirect()->route('hasil.index')
            ->with('success', 'Pengajuan berhasil disimpan dan kalkulasi WP telah diperbarui.');
    }

    // ── Eksekusi hitung WP untuk periode aktif (endpoint manual) ────────────

    public function hitungWP(Request $request): RedirectResponse
    {
        $periodeAktif = Periode::where('status', 'aktif')->firstOrFail();

        DB::transaction(function () use ($periodeAktif) {
            $this->hitungUlangPeriode($periodeAktif->id_periode);

            AuditLog::create([
                'id_pengguna' => Auth::id(),
                'aksi'        => 'eksekusi_wp',
                'modul'       => 'perhitungan',
                'detail'      => ['id_periode' => $periodeAktif->id_periode, 'kode' => $periodeAktif->kode_periode],
                'ip_address'  => request()->ip(),
                'created_at'  => now(),
            ]);
        });

        return redirect()->route('hasil.index')
            ->with('success', 'Kalkulasi WP periode ' . $periodeAktif->kode_periode . ' selesai.');
    }

    // ── Tampilan halaman hitung WP ────────────────────────────────────────────

    public function indexWP(): View|RedirectResponse
    {
        $periodeAktif = Periode::where('status', 'aktif')->first();

        if (!$periodeAktif) {
            return redirect()->route('dashboard')
                ->with('warning', 'Tidak ada periode aktif.');
        }

        $pengajuanList = Pengajuan::with('nasabah')
            ->where('id_periode', $periodeAktif->id_periode)
            ->get();

        $hasilList = HasilPerhitungan::with(['pengajuan.nasabah'])
            ->whereHas('pengajuan', fn ($q) => $q->where('id_periode', $periodeAktif->id_periode))
            ->orderBy('ranking')
            ->get();

        $kriteriaList = \App\Models\Kriteria::orderBy('kode_kriteria')->get();

        return view('penilaian.wp', compact('periodeAktif', 'pengajuanList', 'hasilList', 'kriteriaList'));
    }

    // ── Helper: hitung ulang semua WP untuk satu periode ────────────────────

    private function hitungUlangPeriode(int $idPeriode): void
    {
        $theta = (float) Konfigurasi::ambil('threshold_default', 250);
        $topN  = (int) Konfigurasi::ambil('top_n_prioritas', 5);

        $pengajuanList = Pengajuan::where('id_periode', $idPeriode)->get();

        if ($pengajuanList->isEmpty()) {
            return;
        }

        $hasilList = $this->wp->hitungPeriode($pengajuanList, $theta, $topN);

        foreach ($hasilList as $hasil) {
            HasilPerhitungan::updateOrCreate(
                ['id_pengajuan' => $hasil['id_pengajuan']],
                [
                    'vektor_S'   => $hasil['vektor_S'],
                    'vektor_V'   => $hasil['vektor_V'],
                    'ranking'    => $hasil['ranking'],
                    'status'     => $hasil['status'],
                    'created_at' => now(),
                ]
            );
        }
    }
}
