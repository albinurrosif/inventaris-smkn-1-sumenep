<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <!-- Sidebar Menu -->
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Inventaris</li>

                <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i data-feather="home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
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

                <li class="menu-title">Aktivitas</li>

                <li class="{{ request()->routeIs('peminjaman.*') ? 'active' : '' }}">
                    <a href="{{ route('peminjaman.index') }}">
                        <i data-feather="repeat"></i>
                        <span>Peminjaman</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('pemeliharaan.*') ? 'active' : '' }}">
                    <a href="{{ route('pemeliharaan.index') }}">
                        <i data-feather="tool"></i>
                        <span>Pemeliharaan</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('barang-status.*') ? 'active' : '' }}">
                    <a href="{{ route('barang-status.index') }}">
                        <i data-feather="alert-triangle"></i>
                        <span>Status Barang</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('stok-opname.*') ? 'active' : '' }}">
                    <a href="{{ route('stok-opname.index') }}">
                        <i data-feather="clipboard"></i>
                        <span>Stok Opname</span>
                    </a>
                </li>

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
            </ul>
        </div>
        <!-- End Sidebar Menu -->
    </div>
</div>
