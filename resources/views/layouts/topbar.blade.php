<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex align-items-center">
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

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>
        </div>

        <div class="d-flex align-items-center">
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item" id="mode-setting-btn">
                    <i data-feather="moon" class="icon-lg layout-mode-dark"></i>
                    <i data-feather="sun" class="icon-lg layout-mode-light "></i>
                </button>
            </div>

            {{-- START: Notifikasi --}}
            <!-- Notifikasi Dropdown -->
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon position-relative me-2"
                    id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="feather feather-bell icon-lg">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    @if (Auth::check() && optional($unreadNotifications)->count() > 0)
                        <span class="badge bg-danger rounded-pill notification-badge" id="unread-notification-count">
                            {{ $unreadNotifications->count() }}
                        </span>
                    @endif
                </button>

                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                    aria-labelledby="page-header-notifications-dropdown">
                    <div class="p-3">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-0">Notifikasi</h6>
                            </div>
                            @if (Auth::check() && optional($unreadNotifications)->count() > 0)
                                <div class="col-auto">
                                    <a href="#!" class="small text-reset text-decoration-underline"
                                        onclick="event.preventDefault(); markAllNotificationsAsRead()">
                                        Tandai semua dibaca
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div data-simplebar style="max-height: 230px;" id="notification-dropdown-list">
                        @if (Auth::check() && optional($unreadNotifications)->count() > 0)
                            @foreach ($unreadNotifications->take(5) as $notification)
                                <a href="{{ $notification->data['link'] ?? '#' }}"
                                    class="text-reset notification-item d-block"
                                    data-notification-id="{{ $notification->id }}"
                                    onclick="event.preventDefault(); markNotificationAsRead('{{ $notification->id }}', '{{ $notification->data['link'] ?? '#' }}')">
                                    <div class="d-flex p-3 ">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar-xs">
                                                <span
                                                    class="avatar-title rounded-circle bg-{{ $notification->data['color'] ?? 'primary' }} d-flex align-items-center justify-content-center">
                                                    @switch($notification->data['type'] ?? '')
                                                        @case('peminjaman_finished')
                                                        @case('peminjaman_status_updated')
                                                            <i class="mdi mdi-check-circle font-size-14"> </i>
                                                        @break

                                                        @case('new_peminjaman_request')
                                                            <i class="mdi mdi-clipboard-text-outline font-size-14"> </i>
                                                        @break

                                                        @case('overdue')
                                                            <i class="mdi mdi-alert-circle font-size-14"> </i>
                                                        @break

                                                        @case('reminder')
                                                            <i class="mdi mdi-clock-alert font-size-14"> </i>
                                                        @break

                                                        @default
                                                            <i class="mdi mdi-bell-outline font-size-14"> </i>
                                                    @endswitch
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1  text-truncate">
                                                {{ $notification->data['message'] ?? 'Pesan tidak tersedia' }}</h6>
                                            <div class="font-size-13 text-muted">
                                                <p class="mb-0"><i class="mdi mdi-clock-outline"></i>
                                                    {{ $notification->created_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        @else
                            <div class="text-center p-3">
                                <p class="text-muted mb-0">Tidak ada notifikasi baru</p>
                            </div>
                        @endif
                    </div>

                    <div class="p-2 border-top d-grid">
                        <a class="btn btn-sm btn-link font-size-14 text-center"
                            href="{{ route('notifications.index') }}">
                            <i class="mdi mdi-arrow-right-circle me-1"></i> Lihat Semua
                        </a>
                    </div>
                </div>
            </div>

            {{-- END: Notifikasi --}}

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item bg-light-subtle border-start border-end"
                    id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
                    <img class="rounded-circle header-profile-user"
                        src="{{ asset('assets/images/users/avatar-1.jpg') }}" alt="User Avatar" />
                    <span class="d-none d-xl-inline-block ms-1 fw-medium">{{ Auth::user()->username }}</span>
                    {{-- Menggunakan username --}}
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                        <i class="mdi mdi-account-circle me-1"></i> Profil
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
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

@push('scripts')
    <script>
        // Fungsi untuk menandai notifikasi tunggal sebagai dibaca
        // Parameter `shouldRedirect` menentukan apakah akan langsung redirect setelah berhasil
        function markNotificationAsRead(notificationId, shouldRedirect = false) {

            $.ajax({
                url: '/notifications/' + notificationId + '/mark-as-read',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        // Update badge notifikasi
                        updateNotificationBadge(response.unread_count);

                        // Di dropdown, hapus notifikasi yang baru saja dibaca
                        if ($('#notification-dropdown-list a[onclick*="' + notificationId + '"]').length) {
                            $('#notification-dropdown-list a[onclick*="' + notificationId + '"]').closest(
                                'a.notification-item').remove();

                            // Jika tidak ada notifikasi baru di dropdown, tampilkan pesan
                            if ($('#notification-dropdown-list').children().length === 0) {
                                $('#notification-dropdown-list').html(
                                    '<div class="text-center p-3"><p class="text-muted mb-0">Tidak ada notifikasi baru.</p></div>'
                                );
                            }
                        }

                        if (shouldRedirect) {
                            // Redirect ke link yang diberikan oleh server
                            window.location.href = response.link_to_redirect;
                        } else {
                            // Jika tidak redirect (misal dari halaman daftar lengkap), update UI di sana
                            const notificationItem = $('#notification-' + notificationId);
                            if (notificationItem.length) {
                                notificationItem.removeClass('bg-light fw-bold');
                                notificationItem.find('.float-end').remove(); // Hapus tombol 'Tandai Dibaca'
                                notificationItem.find('small').each(function() {
                                    if ($(this).text().trim() === 'Belum Dibaca') {
                                        $(this).text('Dibaca sekarang');
                                        return false; // Berhenti setelah menemukan yang pertama
                                    }
                                });
                            }
                        }
                    } else {
                        // Server mengembalikan success: false (misal Unauthorized)
                        console.error('Gagal menandai notifikasi sebagai dibaca:', response.message ||
                            'Pesan tidak tersedia');
                        alert('Gagal menandai notifikasi sebagai dibaca: ' + (response.message ||
                            'Pesan tidak tersedia'));
                    }
                },
                error: function(xhr) {
                    // Error AJAX, misalnya 403, 500, atau koneksi
                    console.error('Error AJAX:', xhr.status, xhr.statusText, xhr.responseText);
                    let errorMessage = 'Terjadi kesalahan saat menandai notifikasi.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        // Ambil sebagian pesan jika terlalu panjang
                        errorMessage = xhr.responseText.substring(0, 100) + '...';
                    }
                    alert(errorMessage);
                }
            });
        }

        // Fungsi untuk menandai semua notifikasi sebagai dibaca
        function markAllNotificationsAsRead() {
            if (confirm('Apakah Anda yakin ingin menandai semua notifikasi sebagai sudah dibaca?')) {
                $.ajax({
                    url: '{{ route('notifications.mark-all-as-read') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update badge notifikasi menjadi 0
                            updateNotificationBadge(0);
                            // Hapus semua notifikasi dari dropdown dan tampilkan pesan "Tidak ada notifikasi baru"
                            $('#notification-dropdown-list').html(
                                '<div class="text-center p-3"><p class="text-muted mb-0">Tidak ada notifikasi baru.</p></div>'
                            );
                            // Di halaman daftar notifikasi, ubah semua item menjadi "sudah dibaca"
                            $('.list-group-item.bg-light').removeClass('bg-light fw-bold');
                            $('.list-group-item').find('.float-end').remove();
                            $('.list-group-item small').each(function() {
                                if ($(this).text().trim() === 'Belum Dibaca') {
                                    $(this).text('Dibaca sekarang');
                                }
                            });

                            alert(response.message ||
                                'Semua notifikasi berhasil ditandai sebagai sudah dibaca.');
                        } else {
                            console.error('Gagal menandai semua notifikasi sebagai dibaca:', response.message ||
                                'Pesan tidak tersedia');
                            alert('Gagal menandai semua notifikasi sebagai dibaca: ' + (response.message ||
                                'Pesan tidak tersedia'));
                        }
                    },
                    error: function(xhr) {
                        console.error('Error AJAX:', xhr.status, xhr.statusText, xhr.responseText);
                        let errorMessage = 'Terjadi kesalahan saat menandai semua notifikasi.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            errorMessage = xhr.responseText.substring(0, 100) + '...';
                        }
                        alert(errorMessage);
                    }
                });
            }
        }

        // Fungsi untuk memperbarui badge notifikasi di navbar
        function updateNotificationBadge(count) {
            const badge = $('#unread-notification-count'); // ID span badge
            if (count > 0) {
                badge.text(count).addClass('bg-danger').show(); // Tampilkan jika ada notif
            } else {
                badge.text('').removeClass('bg-danger').hide(); // Sembunyikan jika tidak ada notif
            }
        }

        // Inisialisasi awal badge saat halaman dimuat
        $(document).ready(function() {
            // Asumsi unreadNotifications sudah dimuat dari View Composer
            const initialCount = {{ Auth::check() ? $unreadNotifications->count() : 0 }};
            updateNotificationBadge(initialCount);
        });
    </script>
@endpush
