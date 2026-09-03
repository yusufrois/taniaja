<?php

namespace App\Services\Accounting;

use App\Models\ChartOfAccount;
use App\Models\Company;

/**
 * Bagan Akun standar untuk UMKM pertanian — dibuat berdasarkan pola
 * umum, BUKAN hasil konsultasi akuntan bersertifikasi. Sebaiknya
 * ditinjau akuntan sebelum dipakai untuk keperluan pajak/audit
 * sungguhan (lihat README-FASE-L1.md).
 *
 * Dipanggil dari 2 tempat: otomatis saat company baru daftar (lihat
 * CompanyRegistrationService), dan manual lewat endpoint untuk
 * company yang SUDAH ADA sebelum Fase L ini dibangun.
 */
class StandardChartOfAccountsSeeder
{
    private const ACCOUNTS = [
        // ASET
        ['code' => '1100', 'name' => 'Kas', 'type' => 'asset'],
        ['code' => '1110', 'name' => 'Bank', 'type' => 'asset'],
        ['code' => '1200', 'name' => 'Piutang Usaha', 'type' => 'asset'],
        ['code' => '1210', 'name' => 'Piutang Karyawan', 'type' => 'asset'],
        ['code' => '1300', 'name' => 'Persediaan Barang Dagang', 'type' => 'asset'],
        ['code' => '1310', 'name' => 'Persediaan Input Pertanian', 'type' => 'asset'],
        ['code' => '1400', 'name' => 'Aset Tetap', 'type' => 'asset'],

        // KEWAJIBAN
        ['code' => '2100', 'name' => 'Hutang Usaha', 'type' => 'liability'],
        ['code' => '2200', 'name' => 'Hutang Bank/Pinjaman', 'type' => 'liability'],
        ['code' => '2300', 'name' => 'Hutang Gaji', 'type' => 'liability'],

        // MODAL
        ['code' => '3100', 'name' => 'Modal Pemilik', 'type' => 'equity'],
        ['code' => '3200', 'name' => 'Laba Ditahan', 'type' => 'equity'],

        // PENDAPATAN
        ['code' => '4100', 'name' => 'Pendapatan Penjualan', 'type' => 'revenue'],

        // BEBAN
        ['code' => '5100', 'name' => 'Harga Pokok Penjualan (HPP)', 'type' => 'expense'],
        ['code' => '5200', 'name' => 'Beban Operasional', 'type' => 'expense'],
        ['code' => '5300', 'name' => 'Beban Gaji', 'type' => 'expense'],
    ];

    /**
     * Idempotent — firstOrCreate per (company, code), safe to call
     * repeatedly (e.g. accidentally triggered twice) without creating
     * duplicates.
     */
    public function seedFor(Company $company): void
    {
        foreach (self::ACCOUNTS as $account) {
            ChartOfAccount::firstOrCreate(
                ['company_id' => $company->id, 'code' => $account['code']],
                ['name' => $account['name'], 'type' => $account['type']]
            );
        }
    }
}
