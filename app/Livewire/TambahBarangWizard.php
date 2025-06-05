<?php

namespace App\Livewire;

use Spatie\LivewireWizard\Components\WizardComponent;
use App\Livewire\BarangIndukDanUnitAwalStep;
use App\Livewire\BarangNomorSeriStep;
use App\Models\Barang;
use App\Models\BarangQrCode;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Mechanisms\ComponentRegistry; // Untuk mendapatkan nama alias komponen

class TambahBarangWizard extends WizardComponent
{
    public function steps(): array
    {
        return [
            BarangIndukDanUnitAwalStep::class,
            BarangNomorSeriStep::class,
        ];
    }

    public function shouldSkipStep(string $stepName, array $allPreviousStepData): bool
    {
        // $stepName di sini adalah alias/nama komponen, bukan nama kelas
        $barangNomorSeriStepAlias = app(ComponentRegistry::class)->getName(BarangNomorSeriStep::class);

        if ($stepName === $barangNomorSeriStepAlias) {
            $barangIndukData = $allPreviousStepData['barang-induk-dan-unit-awal-step'] ?? []; // Kunci adalah alias
            return !(isset($barangIndukData['menggunakan_nomor_seri']) && ($barangIndukData['menggunakan_nomor_seri'] == true || $barangIndukData['menggunakan_nomor_seri'] == 1));
        }
        return false;
    }

    public function finishedWizard(): void
    {
        // Menggunakan $this->state()->all() untuk mendapatkan semua data step
        $allStepData = $this->state()->all();
        $dataStep1 = $allStepData['barang-induk-dan-unit-awal-step'] ?? []; // Kunci adalah alias
        $dataStep2 = $allStepData['barang-nomor-seri-step'] ?? null;   // Kunci adalah alias

        $user = Auth::user();
        /** @var \App\Models\User $user */

        try {
            DB::beginTransaction();

            $barang = Barang::create([
                'nama_barang'           => $dataStep1['nama_barang'],
                'kode_barang'           => $dataStep1['kode_barang'],
                'id_kategori'           => $dataStep1['id_kategori'],
                'merk_model'            => $dataStep1['merk_model'] ?? null,
                'ukuran'                => $dataStep1['ukuran'] ?? null,
                'bahan'                 => $dataStep1['bahan'] ?? null,
                'tahun_pembuatan'       => $dataStep1['tahun_pembuatan'] ?? null,
                'harga_perolehan_induk' => $dataStep1['harga_perolehan_induk'] ?? null,
                'sumber_perolehan_induk' => $dataStep1['sumber_perolehan_induk'] ?? null,
                'menggunakan_nomor_seri' => (bool) ($dataStep1['menggunakan_nomor_seri'] ?? false),
            ]);

            LogAktivitas::create([
                'id_user'           => $user->id,
                'aktivitas'         => 'Tambah Jenis Barang (Wizard Livewire)',
                'deskripsi'         => "Menambahkan jenis barang via wizard: {$barang->nama_barang} (ID: {$barang->id})",
                'model_terkait'     => Barang::class,
                'id_model_terkait'  => $barang->id,
                'data_baru'         => $barang->toArray(),
                'ip_address'        => request()->ip(),
                'user_agent'        => request()->userAgent(),
            ]);

            $jumlahUnit = (int) ($dataStep1['jumlah_unit_awal'] ?? 0);
            $serialNumbersInput = ($barang->menggunakan_nomor_seri && $dataStep2 && isset($dataStep2['serial_numbers']))
                ? $dataStep2['serial_numbers']
                : null;

            $unitDetails = [
                'id_ruangan'                => $dataStep1['id_ruangan_awal'] ?? null,
                'id_pemegang_personal'      => $dataStep1['id_pemegang_personal_awal'] ?? null,
                'kondisi'                   => $dataStep1['kondisi_unit_awal'] ?? BarangQrCode::KONDISI_BAIK,
                'harga_perolehan_unit'      => $dataStep1['harga_perolehan_unit_awal'] ?? $barang->harga_perolehan_induk,
                'tanggal_perolehan_unit'    => $dataStep1['tanggal_perolehan_unit_awal'] ?? now()->toDateString(),
                'sumber_dana_unit'          => $dataStep1['sumber_dana_unit_awal'] ?? $barang->sumber_perolehan_induk,
                'no_dokumen_perolehan_unit' => $dataStep1['no_dokumen_unit_awal'] ?? null,
                'deskripsi_unit'            => $dataStep1['deskripsi_unit_awal'] ?? 'Unit awal untuk ' . $barang->nama_barang,
            ];

            for ($i = 0; $i < $jumlahUnit; $i++) {
                BarangQrCode::createWithQrCodeImage(
                    idBarang: $barang->id,
                    noSeriPabrik: $serialNumbersInput[$i] ?? null,
                    hargaPerolehanUnit: $unitDetails['harga_perolehan_unit'],
                    tanggalPerolehanUnit: $unitDetails['tanggal_perolehan_unit'],
                    sumberDanaUnit: $unitDetails['sumber_dana_unit'],
                    noDokumenPerolehanUnit: $unitDetails['no_dokumen_perolehan_unit'],
                    idRuangan: $unitDetails['id_ruangan'],
                    kondisi: $unitDetails['kondisi'],
                    status: BarangQrCode::STATUS_TERSEDIA,
                    deskripsiUnit: $unitDetails['deskripsi_unit'] . ($jumlahUnit > 1 ? ' - Unit ' . ($i + 1) : ''),
                    idPemegangPersonal: $unitDetails['id_pemegang_personal']
                );
            }

            DB::commit();
            session()->flash('success_wizard', "Jenis barang '{$barang->nama_barang}' dan {$jumlahUnit} unit fisik berhasil ditambahkan.");
            $this->redirectRoute('barang.show', ['barang' => $barang->id]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error("Validasi gagal saat finishedWizard: " . json_encode($e->errors()));
            $this->dispatch('wizardError', 'Terjadi kesalahan validasi saat menyimpan data: ' . $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal menyimpan barang via wizard: {$e->getMessage()} di baris {$e->getLine()} pada file {$e->getFile()}");
            $this->dispatch('wizardError', 'Terjadi kesalahan fatal saat menyimpan data barang: ' . $e->getMessage());
        }
    }

    // Helper methods untuk digunakan di Blade, mendelegasikan ke State object
    public function isFirstStep(): bool
    {
        return $this->state()->isFirstStep($this->currentStepName);
    }

    public function isLastStep(): bool
    {
        return $this->state()->isLastStep($this->currentStepName);
    }

    // Metode ini tidak lagi diperlukan jika Blade menggunakan $this->state()->stepNames()
    // public function getStepNamesForNavigation()
    // {
    //     return $this->state()->stepNames();
    // }

    // Metode ini tidak lagi diperlukan jika Blade menggunakan $this->state()->isStepCompleted($stepName)
    // public function getIsStepCompleted(string $stepName): bool
    // {
    //     return $this->state()->isStepCompleted($stepName);
    // }

    // Metode ini tidak lagi diperlukan jika Blade menggunakan $this->state()->isStepAccessible($stepName)
    // public function getIsStepAccessible(string $stepName): bool
    // {
    //     return $this->state()->isStepAccessible($stepName);
    // }

    public function render()
    {
        return view('livewire.tambah-barang-wizard');
    }
}
