/**
 * VideoHub360 Hero Banner/Slider
 * 
 * Handles slider navigation, autoplay, and video interactions
 * 
 * @since 1.0.0
 */

(function() {
    'use strict';
    
    /**
     * Hero Banner class
     */
    class VH360Hero {
        constructor(element) {
            this.element = element;
            this.track = element.querySelector('.vh360-hero-track');
            this.slides = element.querySelectorAll('.vh360-hero-slide');
            this.prevBtn = element.querySelector('.vh360-hero-prev');
            this.nextBtn = element.querySelector('.vh360-hero-next');
            this.dots = element.querySelectorAll('.vh360-hero-dot');
            this.currentIndex = 0;
            this.autoplayTimer = null;
            this.isPaused = false;
            this.isDestroyed = false;
            
            // Get config with validation
            this.config = {
                autoplay: element.dataset.autoplay === 'true',
                delay: Math.max(1000, Math.min(30000, parseInt(element.dataset.delay, 10) || 5000)),
                pauseOnHover: element.dataset.pauseOnHover === 'true',
                transitionType: element.dataset.transitionType || 'slide'
            };
            
            this.init();
        }
        
        init() {
            if (this.isDestroyed) return;
            
            // Only setup slider if multiple slides
            if (this.slides.length <= 1) {
                this.setupVideos();
                return;
            }
            
            // Setup navigation
            this.setupNavigation();
            
            // Setup intersection observer for active slide
            this.setupObserver();
            
            // Initialize first slide for fade transition
            if (this.config.transitionType === 'fade' && this.slides.length > 0) {
                this.slides[0].classList.add('vh360-active');
            }
            
            // Setup keyboard navigation
            this.setupKeyboard();
            
            // Setup autoplay
            if (this.config.autoplay) {
                this.startAutoplay();
            }
            
            // Setup hover pause
            if (this.config.pauseOnHover) {
                this.setupHoverPause();
            }
            
            // Setup videos
            this.setupVideos();
        }
        
        setupNavigation() {
            // Previous button
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', () => this.prev());
            }
            
            // Next button
            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', () => this.next());
            }
            
            // Dots
            this.dots.forEach((dot, index) => {
                dot.addEventListener('click', () => this.goToSlide(index));
            });
        }
        
        setupObserver() {
            if (!this.track) return;
            
            const options = {
                root: this.track,
                threshold: 0.5
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const index = parseInt(entry.target.dataset.slideIndex, 10);
                        if (!isNaN(index)) {
                            this.updateActiveSlide(index);
                        }
                    }
                });
            }, options);
            
            this.slides.forEach(slide => observer.observe(slide));
            
            // Store observer for cleanup
            this.observer = observer;
        }
        
        setupKeyboard() {
            this.keydownHandler = (e) => {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.prev();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.next();
                }
            };
            
            this.element.addEventListener('keydown', this.keydownHandler);
            
            // Make container focusable
            if (!this.element.hasAttribute('tabindex')) {
                this.element.setAttribute('tabindex', '0');
            }
        }
        
        setupHoverPause() {
            this.mouseenterHandler = () => {
                this.pauseAutoplay();
            };
            
            this.mouseleaveHandler = () => {
                if (this.config.autoplay && !this.isPaused) {
                    this.startAutoplay();
                }
            };
            
            this.element.addEventListener('mouseenter', this.mouseenterHandler);
            this.element.addEventListener('mouseleave', this.mouseleaveHandler);
        }
        
        setupVideos() {
            const videos = this.element.querySelectorAll('.vh360-hero-video');
            
            if (!videos.length) return;
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const video = entry.target;
                    
                    if (entry.isIntersecting) {
                        // Video is visible, allow playback
                        if (video.hasAttribute('autoplay')) {
                            video.play().catch(() => {
                                // Autoplay failed, user interaction required
                            });
                        }
                    } else {
                        // Video is off-screen, pause it
                        video.pause();
                    }
                });
            }, { threshold: 0.1 });
            
            videos.forEach(video => {
                observer.observe(video);
                
                // Initialize muted autoplay
                if (video.hasAttribute('autoplay')) {
                    video.muted = true;
                }
            });
            
            // Store observer for cleanup
            this.videoObserver = observer;
        }
        
        prev() {
            const newIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.slides.length - 1;
            this.goToSlide(newIndex);
            this.resetAutoplay();
        }
        
        next() {
            const newIndex = this.currentIndex < this.slides.length - 1 ? this.currentIndex + 1 : 0;
            this.goToSlide(newIndex);
            this.resetAutoplay();
        }
        
        goToSlide(index) {
            if (index < 0 || index >= this.slides.length || this.isDestroyed) return;
            
            // Handle fade transition
            if (this.config.transitionType === 'fade') {
                this.slides.forEach((slide, i) => {
                    if (i === index) {
                        slide.classList.add('vh360-active');
                    } else {
                        slide.classList.remove('vh360-active');
                    }
                });
            } else {
                // Handle slide transition
                if (!this.track || !this.slides[0]) return;
                
                const slideWidth = this.slides[0].offsetWidth;
                this.track.scrollTo({
                    left: slideWidth * index,
                    behavior: 'smooth'
                });
            }
            
            this.currentIndex = index;
            this.updateActiveSlide(index);
        }
        
        updateActiveSlide(index) {
            if (this.isDestroyed) return;
            
            // Update dots
            this.dots.forEach((dot, i) => {
                if (i === index) {
                    dot.classList.add('active');
                    dot.setAttribute('aria-current', 'true');
                } else {
                    dot.classList.remove('active');
                    dot.removeAttribute('aria-current');
                }
            });
            
            this.currentIndex = index;
        }
        
        startAutoplay() {
            this.stopAutoplay();
            
            if (this.isDestroyed) return;
            
            this.autoplayTimer = setInterval(() => {
                this.next();
            }, this.config.delay);
        }
        
        stopAutoplay() {
            if (this.autoplayTimer) {
                clearInterval(this.autoplayTimer);
                this.autoplayTimer = null;
            }
        }
        
        pauseAutoplay() {
            this.isPaused = true;
            this.stopAutoplay();
        }
        
        resetAutoplay() {
            if (this.config.autoplay && !this.isPaused && !this.isDestroyed) {
                this.startAutoplay();
            }
        }
        
        /**
         * Destroy instance and clean up event listeners
         */
        destroy() {
            this.isDestroyed = true;
            this.stopAutoplay();
            
            // Remove event listeners
            if (this.keydownHandler) {
                this.element.removeEventListener('keydown', this.keydownHandler);
            }
            if (this.mouseenterHandler) {
                this.element.removeEventListener('mouseenter', this.mouseenterHandler);
            }
            if (this.mouseleaveHandler) {
                this.element.removeEventListener('mouseleave', this.mouseleaveHandler);
            }
            
            // Disconnect observers
            if (this.observer) {
                this.observer.disconnect();
            }
            if (this.videoObserver) {
                this.videoObserver.disconnect();
            }
        }
    }
    
    // Guard to ensure close/overlay/keydown handlers are only bound once
    let heroLightboxGlobalEventsBound = false;

    /**
     * Initialize image lightbox for hero banners
     */
    function initHeroImageLightbox() {
        const triggers = document.querySelectorAll('[data-vh360-hero-lightbox]');

        if (!triggers.length) {
            return;
        }

        let lightbox = document.querySelector('.vh360-hero-lightbox');

        if (!lightbox) {
            lightbox = document.createElement('div');
            lightbox.className = 'vh360-hero-lightbox';
            lightbox.setAttribute('role', 'dialog');
            lightbox.setAttribute('aria-modal', 'true');
            lightbox.innerHTML = '<img alt=""><button type="button" class="vh360-hero-lightbox-close" aria-label="Close">&times;</button>';
            document.body.appendChild(lightbox);
        }

        const image = lightbox.querySelector('img');
        const closeButton = lightbox.querySelector('.vh360-hero-lightbox-close');

        const close = () => {
            lightbox.classList.remove('is-open');
            image.src = '';
            document.body.classList.remove('vh360-hero-lightbox-open');
        };

        triggers.forEach((trigger) => {
            if (trigger.dataset.vh360HeroLightboxBound === 'true') {
                return;
            }
            trigger.dataset.vh360HeroLightboxBound = 'true';

            trigger.addEventListener('click', () => {
                const src = trigger.getAttribute('data-vh360-hero-lightbox');

                if (!src) {
                    return;
                }

                // Validate URL using URL constructor to prevent XSS via javascript: URIs
                let safeUrl;
                try {
                    const parsed = new URL(src, window.location.href);
                    if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
                        return;
                    }
                    safeUrl = parsed.href;
                } catch (e) {
                    return;
                }

                image.src = safeUrl;
                lightbox.classList.add('is-open');
                document.body.classList.add('vh360-hero-lightbox-open');
                closeButton.focus();
            });
        });

        if (!heroLightboxGlobalEventsBound) {
            heroLightboxGlobalEventsBound = true;

            closeButton.addEventListener('click', close);

            lightbox.addEventListener('click', (event) => {
                if (event.target === lightbox) {
                    close();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && lightbox.classList.contains('is-open')) {
                    close();
                }
            });
        }
    }

    /**
     * Initialize all hero banners on the page
     */
    function initHeroBanners() {
        const heroes = document.querySelectorAll('.vh360-hero-slider, .vh360-hero-single');

        heroes.forEach(element => {
            // Skip elements that already have an active instance
            if (element._vh360HeroInstance && !element._vh360HeroInstance.isDestroyed) {
                return;
            }

            if (element.querySelector('.vh360-hero-slide')) {
                element._vh360HeroInstance = new VH360Hero(element);
            }
        });
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initHeroBanners();
            initHeroImageLightbox();
        });
    } else {
        initHeroBanners();
        initHeroImageLightbox();
    }
    
    // Re-initialize on dynamic content (Elementor preview, AJAX)
    if (window.elementorFrontend && window.elementorFrontend.hooks && typeof window.elementorFrontend.hooks.addAction === 'function') {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/widget', function() {
            initHeroBanners();
            initHeroImageLightbox();
        });
    }
    
    // Expose for manual initialization
    window.VH360Hero = VH360Hero;
    window.initVH360HeroBanners = initHeroBanners;
    window.initVH360HeroImageLightbox = initHeroImageLightbox;
    
})();
