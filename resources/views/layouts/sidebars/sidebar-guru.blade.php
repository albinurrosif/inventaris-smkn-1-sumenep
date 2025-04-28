<div id="sidebar-menu">
    <ul class="metismenu list-unstyled" id="side-menu">
        <li class="menu-title">Inventaris</li>
        <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('redirect-dashboard') }}">
                <i data-feather="home"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-title">Aktivitas</li>
        <li class="{{ request()->routeIs('peminjaman.*') ? 'active' : '' }}">
            <a href="{{ route('peminjaman.index') }}">
                <i data-feather="repeat"></i>
                <span>Pengajuan Peminjaman</span>
            </a>
        </li>
    </ul>
</div>
