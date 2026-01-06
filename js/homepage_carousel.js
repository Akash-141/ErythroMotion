document.addEventListener('DOMContentLoaded', function() {
    const carouselWrapper = document.querySelector('.carousel-wrapper');
    const carouselSlidesContainer = document.querySelector('.carousel-slides');
    const slides = Array.from(document.querySelectorAll('.carousel-slide'));
    const nextButton = document.querySelector('.carousel-control.next');
    const prevButton = document.querySelector('.carousel-control.prev');
    const dotsContainer = document.querySelector('.carousel-dots');

    if (!carouselWrapper || !carouselSlidesContainer || !slides.length || !nextButton || !prevButton || !dotsContainer) {
        // console.warn('Carousel elements not found for swiping animation.');
        return;
    }

    let currentIndex = 0;
    let autoPlayInterval;
    const autoPlayDelay = 3000; // 5 seconds
    const slideWidth = slides[0].getBoundingClientRect().width; // Get width of a single slide

    // Set the width of the slides container if using flex and translateX
    // This is not strictly necessary if each .carousel-slide is min-width: 100%
    // and the .carousel-wrapper has overflow: hidden.
    // carouselSlidesContainer.style.width = `${slides.length * 100}%`;


    // Create dots
    slides.forEach((_, index) => {
        const dot = document.createElement('span');
        dot.classList.add('carousel-dot');
        if (index === 0) {
            dot.classList.add('active');
        }
        dot.addEventListener('click', () => {
            goToSlide(index);
            resetAutoPlay();
        });
        dotsContainer.appendChild(dot);
    });
    const dots = Array.from(dotsContainer.children);

    function updateCarousel() {
        // Apply the transform to slide the container
        carouselSlidesContainer.style.transform = `translateX(-${currentIndex * 100}%)`;
        // The CSS transition on .carousel-slides will handle the animation.

        // Update active dot
        dots.forEach((dot, index) => {
            if (index === currentIndex) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });

        // No need to toggle 'active' class on individual slides for visibility with this method
        // as overflow:hidden on wrapper and translateX handles what's seen.
        // However, if you have other CSS tied to .active on the slide itself (e.g., for an animation within the active slide),
        // you might want to keep this part:
        slides.forEach((slide, index) => {
            if (index === currentIndex) {
                slide.classList.add('active'); // If .active class does more than just opacity
            } else {
                slide.classList.remove('active');
            }
        });
    }

    function goToSlide(index) {
        if (index < 0 || index >= slides.length) return; // Boundary check
        currentIndex = index;
        updateCarousel();
    }

    function showNextSlide() {
        currentIndex = (currentIndex + 1) % slides.length;
        updateCarousel();
    }

    function showPrevSlide() {
        currentIndex = (currentIndex - 1 + slides.length) % slides.length;
        updateCarousel();
    }

    function startAutoPlay() {
        stopAutoPlay(); // Clear any existing interval
        autoPlayInterval = setInterval(showNextSlide, autoPlayDelay);
    }

    function stopAutoPlay() {
        clearInterval(autoPlayInterval);
    }

    function resetAutoPlay() {
        stopAutoPlay();
        startAutoPlay();
    }

    // Event Listeners
    nextButton.addEventListener('click', () => {
        showNextSlide();
        resetAutoPlay();
    });

    prevButton.addEventListener('click', () => {
        showPrevSlide();
        resetAutoPlay();
    });

    carouselWrapper.addEventListener('mouseenter', stopAutoPlay);
    carouselWrapper.addEventListener('mouseleave', startAutoPlay);

    // Initial setup
    if (slides.length > 0) {
        updateCarousel(); // Set initial position
        startAutoPlay();
    }

    // Optional: Recalculate on resize if slide widths might change fluidly
    // window.addEventListener('resize', () => {
    //     slideWidth = slides[0].getBoundingClientRect().width;
    //     updateCarousel(); // Re-apply transform based on new width if needed
    // });
});