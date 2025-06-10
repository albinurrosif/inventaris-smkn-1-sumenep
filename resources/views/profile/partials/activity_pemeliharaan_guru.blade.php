<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Aset</th>
                <th>Kerusakan</th>
                <th>Tgl. Lapor</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pemeliharaanList ?? $pemeliharaanTerbaru as $pm)
                <tr>
                    <td><a
                            href="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.show', $pm->id) }}">#{{ $pm->id }}</a>
                    </td>
                    <td>{{ optional(optional($pm->barangQrCode)->barang)->nama_barang }}</td>
                    <td>{{ Str::limit($pm->catatan_pengajuan, 50) }}</td>
                    <td>{{ $pm->tanggal_pengajuan->isoFormat('DD MMM YYYY') }}</td>
                    <td class="text-center"><span
                            class="badge {{ \App\Models\Pemeliharaan::statusColor($pm->status_pemeliharaan) }}">{{ $pm->status_pemeliharaan }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Tidak ada riwayat laporan kerusakan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ ($pemeliharaanList ?? $pemeliharaanTerbaru)->links() }}
