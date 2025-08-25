/**
 * JavaScript App - Agencia de Viajes Ecuador
 * Funcionalidades del frontend con animaciones estilo Mugen Studio
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== SCROLL PROGRESS BAR =====
    const scrollProgress = document.createElement('div');
    scrollProgress.className = 'scroll-progress';
    document.body.appendChild(scrollProgress);
    
    function updateScrollProgress() {
        const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
        scrollProgress.style.width = scrollPercent + '%';
    }
    
    window.addEventListener('scroll', updateScrollProgress);
    
    // ===== PARALLAX SCROLLING =====
    const parallaxElements = document.querySelectorAll('.parallax-element, .hero-bg');
    
    function handleParallax() {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        
        parallaxElements.forEach(element => {
            element.style.transform = `translateY(${rate}px)`;
        });
    }
    
    window.addEventListener('scroll', handleParallax);
    
    // ===== SCROLL-TRIGGERED TEXT REVEAL =====
    const revealTexts = document.querySelectorAll('.reveal-text');
    
    const textRevealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    revealTexts.forEach(text => {
        textRevealObserver.observe(text);
    });
    
    // ===== SECTION TRANSITIONS =====
    const sectionTransitions = document.querySelectorAll('.section-transition');
    
    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.1
    });
    
    sectionTransitions.forEach(section => {
        sectionObserver.observe(section);
    });
    
    // ===== MAGNETIC BUTTON EFFECT =====
    const magneticButtons = document.querySelectorAll('.magnetic, .btn');
    
    magneticButtons.forEach(button => {
        button.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            this.style.transform = `translate(${x * 0.1}px, ${y * 0.1}px)`;
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translate(0px, 0px)';
        });
    });
    
    // ===== TESTIMONIAL SLIDER =====
    function initTestimonialSlider() {
        const slider = document.querySelector('.testimonial-slider');
        if (!slider) return;
        
        const slides = slider.querySelectorAll('.testimonial-slide');
        const dots = slider.parentElement.querySelectorAll('.testimonial-dot');
        let currentSlide = 0;
        
        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });
            
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
        }
        
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentSlide = index;
                showSlide(currentSlide);
            });
        });
        
        // Auto-advance slides
        setInterval(() => {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }, 5000);
        
        // Initialize first slide
        showSlide(0);
    }
    
    initTestimonialSlider();
    
    // ===== FAQ ACCORDION =====
    const faqQuestions = document.querySelectorAll('.faq-question');
    
    faqQuestions.forEach(question => {
        question.addEventListener('click', function() {
            const isActive = this.classList.contains('active');
            
            // Close all other questions
            faqQuestions.forEach(q => {
                q.classList.remove('active');
                q.nextElementSibling.classList.remove('active');
            });
            
            // Toggle current question
            if (!isActive) {
                this.classList.add('active');
                this.nextElementSibling.classList.add('active');
            }
        });
    });
    
    // ===== ENHANCED COUNTER ANIMATION =====
    function animateCounters() {
        const counters = document.querySelectorAll('.counter');
        
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000;
            const increment = target / (duration / 16);
            let current = 0;
            
            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    counter.textContent = Math.ceil(current);
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target;
                }
            };
            
            updateCounter();
        });
    }
    
    // Trigger counters when visible
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                counterObserver.disconnect();
            }
        });
    }, { threshold: 0.5 });
    
    const counters = document.querySelectorAll('.counter');
    if (counters.length > 0) {
        counters.forEach(counter => counterObserver.observe(counter));
    }
    
    // ===== NAVBAR SCROLL EFFECT =====
    // El efecto de scroll originalmente se aplicaba a un elemento con la clase `.navbar`.
    // Tras refactorizar la estructura del encabezado, la barra de navegación ya no utiliza
    // esa clase y en su lugar se emplea `.site-header`. Para evitar errores de JavaScript
    // cuando el elemento no existe, buscamos cualquiera de las dos y aplicamos la clase
    // `.scrolled` sólo si se encuentra un elemento válido. De este modo se sigue
    // proporcionando el efecto visual sin lanzar excepciones que detendrían la ejecución
    // del resto del script.
    const navbar = document.querySelector('.navbar') || document.querySelector('.site-header');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // ===== SMOOTH SCROLL =====
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // ===== FORM VALIDATION =====
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });

    function validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            const value = field.value.trim();
            const errorDiv = field.parentNode.querySelector('.field-error');
            
            // Limpiar errores previos
            if (errorDiv) {
                errorDiv.remove();
            }
            field.classList.remove('error');
            
            // Validar campo requerido
            if (!value) {
                showFieldError(field, 'Este campo es obligatorio');
                isValid = false;
                return;
            }
            
            // Validar email
            if (field.type === 'email' && !isValidEmail(value)) {
                showFieldError(field, 'Ingrese un email válido');
                isValid = false;
                return;
            }
            
            // Validar teléfono
            if (field.type === 'tel' && !isValidPhone(value)) {
                showFieldError(field, 'Ingrese un teléfono válido');
                isValid = false;
                return;
            }
            
            // Validar número de personas
            if (field.name === 'pax' && (parseInt(value) < 1 || parseInt(value) > 20)) {
                showFieldError(field, 'Número de personas debe ser entre 1 y 20');
                isValid = false;
                return;
            }
        });
        
        return isValid;
    }
    
    function showFieldError(field, message) {
        field.classList.add('error');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = '#ef4444';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '0.25rem';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function isValidPhone(phone) {
        const re = /^[0-9\-\+\s\(\)]{8,15}$/;
        return re.test(phone);
    }

    // ===== ADMIN TABS =====
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetId = this.getAttribute('data-tab');
            
            // Remover clases activas
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Activar tab y contenido
            this.classList.add('active');
            document.getElementById(targetId).classList.add('active');
        });
    });

    // ===== SEARCH FUNCTIONALITY =====
    const searchInput = document.querySelector('#search-tours');
    const searchForm = document.querySelector('#search-form');
    
    if (searchInput && searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = `/?search=${encodeURIComponent(query)}`;
            }
        });
    }

    // ===== LOADING STATES =====
    function showLoading(element) {
        element.classList.add('loading');
        const spinner = document.createElement('div');
        spinner.className = 'spinner';
        element.appendChild(spinner);
    }
    
    function hideLoading(element) {
        element.classList.remove('loading');
        const spinner = element.querySelector('.spinner');
        if (spinner) {
            spinner.remove();
        }
    }

    // ===== MODAL FUNCTIONALITY =====
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modals = document.querySelectorAll('.modal');
    const modalCloses = document.querySelectorAll('.modal-close');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    modalCloses.forEach(close => {
        close.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        });
    });
    
    // Cerrar modal al hacer clic fuera
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // ===== GALLERY FUNCTIONALITY =====
    const galleryImages = document.querySelectorAll('.gallery-image');
    
    galleryImages.forEach(img => {
        img.addEventListener('click', function() {
            const src = this.src;
            const modal = createImageModal(src);
            document.body.appendChild(modal);
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });
    
    function createImageModal(imageSrc) {
        const modal = document.createElement('div');
        modal.className = 'modal image-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <span class="modal-close">&times;</span>
                <img src="${imageSrc}" alt="Imagen ampliada" style="max-width: 90vw; max-height: 90vh; object-fit: contain;">
            </div>
        `;
        
        // Agregar funcionalidad de cierre
        modal.querySelector('.modal-close').addEventListener('click', function() {
            modal.classList.remove('active');
            document.body.style.overflow = '';
            setTimeout(() => modal.remove(), 300);
        });
        
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = '';
                setTimeout(() => this.remove(), 300);
            }
        });
        
        return modal;
    }

    // ===== AUTO HIDE ALERTS =====
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // ===== COUNTER ANIMATION =====
    const counters = document.querySelectorAll('.counter');
    
    function animateCounters() {
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000; // 2 segundos
            const increment = target / (duration / 16); // 60fps
            let current = 0;
            
            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    counter.textContent = Math.ceil(current);
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target;
                }
            };
            
            updateCounter();
        });
    }
    
    // Observador para activar counters cuando sean visibles
    if (counters.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.disconnect(); // Solo animar una vez
                }
            });
        });
        
        counters.forEach(counter => observer.observe(counter));
    }

    // ===== LAZY LOADING IMAGES =====
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    if (lazyImages.length > 0) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.getAttribute('data-src');
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    }

    // ===== CONFIRM DIALOGS =====
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    
    confirmButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // ===== FORMAT DATES =====
    function formatDate(dateString) {
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        return new Date(dateString).toLocaleDateString('es-ES', options);
    }
    
    // Aplicar formato a fechas
    const dateElements = document.querySelectorAll('[data-date]');
    dateElements.forEach(element => {
        const dateValue = element.getAttribute('data-date');
        element.textContent = formatDate(dateValue);
    });

    // ===== PRICE FORMATTER =====
    function formatPrice(price, currency = 'USD') {
        const symbols = {
            'USD': '$',
            'EUR': '€',
            'PEN': 'S/.'
        };
        
        const symbol = symbols[currency] || currency;
        return symbol + new Intl.NumberFormat('es-ES').format(price);
    }

    // ===== MOBILE MENU =====
    // La navegación móvil se gestiona ahora en `assets/js/menu.js` a través de los
    // elementos `btnMenu` y `btnCloseSheet`. Las clases `.mobile-menu-toggle` y
    // `.nav-menu` pertenecían al sistema de navegación antiguo y podrían no existir
    // en el DOM. Para mantener compatibilidad y evitar errores (por ejemplo,
    // intentar acceder a `classList` de `null`), comprobamos que ambos elementos
    // estén presentes antes de registrar el manejador de evento. Si alguno falta,
    // simplemente no asignamos el listener y dejamos que `menu.js` controle el menú.
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            this.classList.toggle('active');
        });
    }

    // ===== INTERSECTION OBSERVER FOR ANIMATIONS =====
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    
    if (animatedElements.length > 0) {
        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    animationObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });
        
        animatedElements.forEach(element => {
            animationObserver.observe(element);
        });
    }
});

// ===== UTILITIES =====
window.ToursApp = {
    showAlert: function(message, type = 'info') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(alert, container.firstChild);
        
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    },
    
    formatPrice: function(price, currency = 'USD') {
        const symbols = {
            'USD': '$',
            'EUR': '€',
            'PEN': 'S/.'
        };
        
        const symbol = symbols[currency] || currency;
        return symbol + new Intl.NumberFormat('es-ES').format(price);
    },
    
    formatDate: function(dateString) {
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        return new Date(dateString).toLocaleDateString('es-ES', options);
    }
};
