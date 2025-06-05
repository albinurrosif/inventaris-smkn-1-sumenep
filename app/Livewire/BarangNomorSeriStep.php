<?php

namespace App\Livewire;

use App\Models\Barang; // Untuk mengambil kode_barang jika barang sudah dibuat sementara
use App\Models\BarangQrCode; // Untuk validasi unique
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\LivewireWizard\Components\StepComponent;

class BarangNomorSeriStep extends StepComponent
{
    public static function title(): string
    {
        return 'Nomor Seri';
    }

    public static function alias(): string
    {
        return 'barang-nomor-seri-step';
    }
    // Properti yang akan di-bind ke view
    public array $serial_numbers = [];

    // Properti untuk menampung data dari step sebelumnya
    public int $jumlah_unit_target = 0;
    public string $kode_barang_induk = '';
    public string $nama_barang_induk = '';
    public array $unit_details_awal_from_step1 = []; // Untuk referensi jika diperlukan

    // Listener untuk memastikan data di-refresh jika ada perubahan state antar step
    // (Mungkin tidak selalu diperlukan jika paket wizard menangani state dengan baik)
    // protected $listeners = ['refreshNomorSeriStep' => 'mount'];

    public function mount()
    {
        // Ambil data dari state step sebelumnya (BarangIndukDanUnitAwalStep)
        // Nama kunci di state() adalah kebab-case dari nama kelas step.
        $previousState = $this->state()->forStep(BarangIndukDanUnitAwalStep::class); // Gunakan nama kelas step sebelumnya

        $this->jumlah_unit_target = (int)($previousState['jumlah_unit_awal'] ?? 0);
        $this->kode_barang_induk = $previousState['kode_barang'] ?? '';
        $this->nama_barang_induk = $previousState['nama_barang'] ?? 'Barang Tidak Diketahui';

        // Simpan detail unit awal dari step 1 untuk referensi di view
        $this->unit_details_awal_from_step1 = [
            'kondisi' => $previousState['kondisi_unit_awal'] ?? '-',
            'id_ruangan' => $previousState['id_ruangan_awal'] ?? null,
            'id_pemegang_personal' => $previousState['id_pemegang_personal_awal'] ?? null,
            'harga_perolehan_unit' => $previousState['harga_perolehan_unit_awal'] ?? null,
            'tanggal_perolehan_unit' => $previousState['tanggal_perolehan_unit_awal'] ?? null,
        ];


        // Inisialisasi array serial_numbers berdasarkan jumlah_unit_target
        // Hanya inisialisasi jika belum ada atau ukurannya tidak sesuai
        if (count($this->serial_numbers) !== $this->jumlah_unit_target) {
            $this->serial_numbers = array_fill(0, $this->jumlah_unit_target, '');
        }
    }

    public function rules(): array
    {
        if ($this->jumlah_unit_target === 0) {
            return []; // Tidak ada validasi jika tidak ada unit yang diharapkan
        }

        $rules = [
            'serial_numbers' => 'required|array|size:' . $this->jumlah_unit_target,
        ];

        foreach (array_keys($this->serial_numbers) as $key) {
            $rules["serial_numbers.{$key}"] = [
                'required',
                'string',
                'max:100',
                // Pastikan 'distinct:ignore_case' bekerja sesuai harapan di Livewire untuk array
                // Jika tidak, validasi distinct mungkin perlu dilakukan secara manual atau di backend akhir
                'distinct:serial_numbers',
                Rule::unique('barang_qr_codes', 'no_seri_pabrik')->whereNull('deleted_at'),
            ];
        }
        return $rules;
    }

    public function messages(): array
    {
        if ($this->jumlah_unit_target === 0) {
            return [];
        }
        $messages = [
            'serial_numbers.required' => 'Daftar nomor seri tidak boleh kosong.',
            'serial_numbers.size' => 'Jumlah nomor seri harus :size unit, sesuai dengan jumlah unit yang ditentukan pada Step 1.',
        ];
        foreach (array_keys($this->serial_numbers) as $key) {
            $messages["serial_numbers.{$key}.required"] = "Nomor seri untuk unit ke-" . ($key + 1) . " wajib diisi.";
            $messages["serial_numbers.{$key}.distinct"] = "Nomor seri untuk unit ke-" . ($key + 1) . " duplikat dengan input lain.";
            $messages["serial_numbers.{$key}.unique"] = "Nomor seri untuk unit ke-" . ($key + 1) . " sudah terdaftar di sistem.";
            $messages["serial_numbers.{$key}.max"] = "Nomor seri untuk unit ke-" . ($key + 1) . " maksimal 100 karakter.";
        }
        return $messages;
    }

    public function submit()
    {
        if ($this->jumlah_unit_target > 0) {
            $this->validate();
        }
        // Data akan otomatis disimpan ke state wizard oleh paket.
        // Jika ini adalah step terakhir sebelum 'finishedWizard',
        // paket wizard akan otomatis memanggil finishedWizard() di komponen wizard utama.
        // Untuk paket Spatie, setelah validasi di step terakhir, ia akan memanggil finishedWizard().
    }

    public function suggestSerials()
    {
        if (empty($this->kode_barang_induk)) {
            session()->flash('step_error_nomor_seri', 'Kode barang induk tidak tersedia untuk membuat saran nomor seri.');
            return;
        }

        $suggestions = [];
        // Untuk suggest, idealnya kita tahu ID barang induk jika sudah dibuat sementara di DB,
        // atau setidaknya jumlah unit yang sudah ada untuk kode barang tersebut.
        // Untuk wizard baru, kita bisa mulai dari 1.
        // Kita perlu cara untuk mendapatkan count yang benar.
        // Jika barang belum ada di DB, kita tidak bisa query qrCodes()->withTrashed()->count().
        // Kita bisa pakai prefix kode barang dan nomor urut sederhana.
        $existingCountForSuggestion = 0; // Placeholder, idealnya query ke DB jika barang sudah ada ID-nya
        // $barangIdFromState = $this->state()->all()['barang-induk-dan-unit-awal-step']['created_barang_id_temp'] ?? null;
        // if($barangIdFromState) {
        //     $barangInduk = Barang::find($barangIdFromState);
        //     if($barangInduk) $existingCountForSuggestion = $barangInduk->qrCodes()->withTrashed()->count();
        // }


        for ($i = 0; $i < $this->jumlah_unit_target; $i++) {
            $nextNumber = $existingCountForSuggestion + $i + 1;
            $suggestions[$i] = strtoupper($this->kode_barang_induk) . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }
        $this->serial_numbers = $suggestions;
        session()->flash('step_success_nomor_seri', 'Saran nomor seri berhasil dibuat.');
    }

    public function stepInfo(): array
    {
        return [
            'label' => 'Input Nomor Seri',
            'icon' => 'bx bx-barcode', // Pastikan ikon ini tersedia di library ikon Anda
        ];
    }

    public function render()
    {
        return view('livewire.barang-nomor-seri-step');
    }
}
