<?php

namespace App\Livewire;

use Spatie\LivewireWizard\Components\StepComponent;
use App\Models\BarangQrCode;
use Illuminate\Support\Facades\Auth;
use App\Models\KategoriBarang;
use App\Models\User;
use App\Models\Ruangan;
use Illuminate\Validation\Rule;

class BarangIndukDanUnitAwalStep extends StepComponent
{
    public static function title(): string
    {
        return 'Induk dan Unit Awal';
    }

    public static function alias(): string
    {
        return 'barang-induk-dan-unit-awal-step';
    }
    // Properti untuk data Jenis Barang (Induk)
    public string $nama_barang = '';
    public string $kode_barang = '';
    public $id_kategori = ''; // Bisa string atau int, akan di-cast saat validasi jika perlu
    public ?string $merk_model = ''; // Default ke string kosong agar tidak error di view
    public ?string $ukuran = '';
    public ?string $bahan = '';
    public ?int $tahun_pembuatan = null;
    public $harga_perolehan_induk = null; // Bisa float atau string, akan divalidasi sebagai numeric
    public ?string $sumber_perolehan_induk = '';
    public bool $menggunakan_nomor_seri = true; // Default ke true

    // Properti untuk Detail Unit Awal
    public int $jumlah_unit_awal = 1;
    public $id_ruangan_awal = '';
    public $id_pemegang_personal_awal = '';
    public string $kondisi_unit_awal = BarangQrCode::KONDISI_BAIK; // Default
    public $harga_perolehan_unit_awal = null;
    public string $tanggal_perolehan_unit_awal = ''; // Default string kosong, di-mount ke tanggal hari ini
    public ?string $sumber_dana_unit_awal = '';
    public ?string $no_dokumen_unit_awal = '';
    public ?string $deskripsi_unit_awal = '';

    // Data untuk dropdown (akan di-load di mount)
    public $kategoriList = [];
    public $ruanganList = [];
    public $pemegangListAll = [];
    public $kondisiOptions = [];

    public function mount()
    {
        $this->tahun_pembuatan = now()->year;
        $this->tanggal_perolehan_unit_awal = now()->format('Y-m-d');

        $user = Auth::user();
        /** @var \App\Models\User $user */

        $this->kategoriList = KategoriBarang::orderBy('nama_kategori')->get();
        $this->ruanganList = $user && $user->hasRole(User::ROLE_OPERATOR)
            ? $user->ruanganYangDiKelola()->orderBy('nama_ruangan')->get()
            : Ruangan::orderBy('nama_ruangan')->get();
        $this->pemegangListAll = User::where('role', User::ROLE_GURU)->orderBy('username')->get();
        $this->kondisiOptions = BarangQrCode::getValidKondisi();
    }

    // Aturan validasi untuk step ini
    public function rules(): array
    {
        return [
            'nama_barang' => 'required|string|max:255',
            'kode_barang' => 'required|string|max:50|unique:barangs,kode_barang',
            'id_kategori' => 'required|exists:kategori_barangs,id',
            'merk_model' => 'nullable|string|max:255',
            'ukuran' => 'nullable|string|max:100',
            'bahan' => 'nullable|string|max:100',
            'tahun_pembuatan' => 'nullable|integer|min:1900|max:' . (now()->year + 5),
            'harga_perolehan_induk' => 'nullable|numeric|min:0',
            'sumber_perolehan_induk' => 'nullable|string|max:100',
            'menggunakan_nomor_seri' => 'required|boolean',

            'jumlah_unit_awal' => 'required|integer|min:1',
            'id_ruangan_awal' => 'required_without:id_pemegang_personal_awal|nullable|exists:ruangans,id',
            'id_pemegang_personal_awal' => 'required_without:id_ruangan_awal|nullable|exists:users,id',
            'kondisi_unit_awal' => ['required', Rule::in(BarangQrCode::getValidKondisi())],
            'harga_perolehan_unit_awal' => 'nullable|numeric|min:0',
            'tanggal_perolehan_unit_awal' => 'nullable|date|before_or_equal:today',
            'sumber_dana_unit_awal' => 'nullable|string|max:255',
            'no_dokumen_unit_awal' => 'nullable|string|max:255',
            'deskripsi_unit_awal' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'jumlah_unit_awal.min' => 'Jumlah unit awal minimal harus 1.',
            'id_ruangan_awal.required_without' => 'Ruangan awal atau Pemegang personal awal harus diisi.',
            'id_pemegang_personal_awal.required_without' => 'Pemegang personal awal atau Ruangan awal harus diisi.',
            // Tambahkan pesan kustom lain jika perlu
        ];
    }

    /**
     * Dipanggil oleh paket wizard saat pengguna menekan tombol "Next".
     * Validasi akan dijalankan secara otomatis berdasarkan metode rules().
     * Jika valid, data akan disimpan ke state wizard dan lanjut ke step berikutnya.
     */
    public function submit()
    {
        $this->validate(); // Jalankan validasi
        $this->nextStep(); // Pindah ke step berikutnya jika validasi lolos
    }

    public function stepInfo(): array
    {
        return [
            'label' => 'Info Barang & Unit Awal',
            'icon' => 'bx bx-list-ul',
        ];
    }

    public function render()
    {
        return view('livewire.barang-induk-dan-unit-awal-step');
    }
}
