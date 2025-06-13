@php
    use App\Models\Ruangan;
@endphp
<form action="{{ route('barang.store-serial', $barang->id) }}" method="POST" id="formInputSerial">
    @csrf

    <div class="alert alert-info mb-3">
        <strong>Ruangan:</strong> {{ Ruangan::find(session('target_ruangan_id'))->nama_ruangan ?? 'Belum ditentukan' }}
        |
        <strong>Jumlah Unit:</strong> {{ $barang->jumlah_barang }}
    </div>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nomor Seri</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < $barang->jumlah_barang; $i++)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <input type="text" name="serial_numbers[{{ $i }}]" class="form-control">
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    <div class="mt-3 d-flex justify-content-between">
        <a href="{{ route('barang.edit-step1', $barang->id) }}" class="btn btn-secondary">
            <i class="mdi mdi-arrow-left"></i> Kembali ke Step 1
        </a>

        <button type="button" id="btnAutoGenerateServer" class="btn btn-outline-secondary text-center">
            Auto-generate Nomor Seri
        </button>
    </div>

    <div class="mt-3 d-flex justify-content-between">
        <!-- Tombol batal (hapus barang) -->
        <a href="{{ route('barang.cancel-create', $barang->id) }}" class="btn btn-outline-danger me-2"
            onclick="return confirm('Yakin ingin membatalkan penambahan barang? Data yang sudah diinput akan dihapus.')">
            <i class="mdi mdi-close"></i> Batal
        </a>

        <button type="submit" class="btn btn-success">
            <i class="mdi mdi-check"></i> Simpan Nomor Seri
        </button>
    </div>
    </div>
</form>

@push('scripts')
    <script>
        document.getElementById('btnAutoGenerateServer').addEventListener('click', function() {
            fetch("{{ route('barang.suggest-serials', $barang->id) }}")
                .then(response => response.json())
                .then(data => {
                    const inputs = document.querySelectorAll('input[name^="serial_numbers"]');
                    inputs.forEach((input, index) => {
                        input.value = data[index] || '';
                    });
                })
                .catch(error => {
                    Swal.fire('Error', 'Gagal mengambil nomor seri dari server', 'error');
                });
        });
    </script>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formInputSerial');

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const inputs = document.querySelectorAll('input[name^="serial_numbers"]');
                let allFilled = true;

                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        allFilled = false;
                        input.classList.add('is-invalid');
                    }
                });

                if (!allFilled) {
                    Swal.fire({
                        title: 'Data belum lengkap',
                        text: 'Harap isi semua nomor seri',
                        icon: 'error',
                        position: 'top',
                        toast: true,
                        timer: 3000,
                        showConfirmButton: false,
                        background: '#f8d7da',
                        customClass: {
                            popup: 'border-danger' // Tambahkan border merah untuk emphasis
                        }
                    });
                    return;
                }

                Swal.fire({
                    title: 'Konfirmasi Nomor Seri',
                    text: 'Anda yakin ingin menyimpan nomor seri? Data tidak dapat diubah setelah disimpan',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Simpan',
                    cancelButtonText: 'Periksa Kembali',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: {
                        popup: 'sweetalert-custom-popup'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.isWizardNavigation = true;
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
