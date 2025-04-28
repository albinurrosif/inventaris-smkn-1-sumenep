<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex align-items-center">
            <!-- Logo -->
            <div class="navbar-brand-box">
                {{-- Logo untuk mode terang --}}
                <a href="{{ route('redirect-dashboard') }}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ asset('assets/images/Logo-SMKN_1_Sumenep-removebg-preview.png') }}" alt="logo-sm"
                            height="45">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ asset('assets/images/Logo-SMKN_1_Sumenep-removebg-preview.png') }}" alt="logo-sm"
                            height="45">
                        <span class="logo-txt d-none d-lg-inline-block ms-2 fw-bold text-uppercase">SMKN 1
                            Sumenep</span>
                    </span>
                </a>

                {{-- Logo untuk mode gelap --}}
                <a href="{{ route('redirect-dashboard') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ asset('assets/images/Logo-SMKN_1_Sumenep-removebg-preview.png') }}" alt="logo-sm"
                            height="45">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ asset('assets/images/Logo-SMKN_1_Sumenep-removebg-preview.png') }}" alt="logo-sm"
                            height="45">
                        <span class="logo-txt d-none d-lg-inline-block ms-2 fw-bold text-uppercase">SMKN 1
                            Sumenep</span>
                    </span>
                </a>
            </div>

            <!-- Sidebar Toggle -->
            <button type="button" class="btn btn-sm px-3 font-size-16 header-item" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>
        </div>

        <div class="d-flex align-items-center">
            <!-- Dark Mode Toggle -->
            <div class="dropdown d-none d-sm-inline-block">
                <button type="button" class="btn header-item" id="mode-setting-btn">
                    <i data-feather="moon" class="icon-lg layout-mode-dark"></i>
                    <i data-feather="sun" class="icon-lg layout-mode-light"></i>
                </button>
            </div>

            <!-- Notifikasi -->
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon position-relative"
                    id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
                    <i data-feather="bell" class="icon-lg"></i>
                    <span class="badge bg-danger rounded-pill">3</span> {{-- Optional: dinamis --}}
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0">
                    <div class="p-3 border-bottom">
                        <h6 class="m-0">Notifikasi</h6>
                    </div>
                    <div data-simplebar style="max-height: 230px">
                        <a href="#" class="text-reset notification-item">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <span class="avatar-title bg-primary rounded-circle">
                                        <i class="mdi mdi-repeat"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Peminjaman Baru</h6>
                                    <div class="text-muted">
                                        <p class="mb-0"><i class="mdi mdi-clock-outline"></i> 2 menit lalu</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <!-- Tambahan notifikasi jika perlu -->
                    </div>
                    <div class="p-2 border-top text-center">
                        <a href="#" class="text-decoration-underline">Lihat Semua</a>
                    </div>
                </div>
            </div>

            <!-- User Profile -->
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item bg-light-subtle border-start border-end"
                    id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="rounded-circle header-profile-user"
                        src="{{ asset('assets/images/users/avatar-1.jpg') }}" alt="User Avatar" />
                    <span class="d-none d-xl-inline-block ms-1 fw-medium">{{ Auth::user()->name }}</span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                        <i class="mdi mdi-account-circle me-1"></i> Profil
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="{{ route('logout.show') }}">
                        <i class="mdi mdi-logout me-1"></i> Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
