{{-- ... bagian atas menu ... --}}
<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">

                <li class="menu-title">Navigasi Utama</li>
                {{-- Dashboard (Disesuaikan berdasarkan peran pengguna saat login) --}}
                @php
                    $dashboardRouteName = 'login'; // Default fallback
                    $user = Auth::user(); // Ambil user sekali saja
                    if ($user) {
                        if ($user->hasRole(App\Models\User::ROLE_ADMIN)) {
                            $dashboardRouteName = 'admin.dashboard';
                        } elseif ($user->hasRole(App\Models\User::ROLE_OPERATOR)) {
                            $dashboardRouteName = 'operator.dashboard';
                        } elseif ($user->hasRole(App\Models\User::ROLE_GURU)) {
                            $dashboardRouteName = 'guru.dashboard';
                        }
                    }
                @endphp
                <li class="{{ request()->routeIs($dashboardRouteName) ? 'active' : '' }}">
                    {{-- Pastikan $dashboardRouteName adalah nama rute yang valid sebelum digunakan --}}
                    <a href="{{ Route::has($dashboardRouteName) ? route($dashboardRouteName) : '#' }}">
                        <i data-feather="home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                {{-- DATA MASTER (Admin & Operator) --}}
                @if ($user && $user->hasAnyRole([App\Models\User::ROLE_ADMIN, App\Models\User::ROLE_OPERATOR]))
                    <li class="menu-title">Data Master</li>

                    @can('viewAny', App\Models\Barang::class)
                        <li class="{{ request()->routeIs('barang.*') ? 'active' : '' }}">
                            {{-- Barang Induk mengarah ke rute global 'barang.index' --}}
                            {{-- Rute 'barang.index' akan dihandle oleh controller untuk menampilkan view sesuai policy --}}
                            <a href="{{ route('barang.index') }}">
                                <i data-feather="package"></i>
                                <span>Barang Induk</span>
                            </a>
                        </li>
                    @endcan

                    @can('viewAny', App\Models\BarangQrCode::class)
                        <li class="{{ request()->routeIs('barang-qr-code.*') ? 'active' : '' }}">
                            <a href="{{ route('barang-qr-code.index') }}">
                                <i data-feather="grid"></i>
                                <span>Unit Barang (QR)</span>
                            </a>
                        </li>
                    @endcan

                    @can('viewAny', App\Models\Ruangan::class)
                        @php
                            $ruanganRouteName = '';
                            if ($user->hasRole(App\Models\User::ROLE_ADMIN)) {
                                $ruanganRouteName = 'admin.ruangan.index';
                            } elseif ($user->hasRole(App\Models\User::ROLE_OPERATOR)) {
                                // Operator mengakses rute globalnya jika ada, atau rute admin sebagai fallback jika policy mengizinkan
                                $ruanganRouteName = Route::has('operator.ruangan.index')
                                    ? 'operator.ruangan.index'
                                    : (Route::has('ruangan.index')
                                        ? 'ruangan.index'
                                        : (Route::has('admin.ruangan.index')
                                            ? 'admin.ruangan.index'
                                            : ''));
                            }
                        @endphp
                        @if (Route::has($ruanganRouteName))
                            <li
                                class="{{ Str::startsWith(request()->route()->getName(), Str::beforeLast($ruanganRouteName, '.')) ? 'mm-active' : '' }}">
                                <a href="{{ route($ruanganRouteName) }}">
                                    <i data-feather="map-pin"></i>
                                    <span>Ruangan & Lokasi</span>
                                </a>
                            </li>
                        @endif
                    @endcan

                    @if ($user->hasRole(App\Models\User::ROLE_ADMIN))
                        @can('viewAny', App\Models\KategoriBarang::class)
                            <li class="{{ request()->routeIs('admin.kategori-barang.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.kategori-barang.index') }}">
                                    <i data-feather="tag"></i>
                                    <span>Kategori Barang</span>
                                </a>
                            </li>
                        @endcan
                    @endif
                @endif

                <li class="menu-title">Aktivitas & Transaksi</li>

                {{-- Menu Peminjaman Disesuaikan --}}
                @can('viewAny', App\Models\Peminjaman::class)
                    <li
                        class="{{ Str::startsWith(request()->route()->getName(), ['admin.peminjaman.', 'operator.peminjaman.', 'guru.peminjaman.']) ? 'mm-active' : '' }}">
                        <a href="javascript: void(0);" class="has-arrow">
                            <i data-feather="share-2"></i>
                            <span>Peminjaman</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            @if ($user->hasRole(App\Models\User::ROLE_GURU))
                                @can('create', App\Models\Peminjaman::class)
                                    <li class="{{ request()->routeIs('guru.peminjaman.create') ? 'active' : '' }}">
                                        <a href="{{ route('guru.peminjaman.create') }}">Buat Pengajuan</a>
                                    </li>
                                @endcan
                                <li class="{{ request()->routeIs('guru.peminjaman.index') ? 'active' : '' }}">
                                    <a href="{{ route('guru.peminjaman.index') }}">Daftar Pengajuan Saya</a>
                                </li>
                                {{-- Contoh link terpisah untuk status tertentu jika diperlukan --}}
                                {{--
                                <li class="{{ request()->routeIs('guru.peminjaman.index') && request('status') == App\Models\Peminjaman::STATUS_SEDANG_DIPINJAM ? 'active' : '' }}">
                                    <a href="{{ route('guru.peminjaman.index', ['status' => App\Models\Peminjaman::STATUS_SEDANG_DIPINJAM]) }}">Sedang Dipinjam</a>
                                </li>
                                <li class="{{ request()->routeIs('guru.peminjaman.index') && request('status') == App\Models\Peminjaman::STATUS_SELESAI ? 'active' : '' }}">
                                    <a href="{{ route('guru.peminjaman.index', ['status' => App\Models\Peminjaman::STATUS_SELESAI]) }}">Riwayat Peminjaman</a>
                                </li>
                                --}}
                            @endif

                            @if ($user->hasAnyRole([App\Models\User::ROLE_ADMIN, App\Models\User::ROLE_OPERATOR]))
                                @php
                                    $peminjamanIndexRoute = $user->hasRole(App\Models\User::ROLE_ADMIN)
                                        ? 'admin.peminjaman.index'
                                        : 'operator.peminjaman.index';
                                @endphp
                                <li
                                    class="{{ request()->routeIs($peminjamanIndexRoute) && !request()->filled('status') && !request()->filled('status_arsip') ? 'active' : '' }}">
                                    <a href="{{ route($peminjamanIndexRoute) }}">Semua Peminjaman</a>
                                </li>
                                <li
                                    class="{{ request()->routeIs($peminjamanIndexRoute) && request('status') === App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN ? 'active' : '' }}">
                                    <a
                                        href="{{ route($peminjamanIndexRoute, ['status' => App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN]) }}">Manajemen
                                        Persetujuan</a>
                                </li>
                                <li
                                    class="{{ request()->routeIs($peminjamanIndexRoute) && request('status') === App\Models\Peminjaman::STATUS_DISETUJUI ? 'active' : '' }}">
                                    <a
                                        href="{{ route($peminjamanIndexRoute, ['status' => App\Models\Peminjaman::STATUS_DISETUJUI]) }}">Siap
                                        Diambil</a>
                                </li>
                                <li
                                    class="{{ request()->routeIs($peminjamanIndexRoute) && request('status') === App\Models\Peminjaman::STATUS_SEDANG_DIPINJAM ? 'active' : '' }}">
                                    <a
                                        href="{{ route($peminjamanIndexRoute, ['status' => App\Models\Peminjaman::STATUS_SEDANG_DIPINJAM]) }}">Sedang
                                        Dipinjam</a>
                                </li>
                                <li
                                    class="{{ request()->routeIs($peminjamanIndexRoute) && request('status_arsip') === 'arsip' ? 'active' : '' }}">
                                    <a href="{{ route($peminjamanIndexRoute, ['status_arsip' => 'arsip']) }}">Arsip
                                        Peminjaman</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endcan

                @can('viewAny', App\Models\Pemeliharaan::class)
                    @php
                        $pemeliharaanRouteName = '';
                        if ($user->hasRole(App\Models\User::ROLE_ADMIN)) {
                            $pemeliharaanRouteName = 'admin.pemeliharaan.index';
                        } elseif ($user->hasRole(App\Models\User::ROLE_OPERATOR)) {
                            $pemeliharaanRouteName = Route::has('operator.pemeliharaan.index')
                                ? 'operator.pemeliharaan.index'
                                : (Route::has('admin.pemeliharaan.index')
                                    ? 'admin.pemeliharaan.index'
                                    : '');
                        }
                    @endphp
                    @if (Route::has($pemeliharaanRouteName))
                        <li
                            class="{{ Str::startsWith(request()->route()->getName(), Str::beforeLast($pemeliharaanRouteName, '.')) ? 'mm-active' : '' }}">
                            <a href="{{ route($pemeliharaanRouteName) }}">
                                <i data-feather="tool"></i>
                                <span>Pemeliharaan</span>
                            </a>
                        </li>
                    @endif
                @endcan

                @can('viewAny', App\Models\StokOpname::class)
                    @php
                        $stokOpnameRouteName = '';
                        if ($user->hasRole(App\Models\User::ROLE_ADMIN)) {
                            $stokOpnameRouteName = 'admin.stok-opname.index';
                        } elseif ($user->hasRole(App\Models\User::ROLE_OPERATOR)) {
                            $stokOpnameRouteName = Route::has('operator.stok-opname.index')
                                ? 'operator.stok-opname.index'
                                : (Route::has('admin.stok-opname.index')
                                    ? 'admin.stok-opname.index'
                                    : '');
                        }
                    @endphp
                    @if (Route::has($stokOpnameRouteName))
                        <li
                            class="{{ Str::startsWith(request()->route()->getName(), Str::beforeLast($stokOpnameRouteName, '.')) ? 'mm-active' : '' }}">
                            <a href="{{ route($stokOpnameRouteName) }}">
                                <i data-feather="clipboard"></i>
                                <span>Stok Opname</span>
                            </a>
                        </li>
                    @endif
                @endcan

                @if ($user->hasRole(App\Models\User::ROLE_ADMIN))
                    {{-- Menu Riwayat Mutasi untuk Admin --}}
                    {{-- @can('viewAny', App\Models\MutasiBarang::class) --}}
                    {{-- <li class="{{ request()->routeIs('admin.mutasi-barang.*') ? 'active' : '' }}"> --}}
                    {{-- <a href="{{ route('admin.mutasi-barang.index') }}"> --}}
                    {{-- <i data-feather="shuffle"></i> --}}
                    {{-- <span>Riwayat Mutasi</span> --}}
                    {{-- </a> --}}
                    {{-- </li> --}}
                    {{-- @endcan --}}
                @endif

                @if ($user && $user->hasRole(App\Models\User::ROLE_ADMIN))
                    <li class="menu-title">Administrasi Sistem</li>
                    @can('viewAny', App\Models\User::class)
                        <li class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.users.index') }}">
                                <i data-feather="users"></i>
                                <span>Manajemen User</span>
                            </a>
                        </li>
                    @endcan
                    @can('viewAny', App\Models\ArsipBarang::class)
                        <li class="{{ request()->routeIs('admin.arsip-barang.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.arsip-barang.index') }}">
                                <i data-feather="archive"></i>
                                <span>Arsip Barang</span>
                            </a>
                        </li>
                    @endcan
                    @can('viewAny', App\Models\BarangStatus::class)
                        <li class="{{ request()->routeIs('admin.barang-status.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.barang-status.index') }}">
                                <i data-feather="activity"></i>
                                <span>Histori Status Barang</span>
                            </a>
                        </li>
                    @endcan
                    @if (Route::has('admin.log-aktivitas.index'))
                        @can('viewAny', App\Models\LogAktivitas::class)
                            <li class="{{ request()->routeIs('admin.log-aktivitas.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.log-aktivitas.index') }}">
                                    <i data-feather="file-text"></i>
                                    <span>Log Aktivitas Sistem</span>
                                </a>
                            </li>
                        @endcan
                    @endif
                    @can('viewAny', App\Models\RekapStok::class)
                        <li class="{{ request()->routeIs('admin.rekap-stok.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.rekap-stok.index') }}">
                                <i data-feather="bar-chart-2"></i>
                                <span>Rekap Stok</span>
                            </a>
                        </li>
                    @endcan
                    @if (Route::has('admin.pengaturan.index'))
                        {{-- @can('manage', App\Models\Pengaturan::class) --}}
                        <li class="{{ request()->routeIs('admin.pengaturan.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.pengaturan.index') }}">
                                <i data-feather="settings"></i>
                                <span>Pengaturan Sistem</span>
                            </a>
                        </li>
                        {{-- @endcan --}}
                    @endif
                @endif

                @if ($user && $user->hasAnyRole([App\Models\User::ROLE_ADMIN, App\Models\User::ROLE_OPERATOR]))
                    @if (Route::has('admin.barang-qr-code.export-pdf'))
                        <li class="menu-title">Laporan</li>
                        <li
                            class="{{ Str::startsWith(request()->route()->getName(), ['admin.laporan.', 'admin.barang-qr-code.export-pdf', 'operator.laporan.']) ? 'mm-active' : '' }}">
                            <a href="javascript: void(0);" class="has-arrow">
                                <i data-feather="printer"></i>
                                <span>Laporan Inventaris</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li>
                                    @php
                                        $exportPdfRoute = $user->hasRole(App\Models\User::ROLE_ADMIN)
                                            ? 'admin.barang-qr-code.export-pdf'
                                            : (Route::has('operator.barang-qr-code.export-pdf')
                                                ? 'operator.barang-qr-code.export-pdf'
                                                : (Route::has('barang-qr-code.export-pdf')
                                                    ? 'barang-qr-code.export-pdf'
                                                    : ''));
                                    @endphp
                                    @if (Route::has($exportPdfRoute))
                                        <a href="{{ route($exportPdfRoute, request()->except('page')) }}"
                                            target="_blank">
                                            Unduh Daftar Unit (PDF)
                                        </a>
                                    @endif
                                </li>
                                {{-- Tambahkan link laporan lain di sini --}}
                            </ul>
                        </li>
                    @endif
                @endif

                <li class="menu-title">Akun Saya</li>
                <li class="{{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                    <a href="{{ route('profile.edit') }}">
                        <i data-feather="user"></i>
                        <span>Profil Saya</span>
                    </a>
                </li>

            </ul>
        </div>
    </div>
</div>
{{-- ... bagian bawah menu ... --}}
