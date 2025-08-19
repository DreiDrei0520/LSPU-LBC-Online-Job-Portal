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