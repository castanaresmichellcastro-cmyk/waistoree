/* ============================================
   WAISTORE Global JavaScript
   Mobile Navigation & Interactions
   ============================================ */

document.addEventListener('DOMContentLoaded', function () {
    initPageLoader();
    initPageTransitions();
    initMobileNav();
    initScrollAnimations();
    initHeaderScroll();

    // Add page-loaded class after a slight delay
    setTimeout(() => {
        document.body.classList.add('page-loaded');
    }, 100);
});

/* ============================================
   PAGE LOADER & TRANSITIONS
   ============================================ */
function initPageLoader() {
    // Check if we are on a login/register page or if we already have a loader
    if (document.querySelector('.page-loader')) return;

    const isLogin = document.body.classList.contains('login-page') ||
        window.location.pathname.includes('index.php') ||
        window.location.pathname.includes('register.php');

    // Create loader if it doesn't exist
    const loader = document.createElement('div');
    loader.className = 'page-loader';

    // Create logo for loader
    const logo = document.createElement('img');
    logo.src = 'WAIS_LOGO.png';
    logo.alt = 'WAISTORE Logo';
    logo.className = 'loader-logo';

    // Create spinner
    const spinnerContainer = document.createElement('div');
    spinnerContainer.className = 'loader-spinner-container';

    const spinner = document.createElement('div');
    spinner.className = 'loader-spinner';

    const spinnerInner = document.createElement('div');
    spinnerInner.className = 'loader-spinner-inner';

    spinnerContainer.appendChild(spinner);
    spinnerContainer.appendChild(spinnerInner);

    loader.appendChild(logo);
    loader.appendChild(spinnerContainer);

    // Inject at the very beginning of body
    document.body.insertBefore(loader, document.body.firstChild);

    // Add transition overlay
    const overlay = document.createElement('div');
    overlay.className = 'page-transition-overlay';
    document.body.appendChild(overlay);

    // Fade out loader on window load
    window.addEventListener('load', function () {
        setTimeout(() => {
            loader.classList.add('fade-out');
            setTimeout(() => {
                loader.remove();
            }, 600);
        }, 300); // Small delay for a smoother feel
    });

    // Fallback if load event takes too long
    setTimeout(() => {
        if (document.querySelector('.page-loader')) {
            loader.classList.add('fade-out');
            setTimeout(() => {
                loader.remove();
            }, 600);
        }
    }, 2000);
}

function initPageTransitions() {
    const links = document.querySelectorAll('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript"]):not(.no-transition):not(.action-btn):not([onclick])');

    links.forEach(link => {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (!href || href === 'logout.php') return;

            e.preventDefault();

            const overlay = document.querySelector('.page-transition-overlay');
            if (overlay) {
                overlay.classList.add('show');

                setTimeout(() => {
                    window.location.href = href;
                }, 400);
            } else {
                window.location.href = href;
            }
        });
    });

    // Handle form submissions if needed
    const forms = document.querySelectorAll('form:not(.no-transition):not([target="_blank"])');
    forms.forEach(form => {
        // We only transition on main actions, e.g. navigating to another page via GET
        if (form.method.toLowerCase() === 'get') {
            form.addEventListener('submit', function (e) {
                const overlay = document.querySelector('.page-transition-overlay');
                if (overlay) {
                    overlay.classList.add('show');
                }
            });
        }
    });
}


/* ============================================
   MOBILE NAVIGATION
   ============================================ */
function initMobileNav() {
    const header = document.querySelector('header');
    if (!header) return;

    const headerContent = header.querySelector('.header-content');
    const nav = header.querySelector('nav');

    if (!headerContent || !nav) return;

    // Don't initialize twice
    if (header.querySelector('.hamburger-menu')) return;

    // FIX: CLONE nav for mobile instead of moving it (moving removes desktop nav!)
    // The header has backdrop-filter: blur(20px) which blurs children,
    // so the mobile clone lives outside the header in document.body
    const mobileNav = nav.cloneNode(true);
    mobileNav.classList.add('mobile-slide-nav');
    document.body.appendChild(mobileNav);

    // Add a close button inside the mobile nav panel
    const closeBtn = document.createElement('button');
    closeBtn.className = 'mobile-nav-close';
    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
    closeBtn.style.cssText = 'position:absolute;top:16px;right:16px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);color:white;width:36px;height:36px;border-radius:8px;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s ease;z-index:10;';
    closeBtn.addEventListener('mouseenter', function () { closeBtn.style.background = 'rgba(239,68,68,0.8)'; });
    closeBtn.addEventListener('mouseleave', function () { closeBtn.style.background = 'rgba(255,255,255,0.1)'; });
    mobileNav.insertBefore(closeBtn, mobileNav.firstChild);

    // Create hamburger button
    const hamburger = document.createElement('button');
    hamburger.className = 'hamburger-menu';
    hamburger.setAttribute('aria-label', 'Toggle navigation');
    hamburger.innerHTML = '<span></span><span></span><span></span>';

    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'mobile-nav-overlay';
    document.body.appendChild(overlay);

    // Collect ALL action links from header (not just .user-actions)
    const mobileActions = document.createElement('div');
    mobileActions.className = 'mobile-user-actions';

    // Find all links/buttons in header that aren't nav links or logo
    const allHeaderLinks = headerContent.querySelectorAll('a, button');
    const navLinks = nav.querySelectorAll('a');
    const navHrefs = new Set();
    navLinks.forEach(l => { if (l.href) navHrefs.add(l.href); });

    allHeaderLinks.forEach(el => {
        // Skip logo, hamburger, and links already in nav
        if (el.closest('.logo') || el.closest('nav') || el.classList.contains('hamburger-menu')) return;
        if (el.closest('.logo') !== null) return;

        const isLogo = el.querySelector('img') && el.querySelector('span');
        if (isLogo) return;

        // Clone for mobile menu
        const clone = el.cloneNode(true);
        clone.removeAttribute('style');
        clone.className = '';

        // Add appropriate icon based on link text/href
        const text = (el.textContent || '').trim().toLowerCase();
        const href = (el.href || '').toLowerCase();
        let icon = 'fas fa-link';

        if (text.includes('account') || href.includes('account')) icon = 'fas fa-user';
        else if (text.includes('logout') || href.includes('logout')) icon = 'fas fa-sign-out-alt';
        else if (text.includes('notification') || href.includes('notification')) icon = 'fas fa-bell';
        else if (text.includes('setting') || href.includes('setting')) icon = 'fas fa-cog';
        else if (el.querySelector('.fa-bell, .fas.fa-bell')) icon = 'fas fa-bell';
        else if (el.querySelector('.fa-cog, .fas.fa-cog')) icon = 'fas fa-cog';

        // Only add text if it has meaningful content
        const linkText = (el.textContent || '').trim();
        if (linkText.length > 0 && linkText.length < 30) {
            clone.innerHTML = '<i class="' + icon + '"></i> ' + linkText;
        } else {
            // Icon-only button — determine label from icon
            let label = 'Link';
            if (icon.includes('bell')) label = 'Notifications';
            else if (icon.includes('cog')) label = 'Settings';
            clone.innerHTML = '<i class="' + icon + '"></i> ' + label;
        }

        mobileActions.appendChild(clone);

        // Mark original for CSS hiding on mobile
        el.classList.add('wais-header-action');
    });

    if (mobileActions.children.length > 0) {
        mobileNav.appendChild(mobileActions);
    }

    // Append hamburger as last child
    headerContent.appendChild(hamburger);

    // Toggle mobile nav (uses the cloned mobileNav, not original nav)
    function toggleNav() {
        const isOpen = mobileNav.classList.contains('mobile-open');
        mobileNav.classList.toggle('mobile-open');
        hamburger.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = isOpen ? '' : 'hidden';
    }

    function closeNav() {
        mobileNav.classList.remove('mobile-open');
        hamburger.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    hamburger.addEventListener('click', toggleNav);
    overlay.addEventListener('click', closeNav);
    closeBtn.addEventListener('click', closeNav);

    // Close nav on link click
    mobileNav.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeNav);
    });

    // Close on escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeNav();
    });

    // Close on resize to desktop
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) closeNav();
    });
}

/* ============================================
   SCROLL ANIMATIONS
   ============================================ */
function initScrollAnimations() {
    const animElements = document.querySelectorAll(
        '.stat-card, .action-card, .welcome-message, .profile-card, .account-card'
    );

    if (!animElements.length || !('IntersectionObserver' in window)) return;

    animElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 80);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    animElements.forEach(el => observer.observe(el));
}

/* ============================================
   HEADER SCROLL EFFECT
   ============================================ */
function initHeaderScroll() {
    const header = document.querySelector('header');
    if (!header) return;

    let lastScroll = 0;

    window.addEventListener('scroll', function () {
        const currentScroll = window.pageYOffset;

        if (currentScroll > 50) {
            header.style.boxShadow = '0 1px 3px rgba(0,0,0,0.15), 0 8px 32px rgba(0,0,0,0.15)';
        } else {
            header.style.boxShadow = '';
        }

        lastScroll = currentScroll;
    }, { passive: true });
}

/* ============================================
   NUMBER COUNTER ANIMATION
   ============================================ */
function animateCounters() {
    const counters = document.querySelectorAll('.stat-value');

    counters.forEach(counter => {
        const text = counter.textContent;
        const match = text.match(/[\d,]+\.?\d*/);
        if (!match) return;

        const target = parseFloat(match[0].replace(/,/g, ''));
        if (isNaN(target) || target === 0) return;

        const prefix = text.substring(0, text.indexOf(match[0]));
        const suffix = text.substring(text.indexOf(match[0]) + match[0].length);
        const decimals = match[0].includes('.') ? match[0].split('.')[1].length : 0;
        const duration = 1000;
        const start = performance.now();

        function update(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = target * eased;

            counter.textContent = prefix + current.toLocaleString(undefined, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }) + suffix;

            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }

        requestAnimationFrame(update);
    });
}

// Run counter animation when stat cards become visible
if ('IntersectionObserver' in window) {
    const statsGrid = document.querySelector('.stats-grid');
    if (statsGrid) {
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setTimeout(animateCounters, 300);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        counterObserver.observe(statsGrid);
    }
}
