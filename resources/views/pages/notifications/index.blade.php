@extends('layouts.app') {{-- Sesuaikan dengan layout utama Anda --}}

@section('title', 'Notifikasi Anda')

@section('content')
    <div class="container mt-4">
        <h1 class="mb-4">Notifikasi Anda</h1>

        @if ($notifications->isEmpty())
            <div class="alert alert-info" role="alert">
                Tidak ada notifikasi yang ditemukan.
            </div>
        @else
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Daftar Notifikasi
                    <button class="btn btn-sm btn-primary" onclick="markAllNotificationsAsRead()">Tandai Semua Dibaca</button>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach ($notifications as $notification)
                        <li class="list-group-item @unless ($notification->read_at) bg-light fw-bold @endunless"
                            id="notification-{{ $notification->id }}">
                            <div class="d-flex w-100 justify-content-between">
                                <a href="{{ $notification->data['link'] ?? '#' }}" class="text-decoration-none text-dark"
                                    @unless ($notification->read_at)
                                        {{-- Di halaman daftar penuh, kita TIDAK ingin redirect otomatis, jadi pass `false` --}}
                                        onclick="markNotificationAsRead('{{ $notification->id }}', false)"
                                    @else
                                        {{-- Jika sudah dibaca, langsung ke link --}}
                                        onclick="window.location.href = '{{ $notification->data['link'] ?? '#' }}';"
                                    @endunless>
                                    <h6 class="mb-1">
                                        {{ $notification->data['message'] ?? 'Pesan notifikasi tidak tersedia' }}</h6>
                                </a>
                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                            </div>
                            <small class="text-muted">
                                @if (!$notification->read_at)
                                    Belum Dibaca
                                @else
                                    Dibaca {{ $notification->read_at->diffForHumans() }}
                                @endif
                            </small>
                            @unless ($notification->read_at)
                                <button class="btn btn-sm btn-outline-secondary float-end ms-2"
                                    onclick="markNotificationAsRead('{{ $notification->id }}', '{{ $notification->data['link'] ?? '#' }}')">
                                    Tandai Dibaca
                                </button>
                            @endunless
                        </li>
                    @endforeach
                </ul>
                <div class="card-footer">
                    {{ $notifications->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    {{-- Atau gunakan versi jQuery Anda --}}
    <script>
        // Fungsi untuk menandai notifikasi tunggal sebagai dibaca

        function markNotificationAsRead(notificationId, link = '#', event = null) {
            if (event) {
                event.preventDefault();
            }

            $.ajax({
                url: '/notifications/' + notificationId + '/mark-as-read',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        // Update UI
                        $('#notification-' + notificationId).removeClass('bg-light fw-bold');
                        $('#notification-' + notificationId).find('.btn-outline-secondary').remove();

                        // Update badge
                        updateNotificationBadge(response.unread_count);

                        // Handle redirect
                        const redirectUrl = response.redirect_url || link;
                        if (redirectUrl && redirectUrl !== '#') {
                            // Validasi URL sebelum redirect
                            if (isValidUrl(redirectUrl)) {
                                window.location.href = redirectUrl;
                            } else {
                                console.error('Invalid redirect URL:', redirectUrl);
                            }
                        }
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr.responseText);
                    // Fallback redirect jika AJAX gagal
                    if (link && link !== '#') {
                        window.location.href = link;
                    }
                }
            });
        }

        // Fungsi validasi URL sederhana
        function isValidUrl(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        }

        function updateNotificationBadge(count) {
            $('.notification-badge, #navbarDropdownNotifications .badge').each(function() {
                const badge = $(this);
                if (count > 0) {
                    badge.text(count).addClass('bg-danger').show();
                } else {
                    badge.text('').removeClass('bg-danger').hide();
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
                            $('.list-group-item.bg-light').removeClass('bg-light fw-bold');
                            $('.list-group-item').find('.float-end').remove(); // Hapus semua tombol
                            updateNotificationBadge(0); // Set badge ke 0
                            console.log('Semua notifikasi ditandai dibaca.');
                            alert('Semua notifikasi berhasil ditandai sebagai sudah dibaca.');
                        } else {
                            console.error('Gagal menandai semua notifikasi sebagai dibaca: ' + response
                                .message);
                        }
                    },
                    error: function(xhr) {
                        console.error('Error AJAX: ' + xhr.responseText);
                    }
                });
            }
        }

        // Fungsi untuk memperbarui badge notifikasi di navbar
        function updateNotificationBadge(count) {
            const badge = $('#navbarDropdownNotifications .badge');
            if (count > 0) {
                badge.text(count).addClass('bg-danger');
            } else {
                badge.text('').removeClass('bg-danger');
            }
        }
    </script>
@endpush
