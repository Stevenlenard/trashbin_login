// ============================================
// SMART TRASHBIN - REGISTRATION PAGE SCRIPTS
// With Header & Footer Scripts from Login Page
// ============================================

(function() {
  'use strict';

  // ============================================
  // SCROLL PROGRESS INDICATOR
  // ============================================
  function initScrollProgress() {
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    document.body.appendChild(progressBar);

    function updateProgress() {
      const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
      const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      const scrolled = (winScroll / height) * 100;
      progressBar.style.width = scrolled + '%';
    }

    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
  }

  // ============================================
  // HEADER SCROLL EFFECT
  // ============================================
  function initHeaderScroll() {
    const header = document.querySelector('.header');
    if (!header) return;
    
    let lastScroll = 0;

    function handleScroll() {
      const currentScroll = window.pageYOffset;

      if (currentScroll > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }

      lastScroll = currentScroll;
    }

    window.addEventListener('scroll', handleScroll, { passive: true });
  }

  // ============================================
  // PASSWORD VISIBILITY TOGGLE
  // ============================================
 // ============================================
// PASSWORD VISIBILITY TOGGLE (same as login)
// ============================================
function initPasswordToggle() {
  const toggleButtons = document.querySelectorAll('.toggle-password');

  toggleButtons.forEach(toggle => {
    const input = toggle.parentElement.querySelector('input');
    toggle.addEventListener('click', (e) => {
      e.preventDefault();
      const icon = toggle.querySelector('i');

      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    });
  });
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
  initPasswordToggle();
});


  // ============================================
  // FORM VALIDATION & SUBMISSION
  // ============================================
  function initFormValidation() {
    const registrationForm = document.getElementById('registrationForm');
    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const emailInput = document.getElementById('regEmail');
    const phoneInput = document.getElementById('phone');
    const passwordInput = document.getElementById('regPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    if (!registrationForm) return;

    // Real-time validation for first name
    if (firstNameInput) {
      firstNameInput.addEventListener('blur', () => {
        validateFirstName(firstNameInput);
      });
      firstNameInput.addEventListener('input', () => {
        clearError('firstNameError', firstNameInput);
      });
    }

    // Real-time validation for last name
    if (lastNameInput) {
      lastNameInput.addEventListener('blur', () => {
        validateLastName(lastNameInput);
      });
      lastNameInput.addEventListener('input', () => {
        clearError('lastNameError', lastNameInput);
      });
    }

    // Real-time validation for email
    if (emailInput) {
      emailInput.addEventListener('blur', () => {
        validateEmail(emailInput);
      });
      emailInput.addEventListener('input', () => {
        clearError('emailError', emailInput);
      });
    }

    // Real-time validation for phone
    if (phoneInput) {
      phoneInput.addEventListener('blur', () => {
        validatePhone(phoneInput);
      });
      phoneInput.addEventListener('input', () => {
        clearError('phoneError', phoneInput);
        // Only allow numbers
        phoneInput.value = phoneInput.value.replace(/\D/g, '');
      });
    }

    // Real-time validation for password
    if (passwordInput) {
      passwordInput.addEventListener('blur', () => {
        validatePassword(passwordInput);
      });
      passwordInput.addEventListener('input', () => {
        clearError('passwordError', passwordInput);
      });
    }

    // Real-time validation for confirm password
    if (confirmPasswordInput) {
      confirmPasswordInput.addEventListener('blur', () => {
        validateConfirmPassword(passwordInput, confirmPasswordInput);
      });
      confirmPasswordInput.addEventListener('input', () => {
        clearError('confirmPasswordError', confirmPasswordInput);
      });
    }

    // Form submission
    registrationForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const firstName = firstNameInput.value.trim();
      const lastName = lastNameInput.value.trim();
      const email = emailInput.value.trim();
      const phone = phoneInput.value.trim();
      const password = passwordInput.value;
      const confirmPassword = confirmPasswordInput.value;
      const termsCheckbox = registrationForm.querySelector('input[name="terms"]');

      // Validate all inputs
      const firstNameValid = validateFirstName(firstNameInput);
      const lastNameValid = validateLastName(lastNameInput);
      const emailValid = validateEmail(emailInput);
      const phoneValid = validatePhone(phoneInput);
      const passwordValid = validatePassword(passwordInput);
      const confirmPasswordValid = validateConfirmPassword(passwordInput, confirmPasswordInput);

      if (!firstNameValid || !lastNameValid || !emailValid || !phoneValid || !passwordValid || !confirmPasswordValid) {
        showNotification('Please fill in all fields correctly', 'error');
        return;
      }

      if (!termsCheckbox.checked) {
        showNotification('Please agree to the Terms of Service and Privacy Policy', 'error');
        return;
      }

      // Here you would normally send the data to your server
      showNotification('Account created successfully! Redirecting to login...', 'success');

      // Simulate redirect after 1.5 seconds
      setTimeout(() => {
        window.location.href = 'user-login.php';
      }, 1500);
    });
  }

  // Validation functions
  function validateFirstName(input) {
    const firstName = input.value.trim();
    const error = document.getElementById('firstNameError');
    
    if (!firstName) {
      setInputError(input, error, 'First name is required');
      return false;
    } else if (!/^[a-zA-Z\s]+$/.test(firstName)) {
      setInputError(input, error, 'First name can only contain letters');
      return false;
    } else {
      setInputSuccess(input, error);
      return true;
    }
  }

  function validateLastName(input) {
    const lastName = input.value.trim();
    const error = document.getElementById('lastNameError');
    
    if (!lastName) {
      setInputError(input, error, 'Last name is required');
      return false;
    } else if (!/^[a-zA-Z\s]+$/.test(lastName)) {
      setInputError(input, error, 'Last name can only contain letters');
      return false;
    } else {
      setInputSuccess(input, error);
      return true;
    }
  }

  function validateEmail(input) {
    const email = input.value.trim();
    const error = document.getElementById('emailError');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!email) {
      setInputError(input, error, 'Email is required');
      return false;
    } else if (!emailRegex.test(email)) {
      setInputError(input, error, 'Please enter a valid email address');
      return false;
    } else {
      setInputSuccess(input, error);
      return true;
    }
  }

  function validatePhone(input) {
    const phone = input.value.trim();
    const error = document.getElementById('phoneError');
    const cleanPhone = phone.replace(/\D/g, '');
    
    if (!phone) {
      setInputError(input, error, 'Phone number is required');
      return false;
    } else if (cleanPhone.length !== 11) {
      setInputError(input, error, 'Phone number must be exactly 11 digits');
      return false;
    } else {
      setInputSuccess(input, error);
      return true;
    }
  }

  function validatePassword(input) {
    const password = input.value;
    const error = document.getElementById('passwordError');
    
    if (!password) {
      setInputError(input, error, 'Password is required');
      return false;
    } else if (password.length < 6) {
      setInputError(input, error, 'Password must be at least 6 characters');
      return false;
    } else {
      setInputSuccess(input, error);
      return true;
    }
  }

  function validateConfirmPassword(passwordInput, confirmInput) {
    const password = passwordInput.value;
    const confirmPassword = confirmInput.value;
    const error = document.getElementById('confirmPasswordError');
    
    if (!confirmPassword) {
      setInputError(confirmInput, error, 'Please confirm your password');
      return false;
    } else if (password !== confirmPassword) {
      setInputError(confirmInput, error, 'Passwords do not match');
      return false;
    } else {
      setInputSuccess(confirmInput, error);
      return true;
    }
  }

  function setInputError(input, errorElement, message) {
    input.classList.add('error');
    input.classList.remove('success');
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.classList.add('show');
    }
  }

  function setInputSuccess(input, errorElement) {
    input.classList.remove('error');
    input.classList.add('success');
    if (errorElement) {
      errorElement.classList.remove('show');
    }
  }

  function clearError(errorId, input) {
    const error = document.getElementById(errorId);
    if (error) {
      error.classList.remove('show');
    }
    input.classList.remove('error');
  }

  function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
      <div class="notification-content">
        <h4>${type === 'success' ? 'Success!' : 'Error'}</h4>
        <p>${message}</p>
      </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
      notification.style.opacity = '0';
      setTimeout(() => {
        notification.remove();
      }, 300);
    }, 3000);
  }

  // ============================================
  // REVEAL ANIMATIONS
  // ============================================
  function initRevealAnimations() {
    const revealElements = document.querySelectorAll(
      '.benefit-item, .form-group, .btn-primary'
    );

    const observerOptions = {
      threshold: 0.15,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
          }, index * 100);
          
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    revealElements.forEach(el => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(20px)';
      el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
      observer.observe(el);
    });
  }

  // ============================================
  // FLOATING SHAPES PARALLAX
  // ============================================
  function initFloatingShapes() {
    const shapes = document.querySelectorAll('.circle, .background-circle');

    function handleParallax() {
      const scrolled = window.pageYOffset;

      shapes.forEach((shape, index) => {
        const speed = 0.3 + (index * 0.1);
        const yPos = -(scrolled * speed);
        shape.style.transform = `translateY(${yPos}px)`;
      });
    }

    window.addEventListener('scroll', handleParallax, { passive: true });
  }

  // ============================================
  // FOOTER DYNAMIC TEXT
  // ============================================
  function initFooterText() {
    const footerText = document.getElementById('footerText');
    if (footerText) {
      const messages = [
        'Making waste management smarter, one bin at a time.',
        'Powered by IoT technology and sustainable innovation.',
        'Join us in creating cleaner, greener communities.',
        'Real-time monitoring for a cleaner tomorrow.'
      ];
      
      let currentIndex = 0;
      
      function updateFooterText() {
        footerText.style.opacity = '0';
        
        setTimeout(() => {
          footerText.textContent = messages[currentIndex];
          footerText.style.opacity = '1';
          currentIndex = (currentIndex + 1) % messages.length;
        }, 500);
      }
      
      // Initial text
      footerText.textContent = messages[0];
      
      // Rotate messages every 5 seconds
      setInterval(updateFooterText, 5000);
    }
  }

  // ============================================
  // NAVIGATION ACTIVE STATE
  // ============================================
  function initActiveNav() {
    const navLinks = document.querySelectorAll('.nav-link');
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';

    navLinks.forEach(link => {
      const href = link.getAttribute('href');
      if (href === currentPage) {
        link.style.color = 'var(--primary-color)';
        link.style.fontWeight = '700';
      }
    });
  }

  // ============================================
  // INITIALIZATION
  // ============================================
  function init() {
    console.log('[Smart Trashbin] Initializing registration page...');
    
    // Initialize all systems
    initScrollProgress();
    initHeaderScroll();
    initPasswordToggle();
    initFormValidation();
    initRevealAnimations();
    initFloatingShapes();
    initFooterText();
    initActiveNav();
    
    console.log('[Smart Trashbin] Registration page initialized successfully!');
  }

  // ============================================
  // RUN ON DOM READY
  // ============================================
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();

// ============================================
// BENEFIT ICON FLIP ANIMATION (same as login)
// ============================================
function initBenefitIconFlip() {
  const benefitItems = document.querySelectorAll('.benefit-item');

  benefitItems.forEach(item => {
    item.addEventListener('mouseenter', () => {
      const icon = item.querySelector('.benefit-icon-container i');
      if (icon) icon.style.transform = 'rotateY(360deg)';
    });

    item.addEventListener('mouseleave', () => {
      const icon = item.querySelector('.benefit-icon-container i');
      if (icon) icon.style.transform = 'rotateY(0deg)';
    });
  });
}

// Run after DOM loaded
document.addEventListener("DOMContentLoaded", () => {
  initBenefitIconFlip();

  // ============================================
  // UNIFORM PASSWORD TOGGLE ICONS (same as login)
  // ============================================
  const toggleIcons = document.querySelectorAll('.toggle-password');
  toggleIcons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const input = btn.parentElement.querySelector('input');
      const icon = btn.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    });
  });
});
