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
                    <i data-feather="sun" class="icon-lg layout-mode-light "></i>
                </button>
            </div>

            <!-- Notifikasi -->
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon position-relative me-2"
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

            {{-- <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item right-bar-toggle me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="feather feather-settings icon-lg">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path
                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z">
                        </path>
                    </svg>
                </button>
            </div> --}}

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
                    {{-- <a class="dropdown-item text-danger" href="{{ route('logout.show') }}">
                        <i class="mdi mdi-logout me-1"></i> Logout
                    </a> --}}
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
