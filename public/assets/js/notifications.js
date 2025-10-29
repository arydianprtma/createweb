// Sistem Notifikasi Realtime dengan WebSocket
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi WebSocket
    const wsProtocol = window.location.protocol === 'https:' ? 'wss://' : 'ws://';
    const wsUrl = wsProtocol + window.location.hostname + ':8080';
    let socket;
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 3;
    let wsConnectedOnce = false;
    
    function connectWebSocket() {
        socket = new WebSocket(wsUrl);
        
        socket.onopen = function() {
            wsConnectedOnce = true;
            reconnectAttempts = 0;
            console.log('WebSocket terhubung');
        };
        
        socket.onmessage = function(event) {
            const data = JSON.parse(event.data);
            
            if (data.type === 'notification') {
                // Update badge notifikasi
                updateNotificationBadge();
                
                // Tampilkan notifikasi toast
                showNotificationToast(data.message);
                
                // Jika di halaman pesan, refresh daftar pesan
                if (window.location.pathname.includes('/admin/messages')) {
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                }
            }
        };
        
        socket.onclose = function() {
            if (wsConnectedOnce && reconnectAttempts < maxReconnectAttempts) {
                reconnectAttempts++;
                console.log('WebSocket terputus. Mencoba menghubungkan kembali...');
                setTimeout(connectWebSocket, 5000);
            } else {
                console.warn('WebSocket tidak tersedia. Beralih ke polling periodik.');
                startPollingFallback();
            }
        };
        
        socket.onerror = function(error) {
            console.warn('WebSocket tidak tersedia:', error);
            // Jika gagal saat inisialisasi, langsung aktifkan polling
            if (!wsConnectedOnce) {
                startPollingFallback();
            }
        };
    }
    
    // Coba hubungkan WebSocket
    connectWebSocket();
    
    // Fallback polling jika WebSocket gagal
    let pollingIntervalId = null;
    function startPollingFallback() {
        if (pollingIntervalId) return;
        // Poll setiap 15 detik
        pollingIntervalId = setInterval(updateNotificationBadge, 15000);
    }

    // Fungsi untuk memperbarui badge notifikasi
    function updateNotificationBadge() {
        fetch('/admin/notifications/unread-count')
            .then(response => response.json())
            .then(data => {
                const notifBadge = document.getElementById('notificationBadge');
                
                if (notifBadge) {
                    if (data.count > 0) {
                        notifBadge.style.display = 'inline-block';
                        notifBadge.textContent = data.count;
                    } else {
                        notifBadge.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Fungsi untuk menampilkan notifikasi toast
    function showNotificationToast(message) {
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.innerHTML = `
            <div class="notification-toast-header">
                <strong>Notifikasi Baru</strong>
                <button type="button" class="btn-close"></button>
            </div>
            <div class="notification-toast-body">
                ${message}
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Tampilkan toast
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        // Sembunyikan toast setelah 5 detik
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 500);
        }, 5000);
        
        // Tombol tutup
        const closeBtn = toast.querySelector('.btn-close');
        closeBtn.addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 500);
        });
    }
    
    // Inisialisasi dropdown notifikasi
    const notifDropdown = document.getElementById('notification-dropdown');
    const notifToggle = document.getElementById('notification-toggle');
    
    if (notifToggle) {
        notifToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Toggle dropdown
            if (notifDropdown.classList.contains('show')) {
                notifDropdown.classList.remove('show');
            } else {
                notifDropdown.classList.add('show');
                loadNotifications();
            }
        });
    }
    
    // Klik di luar dropdown untuk menutup
    document.addEventListener('click', function(e) {
        if (notifDropdown && !notifDropdown.contains(e.target) && !notifToggle.contains(e.target)) {
            notifDropdown.classList.remove('show');
        }
    });
    
    // Fungsi untuk memuat notifikasi
    function loadNotifications() {
        fetch('/admin/notifications/unread')
            .then(response => response.json())
            .then(data => {
                const notifList = document.getElementById('notification-list');
                notifList.innerHTML = '';
                
                if (data.length === 0) {
                    notifList.innerHTML = '<div class="dropdown-item text-center">Tidak ada notifikasi baru</div>';
                    return;
                }
                
                data.forEach(notification => {
                    const item = document.createElement('a');
                    item.className = 'dropdown-item notification-item';
                    if (notification.type === 'new_message') {
                        item.href = /admin/messages/view/${notification.related_id};
                    } else if (notification.type === 'new_order') {
                        item.href = /admin/orders;
                    } else {
                        item.href = '#';
                    }
                    item.dataset.id = notification.id;
                    
                    item.innerHTML = `
                        <div class="d-flex align-items-center">
                            <div class="notification-icon bg-primary text-white">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="notification-content">
                                <p class="mb-0">${notification.message}</p>
                                <small class="text-muted">${formatDate(notification.created_at)}</small>
                            </div>
                        </div>
                    `;
                    
                    notifList.appendChild(item);
                    
                    // Tandai notifikasi sebagai dibaca saat diklik
                    item.addEventListener('click', function() {
                        markAsRead(notification.id);
                    });
                });
                
                // Tambahkan tombol "Tandai semua sebagai dibaca"
                const markAllBtn = document.createElement('div');
                markAllBtn.className = 'dropdown-item text-center mark-all-read';
                markAllBtn.textContent = 'Tandai semua sebagai dibaca';
                markAllBtn.addEventListener('click', markAllAsRead);
                notifList.appendChild(markAllBtn);
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Fungsi untuk menandai notifikasi sebagai dibaca
    function markAsRead(id) {
        fetch('/admin/notifications/mark-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: notification_id=${id}
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationBadge();
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Fungsi untuk menandai semua notifikasi sebagai dibaca
    function markAllAsRead() {
        fetch('/admin/notifications/mark-all-read', {
            method: 'POST'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationBadge();
                    notifDropdown.classList.remove('show');
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Format tanggal
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'short', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Load notifikasi saat halaman dimuat
    updateNotificationBadge();
    // Mulai polling ringan agar badge tetap ter-update meski WS belum siap
    startPollingFallback();
});