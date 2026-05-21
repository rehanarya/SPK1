<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pengajuan extends Model
{
    protected $table = 'pengajuan';
    protected $primaryKey = 'id_pengajuan';
    public $timestamps = false;

    protected $fillable = [
        'id_nasabah',
        'id_periode',
        'C1_laba_usaha',
        'C2_pendapatan_bersih',
        'C3_nilai_agunan',
        'C4_besar_pembiayaan',
        'C5_jangka_waktu',
        'tanggal_pengajuan',
    ];

    protected function casts(): array
    {
        return [
            'C1_laba_usaha'        => 'float',
            'C2_pendapatan_bersih' => 'float',
            'C3_nilai_agunan'      => 'integer',
            'C4_besar_pembiayaan'  => 'float',
            'C5_jangka_waktu'      => 'integer',
            'tanggal_pengajuan'    => 'date',
        ];
    }

    public function nasabah(): BelongsTo
    {
        return $this->belongsTo(Nasabah::class, 'id_nasabah', 'id_nasabah');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class, 'id_periode', 'id_periode');
    }

    public function hasilPerhitungan(): HasOne
    {
        return $this->hasOne(HasilPerhitungan::class, 'id_pengajuan', 'id_pengajuan');
    }

    /** Kembalikan nilai kriteria sebagai array berurutan [C1, C2, C3, C4, C5] */
    public function nilaiKriteria(): array
    {
        return [
            $this->C1_laba_usaha,
            $this->C2_pendapatan_bersih,
            $this->C3_nilai_agunan,
            $this->C4_besar_pembiayaan,
            $this->C5_jangka_waktu,
        ];
    }
}
