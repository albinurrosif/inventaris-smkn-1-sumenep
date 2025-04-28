{{-- @switch(Auth::user()->role)
    @case('Admin')
        @include('layouts.sidebars.sidebar-admin')
    @break

    @case('Operator')
        @include('layouts.sidebars.sidebar-operator')
    @break

    @case('Guru')
        @include('layouts.sidebars.sidebar-guru')
    @break

    @default
        <p class="text-danger p-3">Role tidak dikenali</p>
@endswitch --}}

<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">

                <li class="menu-title">Inventaris</li>

                <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
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
                    <li class="{{ request()->routeIs('operator.peminjaman.daftar-dipinjam') ? 'active' : '' }}">
                        <a href="{{ route('operator.peminjaman.daftar-dipinjam') }}">
                            <i data-feather="list"></i>
                            <span>Daftar Sedang Dipinjam</span>
                        </a>
                    </li>

                    <li class="{{ request()->routeIs('operator.peminjaman.daftar-index') ? 'active' : '' }}">
                        <a href="{{ route('operator.peminjaman.index') }}">
                            <i data-feather="clipboard"></i>
                            <span>Pengajuan Peminjaman</span>
                        </a>
                    </li>

                    </li>
                    <li class="{{ request()->routeIs('operator.pengembalian.verifikasi-pengembalian') ? 'active' : '' }}">
                        <a href="{{ route('operator.pengembalian.index') }}">
                            <i data-feather="check-square"></i>
                            <span>Verifikasi Pengembalian</span>
                        </a>
                    </li>
                @elseif (auth()->user()->role === 'Guru')
                    <li class="{{ request()->routeIs('peminjaman.create') ? 'active' : '' }}">
                        <a href="{{ route('peminjaman.create') }}">
                            <i data-feather="plus-circle"></i>
                            <span>Ajukan Peminjaman</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('peminjaman.index') ? 'active' : '' }}">
                        <a href="{{ route('peminjaman.index') }}">
                            <i data-feather="clock"></i>
                            <span>Riwayat Peminjaman</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->role !== 'Guru')
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
