<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">

                <li class="menu-title">Inventaris</li>

                <li class="{{ request()->routeIs('redirect-dashboard') ? 'active' : '' }}">
                    <a href="{{ route('redirect-dashboard') }}">
                        <i data-feather="home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                @if (auth()->user()->role === 'Admin')
                    <li class="{{ request()->routeIs('barang.*') ? 'active' : '' }}">
                        <a href="{{ route('barang.index') }}">
                            <i data-feather="box"></i>
                            <span>Barang</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('ruangan.*') ? 'active' : '' }}">
                        <a href="{{ route('ruangan.index') }}">
                            <i data-feather="layers"></i>
                            <span>Ruangan</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->role === 'Operator')
                    <li class="{{ request()->routeIs('operator.barang.index') ? 'active' : '' }}">
                        <a href="{{ route('operator.barang.index') }}">
                            <i data-feather="box"></i>
                            <span>Barang</span>
                        </a>
                    </li>
                @endif

                <li class="menu-title">Aktivitas</li>

                @if (auth()->user()->role === 'Admin')
                    <li class="{{ request()->routeIs('admin.peminjaman.index') ? 'active' : '' }}">
                        <a href="{{ route('admin.peminjaman.index') }}">
                            <i data-feather="clipboard"></i>
                            <span>Peminjaman</span>
                        </a>
                    </li>
                @endif
                {{-- Menu Peminjaman --}}
                @if (auth()->user()->role === 'Operator')
                    <li class="{{ request()->routeIs('operator.peminjaman.index') ? 'active' : '' }}">
                        <a href="{{ route('operator.peminjaman.index') }}">
                            <i data-feather="list"></i>
                            <span>Daftar Pengajuan</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('operator.peminjaman.berlangsungOperator') ? 'active' : '' }}">
                        <a href="{{ route('operator.peminjaman.berlangsung') }}">
                            <i data-feather="activity"></i>
                            <span>Peminjaman Berlangsung</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('operator.pengembalian.index') ? 'active' : '' }}">
                        <a href="{{ route('operator.pengembalian.index') }}">
                            <i data-feather="check-square"></i>
                            <span>Verifikasi Pengembalian</span>
                        </a>
                    </li>
                @elseif (auth()->user()->role === 'Guru')
                    <li class="{{ request()->routeIs('guru.peminjaman.index') ? 'active' : '' }}">
                        <a href="{{ route('guru.peminjaman.index') }}">
                            <i data-feather="list"></i>
                            <span>Daftar Peminjaman</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('guru.peminjaman.riwayat') ? 'active' : '' }}">
                        <a href="{{ route('guru.peminjaman.riwayat') }}">
                            <i data-feather="clock"></i>
                            <span>Riwayat Peminjaman</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('guru.peminjaman.create') ? 'active' : '' }}">
                        <a href="{{ route('guru.peminjaman.create') }}">
                            <i data-feather="plus-circle"></i>
                            <span>Buat Peminjaman</span>
                        </a>
                    </li>

                    {{--  Rute peminjaman berlangsung dan pengembalian/perpanjangan per item --}}
                    {{--  <li class="{{ request()->routeIs('guru.peminjaman.berlangsung') ? 'active' : '' }}">
                         <a href="{{ route('guru.peminjaman.berlangsung') }}">
                             <i data-feather="activity"></i>
                             <span>Peminjaman Berlangsung</span>
                         </a>
                     </li>  --}}
                @endif

                @if (auth()->user()->role === 'Guru')
                    <li class="menu-title">Peminjaman</li>
                    <li class="{{ request()->routeIs('guru.peminjaman.berlangsung') ? 'active' : '' }}">
                        <a href="{{ route('guru.peminjaman.berlangsung') }}">
                            <i data-feather="activity"></i>
                            <span>Sedang Dipinjam</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->role !== 'Guru')
                    <li class="menu-title">Lainnya</li>
                    <li class="{{ request()->routeIs('pemeliharaan.*') ? 'active' : '' }}">
                        <a href="{{ route('pemeliharaan.index') }}">
                            <i data-feather="tool"></i>
                            <span>Pemeliharaan</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->role === 'Admin')
                    <li class="{{ request()->routeIs('barang-status.*') ? 'active' : '' }}">
                        <a href="{{ route('barang-status.index') }}">
                            <i data-feather="alert-triangle"></i>
                            <span>Status Barang</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->role !== 'Guru')
                    <li class="{{ request()->routeIs('stok-opname.*') ? 'active' : '' }}">
                        <a href="{{ route('stok-opname.index') }}">
                            <i data-feather="clipboard"></i>
                            <span>Stok Opname</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->role === 'Admin')
                    <li class="menu-title">Administrasi</li>

                    <li class="{{ request()->routeIs('rekap-stok.*') ? 'active' : '' }}">
                        <a href="{{ route('rekap-stok.index') }}">
                            <i data-feather="archive"></i>
                            <span>Rekap Stok</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('laporan.*') ? 'active' : '' }}">
                        <a href="{{ route('laporan.index') }}">
                            <i data-feather="file-text"></i>
                            <span>Laporan</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('pengaturan.*') ? 'active' : '' }}">
                        <a href="{{ route('pengaturan.index') }}">
                            <i data-feather="settings"></i>
                            <span>Pengaturan</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <a href="{{ route('users.index') }}">
                            <i data-feather="users"></i>
                            <span>Manajemen User</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
