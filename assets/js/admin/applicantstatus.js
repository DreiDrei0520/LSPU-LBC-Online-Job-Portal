        document.addEventListener('DOMContentLoaded', function () {
            // Initialize AOS
            AOS.init({
                duration: 800,
                once: true
            });

            // Show mobile toggle button only on mobile
            function checkMobileView() {
                const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
                if (window.innerWidth < 992) {
                    mobileSidebarToggle.style.display = 'flex';
                } else {
                    mobileSidebarToggle.style.display = 'none';
                }
            }

            // Initial check
            checkMobileView();

            // Check on resize
            window.addEventListener('resize', checkMobileView);

            // Tab functionality for modal
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Add active class to clicked button and corresponding content
                    button.classList.add('active');
                    const tabId = button.getAttribute('data-tab');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });

            // Show loading state on form submission
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function () {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                        submitBtn.disabled = true;
                    }
                });
            });
        });

        // Search filter function
        function filterTable(query) {
            query = query.toLowerCase();
            const rows = document.querySelectorAll('.table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }

        // Modal functions
        function closeModal() {
            document.getElementById('applicationModal').style.display = 'none';
            document.body.style.overflow = 'auto';

            // Remove the view_id parameter from URL
            const url = new URL(window.location.href);
            url.searchParams.delete('view_id');
            window.history.replaceState({}, '', url);
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('applicationModal');
            if (modal && event.target === modal) {
                closeModal();
            }
        }

        // Close modal with ESC key
        document.onkeydown = function (evt) {
            evt = evt || window.event;
            if (evt.key === "Escape") {
                closeModal();
            }
        };