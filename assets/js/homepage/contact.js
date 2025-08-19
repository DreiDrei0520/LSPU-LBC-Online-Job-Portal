     // Initialize AOS animation
        AOS.init({
            duration: 800,
            once: true
        });

        // Navbar scroll effect
        $(window).scroll(function() {
            if ($(this).scrollTop() > 100) {
                $('.navbar').addClass('scrolled');
            } else {
                $('.navbar').removeClass('scrolled');
            }
        });

        // Back to top button
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                $('.back-to-top').addClass('active');
            } else {
                $('.back-to-top').removeClass('active');
            }
        });

        $('.back-to-top').click(function(e) {
            e.preventDefault();
            $('html, body').animate({scrollTop: 0}, '300');
        });

        // Form validation
        $('#contactForm').submit(function() {
            // Simple client-side validation
            let isValid = true;
            $(this).find('[required]').each(function() {
                if ($(this).val() === '') {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            return isValid;
        }); 