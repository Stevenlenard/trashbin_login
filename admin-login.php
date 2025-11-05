<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Trashbin - Login</title>
  <link rel="stylesheet" href="css/login.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
  <!-- Navigation Header -->
  <header class="header">
    <div class="header-container">
      <div class="logo-section">
        <div class="logo-wrapper">
          <svg class="animated-logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <rect x="30" y="35" width="40" height="50" rx="6" fill="#16a34a"/>
            <rect x="25" y="30" width="50" height="5" fill="#15803d"/>
            <rect x="40" y="20" width="20" height="8" rx="2" fill="#22c55e"/>
            <line x1="40" y1="45" x2="40" y2="80" stroke="#f0fdf4" stroke-width="3" />
            <line x1="50" y1="45" x2="50" y2="80" stroke="#f0fdf4" stroke-width="3" />
            <line x1="60" y1="45" x2="60" y2="80" stroke="#f0fdf4" stroke-width="3" />
          </svg>
        </div>
        <div class="logo-text-section">
          <h1 class="brand-name">Smart Trashbin</h1>
          <p class="header-subtitle">Intelligent Waste Management System</p>
        </div>
      </div>
      <nav class="nav-center">
        <a href="index.php" class="nav-link">Home</a>
        <a href="about.php" class="nav-link">About</a>
        <a href="contact.php" class="nav-link">Contact</a>
        <a href="features.php" class="nav-link">Features</a>
      </nav>
      <nav class="nav-buttons">
        <a href="registration.php" class="btn btn-signup">
          <i class="fas fa-user-plus"></i> Sign Up
        </a>
      </nav>
    </div>
  </header>

  <!-- Login Container -->
  <div class="login-container">
    <!-- Background floating circles -->
    <div class="background-circle background-circle-1"></div>
    <div class="background-circle background-circle-2"></div>
    <div class="background-circle background-circle-3"></div>

    <div class="login-wrapper">
      <!-- Left Side - Branding -->
      <div class="login-branding">
        <div class="branding-content">
          <!-- Decorative circles -->
          <div class="circle circle-1"></div>
          <div class="circle circle-2"></div>
          <div class="circle circle-3"></div>

          <!-- Container box for branding text -->
          <div class="branding-box">
            <div class="logo-circle">
              <i class="fas fa-trash-alt"></i>
            </div>
            <h1>Smart Trashbin</h1>
            <p>Intelligent Waste Management System</p>
          </div>

          <!-- Features List with Flip Animation -->
          <div class="features-list">
            <div class="feature-item">
              <div class="feature-icon-container">
                <i class="fas fa-chart-line"></i>
              </div>
              <span>Real-time Monitoring</span>
            </div>
            <div class="feature-item">
              <div class="feature-icon-container">
                <i class="fas fa-bell"></i>
              </div>
              <span>Automated Alerts</span>
            </div>
            <div class="feature-item">
              <div class="feature-icon-container">
                <i class="fas fa-chart-bar"></i>
              </div>
              <span>Analytics Dashboard</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Side - Login Form -->
      <div class="login-form-section">
        <div class="form-container">
          <!-- Form Header -->
          <div class="form-header">
            <h2><span class="header-highlight">Admin Login</span></h2>
            <p>Access the administrative panel</p>
          </div>

          <!-- Login Form -->
          <form id="loginForm" class="auth-form">
            <!-- Email Field -->
            <div class="form-group">
              <label for="email">Email Address</label>
              <div class="input-wrapper">
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
              </div>
              <span class="error-message" id="emailError"></span>
            </div>

            <!-- Password Field -->
            <div class="form-group">
              <label for="password">Password</label>
              <div class="input-wrapper">
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <button type="button" class="toggle-password" id="togglePassword">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <span class="error-message" id="passwordError"></span>
            </div>

            <!-- Form Options -->
            <div class="form-options">
              <label class="remember-me">
                <input type="checkbox" name="remember" id="remember">
                <span>Remember me</span>
              </label>
              <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-primary">Sign In</button>

            <!-- Divider -->
            <div class="divider">
              <span>Or continue with</span>
            </div>

            <!-- Google Sign In -->
            <button type="button" class="social-btn google-btn">
              <i class="fab fa-google"></i>
              Sign in with Google
            </button>

            <!-- Footer -->
            <div class="auth-footer">
              <p>Don't have an account? <a href="registration.php">Sign up now</a></p>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    <div class="footer-content">
      <div class="footer-links">
        <a href="privacy.php">Privacy Policy</a>
        <span class="separator">•</span>
        <a href="terms.php">Terms of Service</a>
        <span class="separator">•</span>
        <a href="contact.php">Support</a>
      </div>
      <p class="footer-text" id="footerText"></p>
      <p class="footer-copyright">
        &copy; 2025 Smart Trashbin. All rights reserved.
      </p>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
    // Form submission
    document.addEventListener("DOMContentLoaded", () => {
      const loginForm = document.getElementById("loginForm")
      const emailInput = document.getElementById("email")
      const passwordInput = document.getElementById("password")
      const togglePassword = document.getElementById("togglePassword")

      // Toggle password visibility
      togglePassword.addEventListener("click", (e) => {
        e.preventDefault()
        const type = passwordInput.type === "password" ? "text" : "password"
        passwordInput.type = type
        togglePassword.querySelector("i").classList.toggle("fa-eye")
        togglePassword.querySelector("i").classList.toggle("fa-eye-slash")
      })

      // Clear error on input
      emailInput.addEventListener("input", () => {
        document.getElementById("emailError").textContent = ""
        emailInput.classList.remove("error")
      })

      passwordInput.addEventListener("input", () => {
        document.getElementById("passwordError").textContent = ""
        passwordInput.classList.remove("error")
      })

      loginForm.addEventListener("submit", async (e) => {
        e.preventDefault()

        const email = emailInput.value.trim()
        const password = passwordInput.value.trim()

        // Validate inputs
        const emailValid = validateEmail(emailInput)
        const passwordValid = validatePassword(passwordInput)

        if (!emailValid || !passwordValid) {
          return
        }

        const formData = new FormData()
        formData.append("email", email)
        formData.append("password", password)

        try {
          console.log("[v0] Sending login request for email:", email)
          const response = await fetch("login-handler.php", {
            method: "POST",
            body: formData,
          })

          const data = await response.json()
          console.log("[v0] Login response received:", data)
          console.log("[v0] Redirect URL:", data.redirect)

          if (data.success) {
            console.log("[v0] Login successful, redirecting to:", data.redirect)
            showNotification(data.message, "success")

            // Redirect after delay to let user see success message
            if (data.redirect) {
              console.log("[v0] Redirecting to:", data.redirect)
              setTimeout(() => {
                window.location.href = data.redirect
              }, 1500) // 1.5 second delay for visibility
            }
          } else {
            // Show error messages
            if (data.errors.email) {
              setInputError(emailInput, data.errors.email)
              showNotification(data.errors.email, "error")
            }
            if (data.errors.password) {
              setInputError(passwordInput, data.errors.password)
              showNotification(data.errors.password, "error")
            }
            if (data.errors.general) {
              showNotification(data.errors.general, "error")
            }
          }
        } catch (error) {
          console.error("[v0] Login error:", error)
          showNotification("An error occurred during login. Please try again.", "error")
        }
      })
    })

    function validateEmail(input) {
      const email = input.value.trim()
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

      if (!email) {
        setInputError(input, "Email is required")
        return false
      } else if (!emailRegex.test(email)) {
        setInputError(input, "Please enter a valid email")
        return false
      } else {
        setInputSuccess(input)
        return true
      }
    }

    function validatePassword(input) {
      const password = input.value.trim()

      if (!password) {
        setInputError(input, "Password is required")
        return false
      } else if (password.length < 6) {
        setInputError(input, "Password must be at least 6 characters")
        return false
      } else {
        setInputSuccess(input)
        return true
      }
    }

    function setInputError(input, message) {
      input.classList.add("error")
      input.classList.remove("success")
      const errorElement = input.closest(".form-group").querySelector(".error-message")
      if (errorElement) {
        errorElement.textContent = message
      }
    }

    function setInputSuccess(input) {
      input.classList.remove("error")
      input.classList.add("success")
    }

    function showNotification(message, type = "info") {
      console.log("[v0] Notification (" + type + "):", message)

      // Create notification element
      let notificationEl = document.getElementById("notificationMessage")
      if (!notificationEl) {
        notificationEl = document.createElement("div")
        notificationEl.id = "notificationMessage"
        notificationEl.style.cssText = `
          position: fixed;
          top: 20px;
          right: 20px;
          padding: 16px 20px;
          border-radius: 8px;
          font-size: 14px;
          font-weight: 600;
          z-index: 10000;
          animation: slideIn 0.3s ease;
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        `
        document.body.appendChild(notificationEl)
      }

      notificationEl.textContent = message
      notificationEl.style.display = "block"

      if (type === "error") {
        notificationEl.style.backgroundColor = "#fee2e2"
        notificationEl.style.color = "#991b1b"
        notificationEl.style.borderLeft = "4px solid #dc2626"
      } else if (type === "success") {
        notificationEl.style.backgroundColor = "#dcfce7"
        notificationEl.style.color = "#166534"
        notificationEl.style.borderLeft = "4px solid #16a34a"
      } else {
        notificationEl.style.backgroundColor = "#f3f4f6"
        notificationEl.style.color = "#374151"
        notificationEl.style.borderLeft = "4px solid #6b7280"
      }

      // Auto-hide non-success messages after 4 seconds
      if (type !== "success") {
        setTimeout(() => {
          notificationEl.style.opacity = "0"
          notificationEl.style.transition = "opacity 0.3s ease"
          setTimeout(() => {
            notificationEl.style.display = "none"
            notificationEl.style.opacity = "1"
          }, 300)
        }, 4000)
      }
    }
  </script>
</body>
</html>
