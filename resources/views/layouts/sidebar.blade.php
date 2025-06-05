{{-- ... bagian atas menu ... --}}
<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">

                <li class="menu-title">Navigasi Utama</li>
                {{-- Dashboard (Disesuaikan berdasarkan peran pengguna saat login) --}}
                @php
                    $dashboardRouteName = 'login'; // Default fallback
                    if (Auth::check()) {
                        $user = Auth::user();
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
                @if (Auth::check() && Auth::user()->hasAnyRole([App\Models\User::ROLE_ADMIN, App\Models\User::ROLE_OPERATOR]))
                    <li class="menu-title">Data Master</li>
                    
                    @can('viewAny', App\Models\Barang::class)
                        <li class="{{ request()->routeIs('barang.*') ? 'active' : '' }}">
                            {{-- Barang Induk mengarah ke rute global 'barang.index' --}}
                            <a href="{{ route('barang.index') }}">
                                <i data-feather="package"></i>
                                <span>Barang Induk</span>
                            </a>
                        </li>
                    @endcan

                    @can('viewAny', App\Models\BarangQrCode::class)
                        <li class="{{ request()->routeIs('barang-qr-code.*') ? 'active' : '' }}">
                            {{-- Unit Barang (QR) mengarah ke rute global 'barang-qr-code.index' --}}
                            <a href="{{ route('barang-qr-code.index') }}">
                                <i data-feather="grid"></i>
                                <span>Unit Barang (QR)</span>
                            </a>
                        </li>
                    @endcan

                    @can('viewAny', App\Models\Ruangan::class)
                        @php
                            $ruanganRouteName = '';
                            if (Auth::user()->hasRole(App\Models\User::ROLE_ADMIN)) {
                                $ruanganRouteName = 'admin.ruangan.index';
                            } elseif (Auth::user()->hasRole(App\Models\User::ROLE_OPERATOR)) {
                                // Operator mengakses rute global jika ada, atau tidak ada menu jika tidak ada rute global
                                $ruanganRouteName = Route::has('ruangan.index') ? 'ruangan.index' : ''; 
                            }
                        @endphp
                        @if(Route::has($ruanganRouteName))
                        <li class="{{ request()->routeIs($ruanganRouteName) || request()->routeIs(str_replace('.index', '.*', $ruanganRouteName)) ? 'active' : '' }}">
                            <a href="{{ route($ruanganRouteName) }}">
                                <i data-feather="map-pin"></i>
                                <span>Ruangan & Lokasi</span>
                            </a>
                        </li>
                        @endif
                    @endcan

                    @if (Auth::user()->hasRole(App\Models\User::ROLE_ADMIN))
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
                
                {{-- Menu Peminjaman --}}
                @can('viewAny', App\Models\Peminjaman::class)
                    <li class="{{ Str::startsWith(request()->route()->getName(), 'peminjaman.') || Str::startsWith(request()->route()->getName(), 'guru.peminjaman.') ? 'mm-active' : '' }}">
                        <a href="javascript: void(0);" class="has-arrow">
                            <i data-feather="share-2"></i>
                            <span>Peminjaman</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            @if (Auth::user()->hasRole(App\Models\User::ROLE_GURU))
                                @can('create', App\Models\Peminjaman::class)
                                    <li class="{{ request()->routeIs('peminjaman.create') ? 'active' : '' }}">
                                        <a href="{{ route('peminjaman.create') }}">Buat Pengajuan</a>
                                    </li>
                                @endcan
                                {{-- 'guru.peminjaman.saya' tidak ada di web.php Anda, mungkin maksudnya daftar peminjaman guru? --}}
                                {{-- Jika Anda membuatkan rute 'guru.peminjaman.saya', silakan uncomment dan sesuaikan --}}
                                {{-- <li class="{{ request()->routeIs('guru.peminjaman.saya') ? 'active' : '' }}">
                                    <a href="{{ route('guru.peminjaman.saya') }}">Pengajuan Saya</a>
                                </li> --}}
                                @if(Route::has('guru.peminjaman.berlangsung'))
                                <li class="{{ request()->routeIs('guru.peminjaman.berlangsung') ? 'active' : '' }}">
                                    <a href="{{ route('guru.peminjaman.berlangsung') }}">Sedang Dipinjam</a>
                                </li>
                                @endif
                                @if(Route::has('guru.peminjaman.riwayat'))
                                <li class="{{ request()->routeIs('guru.peminjaman.riwayat') ? 'active' : '' }}">
                                    <a href="{{ route('guru.peminjaman.riwayat') }}">Riwayat Saya</a>
                                </li>
                                @endif
                            @endif

                            @if (Auth::check() && Auth::user()->hasAnyRole([App\Models\User::ROLE_ADMIN, App\Models\User::ROLE_OPERATOR]))
                                {{-- Admin & Operator mengakses rute peminjaman global --}}
                                <li class="{{ request()->routeIs('peminjaman.index') && request()->query('status') !== 'Menunggu Persetujuan' && request()->query('status') !== 'menunggu_persetujuan' ? 'active' : '' }}">
                                    <a href="{{ route('peminjaman.index') }}">Semua Peminjaman</a>
                                </li>
                                <li class="{{ (request()->routeIs('peminjaman.index') && (request()->query('status') === 'Menunggu Persetujuan' || request()->query('status') === 'menunggu_persetujuan')) ? 'active' : '' }}">
                                    {{-- 'Menunggu Persetujuan' bisa juga dikirim sebagai 'menunggu_persetujuan' (lowercase_snake_case) tergantung implementasi filter Anda --}}
                                    <a href="{{ route('peminjaman.index', ['status' => 'Menunggu Persetujuan']) }}">Manajemen Persetujuan</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endcan

                @can('viewAny', App\Models\Pemeliharaan::class)
                     @php
                        $pemeliharaanRouteName = '';
                        if (Auth::user()->hasRole(App\Models\User::ROLE_ADMIN)) {
                            $pemeliharaanRouteName = 'admin.pemeliharaan.index';
                        } elseif (Auth::user()->hasRole(App\Models\User::ROLE_OPERATOR)) {
                            // Operator mengakses rute global jika ada
                            $pemeliharaanRouteName = Route::has('pemeliharaan.index') ? 'pemeliharaan.index' : '';
                        }
                    @endphp
                    @if(Route::has($pemeliharaanRouteName))
                    <li class="{{ request()->routeIs($pemeliharaanRouteName) || request()->routeIs(str_replace('.index', '.*', $pemeliharaanRouteName)) ? 'active' : '' }}">
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
                        if (Auth::user()->hasRole(App\Models\User::ROLE_ADMIN)) {
                            $stokOpnameRouteName = 'admin.stok-opname.index';
                        } elseif (Auth::user()->hasRole(App\Models\User::ROLE_OPERATOR)) {
                            // Operator mengakses rute global jika ada
                            $stokOpnameRouteName = Route::has('stok-opname.index') ? 'stok-opname.index' : '';
                        }
                    @endphp
                    @if(Route::has($stokOpnameRouteName))
                    <li class="{{ request()->routeIs($stokOpnameRouteName) || request()->routeIs(str_replace('.index', '.*', $stokOpnameRouteName)) ? 'active' : '' }}">
                        <a href="{{ route($stokOpnameRouteName) }}">
                            <i data-feather="clipboard"></i>
                            <span>Stok Opname</span>
                        </a>
                    </li>
                    @endif
                @endcan
                
                @if (Auth::user()->hasRole(App\Models\User::ROLE_ADMIN))
                    {{-- @can('viewAny', App\Models\MutasiBarang::class) --}} {{-- Pastikan model & policy MutasiBarang ada --}}
                        {{-- <li class="{{ request()->routeIs('admin.mutasi-barang.*') ? 'active' : '' }}"> --}}
                            {{-- <a href="{{ route('admin.mutasi-barang.index') }}"> --}} {{-- Pastikan rute admin.mutasi-barang.index ada --}}
                                {{-- <i data-feather="shuffle"></i> --}}
                                {{-- <span>Riwayat Mutasi</span> --}}
                            {{-- </a> --}}
                        {{-- </li> --}}
                    {{-- @endcan --}}
                @endif


                @if (Auth::check() && Auth::user()->hasRole(App\Models\User::ROLE_ADMIN))
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
                                <span>Arsip & Penghapusan</span>
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
                     @if(Route::has('admin.log-aktivitas.index')) {{-- Cek apakah rute Log Aktivitas ada --}}
                        @can('viewAny', App\Models\LogAktivitas::class) {{-- Ganti dengan Model LogAktivitas jika ada --}}
                            <li class="{{ request()->routeIs('admin.log-aktivitas.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.log-aktivitas.index') }}">
                                    <i data-feather="file-text"></i>
                                    <span>Log Aktivitas Sistem</span>
                                </a>
                            </li>
                        @endcan
                    @endif
                    @can('viewAny', App\Models\RekapStok::class) {{-- Pastikan Model RekapStok ada --}}
                        <li class="{{ request()->routeIs('admin.rekap-stok.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.rekap-stok.index') }}">
                                <i data-feather="bar-chart-2"></i>
                                <span>Rekap Stok</span>
                            </a>
                        </li>
                    @endcan
                    @if(Route::has('admin.pengaturan.index')) {{-- Cek apakah rute Pengaturan ada --}}
                        {{-- @can('manage', App\Models\Pengaturan::class) --}} {{-- Ganti dengan Model Pengaturan & Policy jika ada --}}
                        <li class="{{ request()->routeIs('admin.pengaturan.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.pengaturan.index') }}">
                                <i data-feather="settings"></i>
                                <span>Pengaturan Sistem</span>
                            </a>
                        </li>
                        {{-- @endcan --}}
                    @endif
                @endif

                @if (Auth::check() && Auth::user()->hasAnyRole([App\Models\User::ROLE_ADMIN, App\Models\User::ROLE_OPERATOR]))
                    @if(Route::has('admin.barang-qr-code.export-pdf')) {{-- Asumsi laporan hanya diakses admin, atau sesuaikan --}}
                    <li class="menu-title">Laporan</li>
                    <li class="{{ Str::startsWith(request()->route()->getName(), ['admin.laporan.', 'admin.barang-qr-code.export-pdf']) ? 'mm-active' : '' }}">
                        <a href="javascript: void(0);" class="has-arrow">
                            <i data-feather="printer"></i>
                            <span>Laporan Inventaris</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <li>
                                <a href="{{ route('admin.barang-qr-code.export-pdf', request()->except('page')) }}" target="_blank">
                                    Unduh Daftar Unit (PDF)
                                </a>
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