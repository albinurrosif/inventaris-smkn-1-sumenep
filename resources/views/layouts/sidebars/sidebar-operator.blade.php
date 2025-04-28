<div id="sidebar-menu">
    <ul class="metismenu list-unstyled" id="side-menu">
        <li class="menu-title">Inventaris</li>
        <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('redirect-dashboard') }}">
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
                <i data-feather="check-circle"></i>
                <span>Validasi Peminjaman</span>
            </a>
        </li>
        <li class="{{ request()->routeIs('pemeliharaan.*') ? 'active' : '' }}">
            <a href="{{ route('pemeliharaan.index') }}">
                <i data-feather="tool"></i>
                <span>Pemeliharaan</span>
            </a>
        </li>
        <li class="{{ request()->routeIs('stok-opname.*') ? 'active' : '' }}">
            <a href="{{ route('stok-opname.index') }}">
                <i data-feather="clipboard"></i>
                <span>Stok Opname</span>
            </a>
        </li>
    </ul>
</div>
