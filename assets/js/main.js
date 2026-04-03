/* assets/js/main.js */

document.addEventListener('DOMContentLoaded', () => {
    // Theme Toggle Logic
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const body = document.body;

    const savedTheme = localStorage.getItem('theme') || 'dark';
    if (savedTheme === 'light') {
        body.classList.add('light-theme');
        if (themeIcon) themeIcon.src = 'assets/dark_theme.png';
        if (themeToggle) themeToggle.title = 'Switch to Dark Theme';
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isLight = body.classList.toggle('light-theme');
            const newTheme = isLight ? 'light' : 'dark';
            localStorage.setItem('theme', newTheme);
            if (themeIcon) themeIcon.src = isLight ? 'assets/dark_theme.png' : 'assets/light_theme.png';
            themeToggle.title = isLight ? 'Switch to Dark Theme' : 'Switch to Light Theme';
        });
    }

    // Reveal animations on scroll
    const animateElements = document.querySelectorAll('.animate-fade-in');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    animateElements.forEach(el => observer.observe(el));

    // Form Validation (Example for Login/Register)
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Dynamic Price Update in Cart/Product Details
    const qtyInput = document.querySelector('input[name="quantity"]');
    const priceDisplay = document.querySelector('.price-tag');
    
    if (qtyInput && priceDisplay) {
        const basePrice = parseFloat(priceDisplay.textContent.replace('$', '').replace(',', ''));
        
        qtyInput.addEventListener('input', () => {
            const qty = parseInt(qtyInput.value) || 1;
            const newPrice = (basePrice * qty).toFixed(2);
            priceDisplay.textContent = `$${newPrice}`;
        });
    }

    // Card Formatting Logic
    const cardNumber = document.getElementById('card-number');
    const cardExpiry = document.getElementById('card-expiry');
    const cardCvc = document.getElementById('card-cvc');

    if (cardNumber) {
        cardNumber.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || '';
            e.target.value = formattedValue.substring(0, 19);
        });
    }

    if (cardExpiry) {
        cardExpiry.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                e.target.value = value.substring(0, 2) + ' / ' + value.substring(2, 4);
            } else {
                e.target.value = value;
            }
        });
    }

    // Payment Simulation
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        window.switchPayment = (type) => {
            const cardTab = document.getElementById('tab-card');
            const paypalTab = document.getElementById('tab-paypal');
            const cardSection = document.getElementById('payment-card');
            const paypalSection = document.getElementById('payment-paypal');
            const submitBtn = document.getElementById('submit-btn');

            if (type === 'card') {
                cardTab.classList.add('active', 'border-primary');
                cardTab.classList.remove('opacity-50');
                paypalTab.classList.remove('active', 'border-primary');
                paypalTab.classList.add('opacity-50');
                cardSection.classList.remove('d-none');
                paypalSection.classList.add('d-none');
                submitBtn.classList.remove('d-none');
            } else {
                paypalTab.classList.add('active', 'border-primary');
                paypalTab.classList.remove('opacity-50');
                cardTab.classList.remove('active', 'border-primary');
                cardTab.classList.add('opacity-50');
                paypalSection.classList.remove('d-none');
                cardSection.classList.add('d-none');
                submitBtn.classList.add('d-none');
            }
        };

        window.simulatePaypal = (amount) => {
            const statusDiv = document.getElementById('payment-status');
            const statusText = document.getElementById('status-text');

            statusDiv.classList.remove('d-none');
            statusText.textContent = "Negotiating Secure SSL with PayPal...";

            setTimeout(() => {
                window.location.href = `paypal_login.php?amount=${amount}`;
            }, 1200);
        };

        checkoutForm.addEventListener('submit', (e) => {
            const submitBtn = document.getElementById('submit-btn');
            const statusDiv = document.getElementById('payment-status');

            if (submitBtn && !submitBtn.dataset.processed) {
                e.preventDefault();
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50');
                statusDiv.classList.remove('d-none');

                // Simulate payment gateway connection
                setTimeout(() => {
                    submitBtn.dataset.processed = "true";
                    checkoutForm.submit();
                }, 2000);
            }
        });
    }
});
