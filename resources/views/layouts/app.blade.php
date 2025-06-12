<!doctype html>
<html lang="en" data-bs-theme="{{ session('darkMode', 'light') }}">

<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Dashboard') | Web Inventaris SMKN 1 Sumenep</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Sistem Inventaris SMKN 1 Sumenep" name="description" />
    <meta content="SMKN 1 Sumenep" name="author" />
    <!-- PWA  -->
    <meta name="theme-color" content="#6777ef" />
    <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">
    <link rel="manifest" href="{{ asset('/manifest.json') }}">
    <script>
        // Immediately execute before any DOM is constructed
        (function() {
            function getThemePreference() {
                // Check localStorage first
                var localTheme = localStorage.getItem('darkMode');
                if (localTheme) return localTheme;

                // Then check cookies
                var cookies = document.cookie.split(';');
                var cookieTheme = null;
                for (var i = 0; i < cookies.length; i++) {
                    var cookie = cookies[i].trim();
                    if (cookie.indexOf('darkMode=') === 0) {
                        cookieTheme = cookie.substring('darkMode='.length, cookie.length);
                        break;
                    }
                }
                if (cookieTheme) return cookieTheme;

                // Default to light or check system preference
                return window.matchMedia('(prefers-color-scheme: light)').matches ? 'dark' : 'light';
            }

            // Apply theme immediately
            var theme = getThemePreference();
            document.documentElement.setAttribute('data-bs-theme', theme);

            // You can also set a class on the body if needed
            // document.body will be null at this point, so we use this approach:
            document.addEventListener('DOMContentLoaded', function() {
                document.body.setAttribute('data-sidebar', theme);
                document.body.setAttribute('data-layout-mode', theme);
                document.body.setAttribute('data-bs-theme', theme);
                document.body.setAttribute('data-topbar', theme);
            });
        })();
    </script>
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/Logo-SMKN_1_Sumenep-removebg-preview.png') }}">

    <!-- preloader css -->
    <link rel="stylesheet" href="{{ asset('assets/css/preloader.min.css') }}" type="text/css" />

    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />

    <!-- DataTables -->
    <link href="{{ asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}"
        rel="stylesheet" />
    <link href="{{ asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}"
        rel="stylesheet" />

    <!-- SweetAlert2 -->
    <link href="{{ asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    {{-- Choices.js CSS --}}
    <link href="{{ asset('assets/libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet"
        type="text/css" />

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* PWA Install Prompt Styles */
        .pwa-install-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 300px;
            background-color: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 1rem;
            display: none;
        }

        .pwa-install-container.show {
            display: block;
            animation: fadeInUp 0.3s ease-out;
        }

        .pwa-install-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .pwa-install-icon {
            width: 40px;
            height: 40px;
            margin-right: 0.75rem;
        }

        .pwa-install-title {
            font-weight: 600;
            margin: 0;
            color: var(--bs-heading-color);
        }

        .pwa-install-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .pwa-install-btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Style untuk SweetAlert */
        .sweetalert-custom-popup {
            font-family: 'Nunito', sans-serif;
            border-radius: 0.5rem;
        }

        .sweetalert-custom-confirm {
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
        }

        .sweetalert-custom-cancel {
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
        }

        .swal2-toast.swal2-error {
            border-left: 4px solid #dc3545 !important;
        }

        .border-danger {
            border: 1px solid #dc3545 !important;
        }

        /* Style untuk input invalid */
        .is-invalid {
            border-color: #dc3545 !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        /* Custom styles untuk Choices.js agar konsisten dengan ukuran form-select-sm */
        .choices__inner {
            min-height: calc(1.5em + .5rem + 2px);
            /* Mirip form-select-sm */
            padding: .25rem .5rem;
            font-size: .875rem;
            line-height: 1.5;
        }

        .choices[data-type*="select-one"] .choices__inner {
            padding-bottom: .25rem;
        }

        .choices__list--dropdown .choices__item--selectable,
        .choices__list--dropdown .choices__item--choice {
            font-size: .875rem;
            padding: .35rem .75rem;
            /* Sedikit padding agar nyaman */
        }

        .choices__input {
            font-size: .875rem;
            /* Samakan font size input search */
        }

        /* Pastikan label form di SweetAlert rata kiri jika diperlukan */
        .swal2-popup .swal2-html-container .form-label {
            text-align: left !important;
            display: block;
        }

        .swal2-popup .swal2-html-container .form-select-sm,
        .swal2-popup .swal2-html-container .form-control-sm {
            font-size: 0.875rem;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }
    </style>
    @stack('styles')
</head>

<body data-sidebar="{{ session('darkMode', 'light') }}" data-layout-mode="{{ session('darkMode', 'light') }}"
    data-topbar="{{ session('darkMode', 'light') }}">

    <!-- Begin page -->
    <div id="layout-wrapper">

        @include('layouts.topbar')

        @include('layouts.sidebar')

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                {{-- <h4 class="mb-sm-0">@yield('page-title', 'Dashboard')</h4> --}}

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        @yield('breadcrumb', '')
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    @yield('content')
                </div>
            </div>

            @include('layouts.footer')
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <!-- Right Sidebar -->
    @include('layouts.rightbar')
    <!-- /Right-bar -->

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <div id="pwa-install-container" class="pwa-install-container">
        <div class="pwa-install-header">
            <img src="{{ asset('logo.png') }}" alt="App Icon" class="pwa-install-icon">
            <h5 class="pwa-install-title">Instal Aplikasi</h5>
        </div>
        <p>Pasang aplikasi Inventaris SMKN 1 Sumenep di perangkat Anda untuk pengalaman yang lebih baik.</p>
        <div class="pwa-install-actions">
            <button id="pwa-install-cancel" class="btn btn-outline-secondary btn-sm pwa-install-btn">Nanti</button>
            <button id="pwa-install-accept" class="btn btn-primary btn-sm pwa-install-btn">Pasang</button>
        </div>
    </div>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <!-- pace js -->
    <script src="{{ asset('assets/libs/pace-js/pace.min.js') }}"></script>

    <!-- Plugins -->
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js') }}"></script>
    <script src="{{ asset('assets/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-world-mill-en.js') }}">
    </script>

    <!-- Init JS -->
    <script src="{{ asset('assets/js/pages/dashboard.init.js') }}"></script>

    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>

    <!-- Required datatable js -->
    <script src="{{ asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <!-- Buttons examples -->
    <script src="{{ asset('assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/libs/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('assets/libs/pdfmake/build/pdfmake.min.js') }}"></script>
    <script src="{{ asset('assets/libs/pdfmake/build/vfs_fonts.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-buttons/js/buttons.colVis.min.js') }}"></script>
    <!-- Responsive examples -->
    <script src="{{ asset('assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>

    <!-- SweetAlert2 -->
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>

    {{-- Choices.js JS --}}
    <script src="{{ asset('assets/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    {{-- TAMBAHKAN SCRIPT INI --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    {{-- <script src="{{ asset('assets/js/preloader.min.js') }}"></script> --}}
    <script>
        // Tunggu hingga preloader selesai
        document.addEventListener('preloaderComplete', function() {
            // Kode dark mode di sini
        });
    </script>

    <script src="{{ asset('/sw.js') }}"></script>
    <script>
        if ("serviceWorker" in navigator) {
            // Register a service worker hosted at the root of the
            // site using the default scope.
            navigator.serviceWorker.register("/sw.js").then(
                (registration) => {
                    console.log("Service worker registration succeeded:", registration);
                },
                (error) => {
                    console.error(`Service worker registration failed: ${error}`);
                },
            );
        } else {
            console.error("Service workers are not supported.");
        }
    </script>

    <!-- Tooltips and Popovers Initialization -->
    <script>
        $(document).ready(function() {
            // Initialize all tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Initialize all popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
            var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl)
            });

            // Initialize DataTables
            $('.datatable').DataTable({
                responsive: true
            });

            // Replace feather icons
            feather.replace();
        });
    </script>

    {{-- SweetAlert Notifications --}}
    @if (session('success') || session('error'))
        <script>
            Swal.fire({
                icon: '{{ session('success') ? 'success' : 'error' }}',
                title: '{{ session('success') ? 'Berhasil' : 'Gagal' }}',
                text: '{{ session('success') ?? session('error') }}',
                showConfirmButton: false,
                timer: 5000,
                position: 'top',
                toast: true
            });
        </script>
    @endif

    <!-- Tambahkan ke layout -->
    {{-- @if (session()->has('incomplete_barang_id') && !in_array(\Request::route()->getName(), ['barang.input-serial', 'barang.store-serial', 'barang.edit-step1', 'barang.update-step1']))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Konfirmasi jika user mencoba meninggalkan halaman
                window.addEventListener('beforeunload', function(e) {
                    // Kecualikan untuk submit form, link tertentu, dan navigasi wizard
                    if (!window.isFormSubmitting && !window.isWizardNavigation) {
                        e.preventDefault();
                        e.returnValue =
                            'Anda memiliki proses pembuatan barang yang belum selesai. Yakin ingin meninggalkan halaman?';
                    }
                });

                // Flag untuk form submission
                const forms = document.querySelectorAll('form');
                forms.forEach(form => {
                    form.addEventListener('submit', () => {
                        window.isFormSubmitting = true;
                    });
                });

                // Untuk link yang diizinkan (cancel, save, dll)
                const safeLinks = document.querySelectorAll('.safe-navigation, .wizard-navigation');
                safeLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        window.isFormSubmitting = true;
                        window.isWizardNavigation = true;
                    });
                });
            });
        </script>
    @endif

    @if (session()->has('incomplete_barang_id') && !in_array(\Request::route()->getName(), ['barang.input-serial', 'barang.store-serial', 'barang.edit-step1', 'barang.update-step1']))
        <div class="modal fade" id="modalWarnIncomplete" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Peringatan</h5>
                    </div>
                    <div class="modal-body">
                        <p>Anda memiliki proses pembuatan barang yang belum selesai.</p>
                        <p>Silahkan pilih:</p>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('barang.input-serial', session('incomplete_barang_id')) }}"
                            class="btn btn-primary safe-navigation">Lanjutkan Pengisian</a>

                        <form action="{{ route('barang.cancel-create', session('incomplete_barang_id')) }}"
                            method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger safe-navigation">Batalkan Pengisian</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Tampilkan modal secara otomatis
                const modal = new bootstrap.Modal(document.getElementById('modalWarnIncomplete'));
                modal.show();
            });
        </script>

        <!-- Dark Mode Manager (must be after app.js) -->
        <script src="{{ asset('assets/js/dark-mode-fix.js') }}"></script>
    @endif --}}
    <!-- Add this script right before your dark mode initialization -->
    <script>
        // Override problematic app.js code
        if (typeof window.appJsOverrides === 'undefined') {
            window.appJsOverrides = {
                originalInit: null
            };

            // Store original app.js init if needed
            if (typeof initApp !== 'undefined') {
                window.appJsOverrides.originalInit = initApp;
            }

            // Override problematic functions
            if (typeof l !== 'undefined') {
                l = function() {}; // Empty function to prevent errors
            }
        }
    </script>

    <script>
        // Complete dark mode implementation that overrides app.js conflicts
        document.addEventListener('DOMContentLoaded', function() {
            // ===== CONFIGURATION =====
            const STORAGE_KEY = 'darkMode';
            const COOKIE_NAME = 'darkMode';
            const COOKIE_DAYS = 30;
            const SESSION_ROUTE = '/set-dark-mode';

            // ===== HELPER FUNCTIONS =====

            // Get cookie value
            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
                return null;
            }

            // Set cookie
            function setCookie(name, value, days) {
                let expires = '';
                if (days) {
                    const date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    expires = `; expires=${date.toUTCString()}`;
                }
                document.cookie = `${name}=${value}${expires}; path=/; SameSite=Lax`;
            }

            // Apply theme to all elements
            function applyTheme(theme) {
                // Console log for debugging
                console.log('Applying theme:', theme);

                // Set on HTML element
                document.documentElement.setAttribute('data-bs-theme', theme);

                // Set on body with all required attributes
                document.body.setAttribute('data-sidebar', theme);
                document.body.setAttribute('data-layout-mode', theme);
                document.body.setAttribute('data-topbar', theme);
                document.body.setAttribute('data-bs-theme', theme);

                // Force topbar to have correct styling
                const topbar = document.getElementById('page-topbar');
                if (topbar) {
                    topbar.setAttribute('data-topbar', theme);
                }

                // Update navbar header
                const navbarHeader = document.querySelector('.navbar-header');
                if (navbarHeader) {
                    navbarHeader.setAttribute('data-topbar', theme);
                }

                // Update toggle button state
                updateToggleButtonState(theme);
            }

            // Update toggle button state
            function updateToggleButtonState(theme) {
                const modeBtn = document.getElementById('mode-setting-btn');
                if (!modeBtn) return;

                const moonIcon = modeBtn.querySelector('.layout-mode-dark');
                const sunIcon = modeBtn.querySelector('.layout-mode-light');

                if (moonIcon && sunIcon) {
                    if (theme === 'dark') {
                        moonIcon.classList.add('d-none');
                        sunIcon.classList.remove('d-none');
                    } else {
                        moonIcon.classList.remove('d-none');
                        sunIcon.classList.add('d-none');
                    }
                }
            }

            // Save theme preference
            function saveThemePreference(theme) {
                // Save to localStorage
                localStorage.setItem(STORAGE_KEY, theme);

                // Save to cookie
                setCookie(COOKIE_NAME, theme, COOKIE_DAYS);

                // Save to session via AJAX
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) {
                    fetch(SESSION_ROUTE, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                darkMode: theme
                            })
                        })
                        .then(response => response.json())
                        .then(data => console.log('Theme saved to session:', data))
                        .catch(error => console.error('Error saving theme to session:', error));
                }
            }

            // Get current theme preference (with priority)
            function getCurrentTheme() {
                // Check URL parameters (for testing)
                const urlParams = new URLSearchParams(window.location.search);
                const urlTheme = urlParams.get('theme');
                if (urlTheme === 'dark' || urlTheme === 'light') {
                    return urlTheme;
                }

                // Check localStorage
                const localTheme = localStorage.getItem(STORAGE_KEY);
                if (localTheme === 'dark' || localTheme === 'light') {
                    return localTheme;
                }

                // Check cookies
                const cookieTheme = getCookie(COOKIE_NAME);
                if (cookieTheme === 'dark' || cookieTheme === 'light') {
                    return cookieTheme;
                }

                // Default to light or check system preference
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            // ===== MAIN CODE =====

            // 1. Get the current theme
            const currentTheme = getCurrentTheme();

            // 2. Apply theme immediately
            applyTheme(currentTheme);

            // 3. Save theme preference (to ensure all storage mechanisms are in sync)
            saveThemePreference(currentTheme);

            // 4. Set up toggle button listener
            const modeSettingBtn = document.getElementById('mode-setting-btn');
            if (modeSettingBtn) {
                // Disable existing listeners by cloning and replacing the element
                const newBtn = modeSettingBtn.cloneNode(true);
                modeSettingBtn.parentNode.replaceChild(newBtn, modeSettingBtn);

                // Add our own listener
                newBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Important: stop event propagation

                    // Toggle theme
                    const oldTheme = document.documentElement.getAttribute('data-bs-theme');
                    const newTheme = (oldTheme === 'dark') ? 'light' : 'dark';

                    console.log('Toggling theme from', oldTheme, 'to', newTheme);

                    // Apply new theme
                    applyTheme(newTheme);

                    // Save preference
                    saveThemePreference(newTheme);
                });
            }

            // 5. Handle any app.js conflicts by overriding problematic functions
            try {
                // This is necessary to prevent the error in app.js
                if (typeof window.initThemeMode === 'function') {
                    window.initThemeMode = function() {
                        console.log('Original initThemeMode function overridden');
                    };
                }

                // If l function exists (the one causing the error), override it
                if (typeof window.l === 'function') {
                    window.l = function() {
                        console.log('Original l function overridden');
                        return true; // Return something to avoid further errors
                    };
                }
            } catch (e) {
                console.error('Error overriding app.js functions:', e);
            }

            // 6. Monitor for system preference changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                // Only change if user has no explicit preference
                if (!localStorage.getItem(STORAGE_KEY) && !getCookie(COOKIE_NAME)) {
                    const newTheme = e.matches ? 'dark' : 'light';
                    applyTheme(newTheme);
                    saveThemePreference(newTheme);
                }
            });

            // Debug logging
            console.log('Dark mode system initialized with theme:', currentTheme);
        });

        // Execute immediately as well (before DOM is ready)
        (function() {
            // Quick function to get current theme
            function getQuickTheme() {
                // Check localStorage first (fastest)
                const localTheme = localStorage.getItem('darkMode');
                if (localTheme === 'dark' || localTheme === 'light') {
                    return localTheme;
                }

                // Check cookies
                const cookies = document.cookie.split(';');
                for (let i = 0; i < cookies.length; i++) {
                    const cookie = cookies[i].trim();
                    if (cookie.startsWith('darkMode=')) {
                        const cookieTheme = cookie.substring('darkMode='.length);
                        if (cookieTheme === 'dark' || cookieTheme === 'light') {
                            return cookieTheme;
                        }
                    }
                }

                // Default based on system preference
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            // Set theme on html element immediately to prevent flash
            const quickTheme = getQuickTheme();
            document.documentElement.setAttribute('data-bs-theme', quickTheme);
        })();
    </script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.feather) {
                feather.replace();
            }
        });
    </script>
    {{-- <script src="{{ asset('pwa-install.js') }}"></script> --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let deferredPrompt;
            const pwaInstallContainer = document.getElementById('pwa-install-container');
            const pwaInstallAccept = document.getElementById('pwa-install-accept');
            const pwaInstallCancel = document.getElementById('pwa-install-cancel');

            // Only show the install prompt if not already installed
            if (!window.matchMedia('(display-mode: standalone)').matches) {
                window.addEventListener('beforeinstallprompt', (e) => {
                    // Prevent the mini-infobar from appearing on mobile
                    e.preventDefault();
                    // Stash the event so it can be triggered later
                    deferredPrompt = e;

                    // Show the custom install prompt
                    setTimeout(() => {
                        pwaInstallContainer.classList.add('show');
                    }, 10000); // Show after 10 seconds
                });

                pwaInstallAccept.addEventListener('click', () => {
                    // Hide the custom prompt
                    pwaInstallContainer.classList.remove('show');

                    // Show the native prompt
                    deferredPrompt.prompt();

                    // Wait for the user to respond to the prompt
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the install prompt');
                        } else {
                            console.log('User dismissed the install prompt');
                        }
                        deferredPrompt = null;
                    });
                });

                pwaInstallCancel.addEventListener('click', () => {
                    pwaInstallContainer.classList.remove('show');
                    // Hide for at least 1 week
                    localStorage.setItem('pwaInstallDismissed', Date.now());
                });

                // Check if user previously dismissed
                const lastDismissed = localStorage.getItem('pwaInstallDismissed');
                if (lastDismissed && (Date.now() - parseInt(lastDismissed)) < (7 * 24 * 60 * 60 * 1000)) {
                    // Don't show if dismissed within last week
                    return;
                }
            }
        });
    </script>

    @stack('scripts');
    @yield('scripts');

</body>

</html>
