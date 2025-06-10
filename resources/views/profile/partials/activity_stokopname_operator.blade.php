<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ruangan</th>
                <th>Tgl. Opname</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stokOpnameTugas as $so)
                <tr>
                    <td><a href="{{ route('operator.stok-opname.show', $so->id) }}">#{{ $so->id }}</a></td>
                    <td>{{ optional($so->ruangan)->nama_ruangan }}</td>
                    <td>{{ $so->tanggal_opname->isoFormat('DD MMM YYYY') }}</td>
                    <td class="text-center"><span
                            class="badge bg-{{ $so->status == 'Selesai' ? 'success' : 'warning text-dark' }}">{{ $so->status }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Tidak ada tugas stok opname.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $stokOpnameTugas->links() }}
