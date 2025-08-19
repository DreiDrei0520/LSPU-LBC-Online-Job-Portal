document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
    
    // Dropdown functionality
    const notificationBtn = document.querySelector('.notification-btn');
    const notificationMenu = document.querySelector('.notification-menu');
    const profileBtn = document.querySelector('.profile-btn');
    const profileMenu = document.querySelector('.profile-menu');
    
    if (notificationBtn && notificationMenu) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationMenu.classList.toggle('show');
            if (profileMenu) profileMenu.classList.remove('show');
        });
    }
    
    if (profileBtn && profileMenu) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            profileMenu.classList.toggle('show');
            if (notificationMenu) notificationMenu.classList.remove('show');
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        if (notificationMenu) notificationMenu.classList.remove('show');
        if (profileMenu) profileMenu.classList.remove('show');
    });
    
    // Auto-refresh notifications every 30 seconds
    const refreshNotifications = () => {
        fetch('get_notifications.php')
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(data => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                const newNotifications = doc.querySelector('.notification-menu');
                
                if (newNotifications) {
                    const currentContainer = document.querySelector('.notification-menu');
                    if (currentContainer) {
                        currentContainer.innerHTML = newNotifications.innerHTML;
                    }
                }
            })
            .catch(error => console.error('Error refreshing notifications:', error));
    };
    
    // Start auto-refresh (only if on dashboard page)
    if (window.location.pathname.endsWith('admin_dashboard.php')) {
        setInterval(refreshNotifications, 30000);
    }
    
    // Show loading state on form submission
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="loading"></span>';
                submitBtn.disabled = true;
            }
        });
    });
    
    // Close flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 300);
        }, 5000);
    });
});