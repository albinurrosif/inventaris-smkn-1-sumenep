<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tujuan</th>
                <th>Tgl. Pengajuan</th>
                <th class="text-center">Item</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($peminjamanList ?? $peminjamanTerbaru as $p)
                <tr>
                    <td><a
                            href="{{ route(Auth::user()->getRolePrefix() . 'peminjaman.show', $p->id) }}">#{{ $p->id }}</a>
                    </td>
                    <td>{{ Str::limit($p->tujuan_peminjaman, 50) }}</td>
                    <td>{{ $p->tanggal_pengajuan->isoFormat('DD MMM YYYY') }}</td>
                    <td class="text-center">{{ $p->detail_peminjaman_count }}</td>
                    <td class="text-center"><span
                            class="badge {{ \App\Models\Peminjaman::statusColor($p->status) }}">{{ $p->status }}</span>

                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Tidak ada riwayat peminjaman.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ ($peminjamanList ?? $peminjamanTerbaru)->links() }}
