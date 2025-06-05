<div>
    {{-- Judul Step (bisa juga di-render oleh komponen wizard utama) --}}
    {{-- <h5 class="text-center mb-2">Langkah 2: Input Nomor Seri Pabrik</h5> --}}

    @if (session()->has('step_error_nomor_seri'))
        <div class="alert alert-danger small py-2">
            {{ session('step_error_nomor_seri') }}
        </div>
    @endif
    @if (session()->has('step_success_nomor_seri'))
        <div class="alert alert-success small py-2">
            {{ session('step_success_nomor_seri') }}
        </div>
    @endif

    <div class="alert alert-info small mb-3">
        <p class="mb-1"><strong>Jenis Barang:</strong> {{ $nama_barang_induk }} ({{ $kode_barang_induk }})</p>
        <p class="mb-1"><strong>Jumlah Unit Akan Dibuat:</strong> {{ $jumlah_unit_target }} unit.</p>
        @if ($unit_details_awal_from_step1)
            <p class="mb-1"><strong>Kondisi Awal Unit:</strong> {{ $unit_details_awal_from_step1['kondisi'] ?? '-' }}
            </p>
            <p class="mb-0"><strong>Lokasi/Pemegang Awal:</strong>
                @if (!empty($unit_details_awal_from_step1['id_ruangan']))
                    {{ \App\Models\Ruangan::find($unit_details_awal_from_step1['id_ruangan'])->nama_ruangan ?? 'N/A' }}
                @elseif(!empty($unit_details_awal_from_step1['id_pemegang_personal']))
                    Dipegang oleh:
                    {{ \App\Models\User::find($unit_details_awal_from_step1['id_pemegang_personal'])->username ?? 'N/A' }}
                @else
                    Belum ditentukan
                @endif
            </p>
        @endif
        <hr class="my-2">
        <p class="mb-0">Masukkan nomor seri pabrik untuk setiap unit di bawah ini. Detail perolehan lain akan mengikuti
            data dari Step 1.</p>
    </div>

    @if ($jumlah_unit_target > 0)
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;" class="text-center">Unit Ke-</th>
                        <th>Nomor Seri Pabrik <span class="text-danger">*</span></th>
                    </tr>
                </thead>
                <tbody>
                    @for ($i = 0; $i < $jumlah_unit_target; $i++)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td>
                                <input type="text" wire:model.defer="serial_numbers.{{ $i }}"
                                    id="serial_numbers_{{ $i }}_lw"
                                    class="form-control form-control-sm @error('serial_numbers.' . $i) is-invalid @enderror"
                                    required>
                                @error('serial_numbers.' . $i)
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        <div class="mt-3 d-flex justify-content-center mb-3">
            <button type="button" wire:click="suggestSerials" class="btn btn-outline-info waves-effect btn-sm"
                wire:loading.attr="disabled">
                <span wire:loading wire:target="suggestSerials" class="spinner-border spinner-border-sm me-1"
                    role="status" aria-hidden="true"></span>
                <i wire:loading.remove wire:target="suggestSerials" class="mdi mdi-autorenew me-1"></i>
                Sarankan Nomor Seri Otomatis
            </button>
        </div>
        @error('serial_numbers')
            {{-- Error umum untuk array serial_numbers --}}
            <div class="alert alert-danger small py-1 mt-2">{{ $message }}</div>
        @enderror
    @else
        <div class="alert alert-warning text-center">
            Jumlah unit target tidak valid (0). Silakan kembali ke Step 1 untuk mengatur jumlah unit.
        </div>
    @endif

    {{-- Tombol navigasi "Previous" dan "Finish/Submit" akan disediakan oleh view wizard utama --}}
    {{-- yang memuat step ini (misalnya, resources/views/livewire/tambah-barang-wizard.blade.php) --}}
    {{-- Contoh tombol jika Anda ingin menambahkannya di sini (biasanya tidak perlu dengan Spatie Wizard) --}}
    {{-- <div class="d-flex justify-content-between mt-4">
        <button type="button" wire:click="previousStep" class="btn btn-light">Kembali ke Step 1</button>
        <button type="button" wire:click="submit" class="btn btn-success">Simpan Nomor Seri & Selesaikan</button>
    </div> --}}
</div>
