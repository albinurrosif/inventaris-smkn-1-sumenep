<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">

                @php
                    // Logika ini bisa disederhanakan dengan memanggil method dari model User
                    $user = Auth::user();
                    $role_prefix = $user ? $user->getRolePrefix() : ''; // Memanggil helper, lebih ringkas
                @endphp

                <li class="menu-title">Navigasi Utama</li>

                {{-- Dashboard Link (Sudah dinamis dan benar) --}}
                <li>
                    <a href="{{ route($role_prefix . 'dashboard') }}" class="waves-effect">
                        <i data-feather="home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                {{-- GRUP DATA MASTER --}}
                @if ($user->hasAnyRole([\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_OPERATOR]))
                    <li class="menu-title">Manajemen Aset</li>

                    {{-- Menu Inventaris Barang (Dropdown) --}}
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i data-feather="archive"></i>
                            <span>Inventaris</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            @can('viewAny', \App\Models\Barang::class)
                                <li><a href="{{ route($role_prefix . 'barang.index') }}">Jenis Barang</a></li>
                            @endcan
                            @can('viewAny', \App\Models\BarangQrCode::class)
                                <li><a href="{{ route($role_prefix . 'barang-qr-code.index') }}">Unit Barang (QR)</a></li>
                            @endcan
                            @can('viewAny', \App\Models\MutasiBarang::class)
                                <li>
                                    <a href="{{ route($role_prefix . 'mutasi-barang.index') }}">
                                        Riwayat Mutasi
                                    </a>
                                </li>
                            @endcan
                            @can('viewAny', \App\Models\KategoriBarang::class)
                                <li><a href="{{ route($role_prefix . 'kategori-barang.index') }}">Kategori</a></li>
                            @endcan
                            @can('viewAny', \App\Models\Ruangan::class)
                                <li><a href="{{ route($role_prefix . 'ruangan.index') }}">Ruangan & Lokasi</a></li>
                            @endcan
                        </ul>
                    </li>
                @endif
                {{-- PENAMBAHAN MENU KATALOG BARANG (Hanya untuk Guru) --}}
                @if ($user->hasRole(\App\Models\User::ROLE_GURU))
                    <li class="menu-title">Aktivitas</li>
                    <li>
                        <a href="{{ route('guru.katalog.index') }}" class="waves-effect">
                            <i data-feather="shopping-bag"></i>
                            <span>Katalog Barang</span>
                        </a>
                    </li>
                @endif

                @if (!$user->hasRole(\App\Models\User::ROLE_GURU))
                    <li class="menu-title">Aktivitas & Transaksi</li>
                @endif
                {{-- Menu Peminjaman (Sudah dinamis dan benar) --}}
                @can('viewAny', \App\Models\Peminjaman::class)
                    <li>
                        <a href="{{ route($role_prefix . 'peminjaman.index') }}" class="waves-effect">
                            <i data-feather="share-2"></i>
                            <span>Peminjaman</span>
                        </a>
                    </li>
                @endcan

                {{-- Menu Pemeliharaan --}}
                @can('viewAny', \App\Models\Pemeliharaan::class)
                    <li>
                        <a href="{{ route($role_prefix . 'pemeliharaan.index') }}" class="waves-effect">
                            <i data-feather="tool"></i>
                            <span>Pemeliharaan</span>
                        </a>
                    </li>
                @endcan

                {{-- Menu Stok Opname --}}
                @can('viewAny', \App\Models\StokOpname::class)
                    <li>
                        <a href="{{ route($role_prefix . 'stok-opname.index') }}" class="waves-effect">
                            <i data-feather="clipboard"></i>
                            <span>Stok Opname</span>
                        </a>
                    </li>
                @endcan

                {{-- MENU LAPORAN BARU --}}
                @if ($user->hasAnyRole([\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_OPERATOR]))
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i data-feather="file-text"></i>
                            <span>Laporan</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            {{-- PERUBAHAN: Gunakan nama Gate, tanpa argumen kedua --}}
                            @can('view-laporan-inventaris')
                                <li><a href="{{ route($role_prefix . 'laporan.inventaris') }}">Laporan Inventaris</a></li>
                            @endcan
                            @can('view-laporan-peminjaman')
                                <li><a href="{{ route($role_prefix . 'laporan.peminjaman') }}">Laporan Peminjaman</a></li>
                            @endcan
                            @can('view-laporan-pemeliharaan')
                                <li><a href="{{ route($role_prefix . 'laporan.pemeliharaan') }}">Laporan Pemeliharaan</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                {{-- Menu Administrasi Sistem (Hanya Admin) --}}
                @if ($user->hasRole(\App\Models\User::ROLE_ADMIN))
                    <li class="menu-title">Administrasi Sistem</li>

                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i data-feather="settings"></i>
                            <span>Pengaturan Sistem</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            @can('viewAny', \App\Models\User::class)
                                <li><a href="{{ route('admin.users.index') }}">Manajemen Pengguna</a></li>
                            @endcan
                            @can('viewAny', \App\Models\ArsipBarang::class)
                                <li><a href="{{ route('admin.arsip-barang.index') }}">Arsip Barang</a></li>
                            @endcan
                            @can('viewAny', \App\Models\LogAktivitas::class)
                                <li><a href="{{ route('admin.log-aktivitas.index') }}">Log Aktivitas</a></li>
                            @endcan

                        </ul>
                    </li>
                @endif
                @can('view-pengaturan')
                    <li>
                        <a href="{{ route('admin.pengaturan.index') }}">
                            <i data-feather="sliders"></i>
                            <span>Pengaturan Umum</span>
                        </a>
                    </li>
                @endcan

                {{-- Menu Akun --}}
                <li class="menu-title">Akun Saya</li>
                <li>
                    <a href="{{ route('profile.edit') }}" class="waves-effect">
                        <i data-feather="user"></i>
                        <span>Profil Saya</span>
                    </a>
                </li>
                @can('view-my-activity')
                    <li>
                        <a href="{{ route('profile.activity') }}" class="waves-effect">
                            <i data-feather="list"></i>
                            <span>Aktivitas Saya</span>
                        </a>
                    </li>
                @endcan
                <li>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        class="waves-effect">
                        <i data-feather="log-out"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
