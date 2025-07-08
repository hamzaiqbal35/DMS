document.addEventListener("DOMContentLoaded", () => {
    // Auto-update Time
    const currentTime = document.getElementById("currentTime")
    if (currentTime) {
      function updateTime() {
        const now = new Date()
        currentTime.innerText = now.toLocaleString()
      }
      updateTime()
      setInterval(updateTime, 1000)
    }
  
    // Set active navigation item based on current URL
    setActiveNavItem()
  
    // Initialize collapsible menus in sidebar
    initializeSidebarCollapse()
  
    // Initialize footer behavior
    initializeFooterBehavior()
  
    // Check JWT token validity on page load
    checkTokenValidity()
  })
  
  // Function to check if JWT token is valid and not expired
  function checkTokenValidity() {
      const token = localStorage.getItem("jwt_token");
      
      // Get current page name
      const currentPage = window.location.pathname.split('/').pop();
      
      // Define public pages that don't require authentication
      const publicPages = [
          'login.php',
          'register.php',
          'forgotPassword.php',
          'resetPasswordForm.php'
      ];
      
      // Allow access to public pages without token
      if (publicPages.includes(currentPage)) {
          return true;
      }
      
      if (!token) {
          // No token found, redirect to login if not already on login page
          if (!window.location.pathname.includes('login.php')) {
              window.location.href = '../views/login.php';
          }
          return false;
      }
  
      try {
          // Decode JWT token (basic check - you might want to verify on server)
          const payload = JSON.parse(atob(token.split('.')[1]));
          const currentTime = Math.floor(Date.now() / 1000);
          
          if (payload.exp < currentTime) {
              // Token expired
              localStorage.removeItem("jwt_token");
              if (!window.location.pathname.includes('login.php')) {
                  alert('Session expired. Please login again.');
                  window.location.href = '../views/login.php';
              }
              return false;
          }
          return true;
      } catch (error) {
          // Invalid token
          localStorage.removeItem("jwt_token");
          if (!window.location.pathname.includes('login.php')) {
              window.location.href = '../views/login.php';
          }
          return false;
      }
  }
  
  // Function to handle login response - IMPROVED
  function handleLoginResponse(response) {
    if (response.status === "success") {
        // Store JWT token in localStorage
        localStorage.setItem("jwt_token", response.token);
        
        // Show success message
        const alertBox = document.getElementById("alertBox");
        if (alertBox) {
            alertBox.classList.remove("d-none", "alert-danger");
            alertBox.classList.add("alert-success");
            alertBox.innerText = response.message;
        }
        
        // Redirect after delay
        setTimeout(() => {
            window.location.href = "../views/dashboard.php";
        }, 2000);
    } else {
        // Show error message
        const alertBox = document.getElementById("alertBox");
        if (alertBox) {
            alertBox.classList.remove("d-none", "alert-success");
            alertBox.classList.add("alert-danger");
            alertBox.innerText = response.message;
        }
    }
  }
  
  // Function to get JWT token from localStorage
  function getJWTToken() {
    return localStorage.getItem("jwt_token");
  }
  
  // Function to logout user - IMPROVED VERSION WITH TOASTR
  function logout() {
    // Remove token from localStorage immediately
    localStorage.removeItem("jwt_token");
    
    // Configure toastr options for logout
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": false,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": true,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "500",
            "timeOut": "2000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };
    }
    
    // Make AJAX call to server-side logout
    fetch('../model/login/logout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Show success toast
            if (typeof toastr !== 'undefined') {
                toastr.success(data.message || 'Successfully logged out!');
            } else {
                alert(data.message || 'Successfully logged out!');
            }
            
            // Redirect after delay
            setTimeout(() => {
                window.location.href = '../views/login.php';
            }, 2000);
        } else {
            // Show warning but still redirect
            if (typeof toastr !== 'undefined') {
                toastr.warning(data.message || 'Logout completed');
            } else {
                alert(data.message || 'Logout completed');
            }
            setTimeout(() => {
                window.location.href = '../views/login.php';
            }, 2000);
        }
    })
    .catch(error => {
        console.error('Logout error:', error);
        // Still show message and redirect even if request fails
        if (typeof toastr !== 'undefined') {
            toastr.success('Successfully logged out!');
        } else {
            alert('Successfully logged out!');
        }
        setTimeout(() => {
            window.location.href = '../views/login.php';
        }, 2000);
    });
  }
  
  // Alternative: Custom popup function (if you prefer custom styling)
  function showCustomLogoutPopup(message) {
    // Create custom popup
    const popup = document.createElement('div');
    popup.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 9999;
        font-family: Arial, sans-serif;
        font-size: 14px;
        animation: slideIn 0.3s ease-out;
    `;
    
    popup.innerHTML = `
        <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
        ${message}
    `;
    
    // Add CSS animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
    
    // Add to page
    document.body.appendChild(popup);
    
    // Remove after delay
    setTimeout(() => {
        popup.style.animation = 'slideIn 0.3s ease-out reverse';
        setTimeout(() => {
            document.body.removeChild(popup);
            document.head.removeChild(style);
        }, 300);
    }, 1200);
  }
  
  // Set active navigation item based on current URL
  function setActiveNavItem() {
    const currentPath = window.location.pathname
    const navLinks = document.querySelectorAll("#sidebar .nav-link")
  
    navLinks.forEach((link) => {
      const href = link.getAttribute("href")
      if (href && currentPath.includes(href) && href !== "/" && href !== "/index.php") {
        link.classList.add("active")
  
        // If this link is in a dropdown, expand the dropdown and mark parent as having active child
        const parentCollapse = link.closest(".collapse")
        if (parentCollapse) {
          const collapseToggle = document.querySelector(`[href="#${parentCollapse.id}"]`)
          if (collapseToggle) {
            collapseToggle.setAttribute("aria-expanded", "true")
            collapseToggle.classList.add("menu-expanded")
            collapseToggle.classList.add("parent-active") // Add the parent-active class
            parentCollapse.classList.add("show")
          }
        }
      }
    })
  
    // Check for parent-active status even if menu is collapsed
    document.querySelectorAll("#sidebar .collapse").forEach((collapse) => {
      const hasActiveChild = collapse.querySelector(".nav-link.active")
      if (hasActiveChild) {
        const collapseToggle = document.querySelector(`[href="#${collapse.id}"]`)
        if (collapseToggle) {
          collapseToggle.classList.add("parent-active")
        }
      }
    })
  }
  
  function initializeSidebarCollapse() {
    // Initialize all collapse elements
    document.querySelectorAll(".collapse").forEach((collapse) => {
        // Initialize Bootstrap collapse
        new bootstrap.Collapse(collapse, {
            toggle: false
        });
  
        // Handle show event
        collapse.addEventListener("show.bs.collapse", function () {
            const toggle = this.previousElementSibling;
            if (toggle) {
                toggle.querySelector(".fa-chevron-down")?.classList.add("rotate-icon");
                toggle.classList.add("menu-expanded");
                toggle.setAttribute("aria-expanded", "true");
            }
        });
  
        // Handle hide event
        collapse.addEventListener("hide.bs.collapse", function () {
            const toggle = this.previousElementSibling;
            if (toggle) {
                toggle.querySelector(".fa-chevron-down")?.classList.remove("rotate-icon");
                toggle.classList.remove("menu-expanded");
                toggle.setAttribute("aria-expanded", "false");
  
                // Preserve parent-active class if there's an active child
                const hasActiveChild = this.querySelector(".nav-link.active");
                if (hasActiveChild) {
                    toggle.classList.add("parent-active");
                } else {
                    toggle.classList.remove("parent-active");
                }
            }
        });
    });
  
    // Handle click on menu items with submenus
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach((toggle) => {
        toggle.addEventListener("click", function (e) {
            e.preventDefault();
            const targetId = this.getAttribute("href");
            const targetElement = document.querySelector(targetId);
            
            if (!targetElement) return;
  
            // Get Bootstrap collapse instance
            const bsCollapse = bootstrap.Collapse.getInstance(targetElement);
            if (!bsCollapse) return;
  
            // Close other open menus
            document.querySelectorAll(".collapse.show").forEach((openMenu) => {
                if (openMenu !== targetElement) {
                    const openBsCollapse = bootstrap.Collapse.getInstance(openMenu);
                    if (openBsCollapse) {
                        openBsCollapse.hide();
                    }
                }
            });
  
            // Toggle current menu
            bsCollapse.toggle();
        });
    });
  }
  
  // Initialize footer behavior with improved scroll detection
  function initializeFooterBehavior() {
    const sidebarFooter = document.querySelector(".sidebar-footer")
    if (!sidebarFooter) return
  
    let lastScrollTop = 0
    let scrollTimer = null
    const sidebar = document.getElementById("sidebar")
  
    sidebar.addEventListener(
      "scroll",
      function () {
        const st = this.scrollTop
  
        // Clear any pending timer
        if (scrollTimer !== null) {
          clearTimeout(scrollTimer)
        }
  
        // Determine scroll direction with improved threshold detection
        if (st > lastScrollTop && st > 30) {
          // Scrolling down - hide footer
          sidebarFooter.classList.add("footer-hidden")
        } else if (st < lastScrollTop || st < 10) {
          // Scrolling up or near top - show footer
          sidebarFooter.classList.remove("footer-hidden")
        }
  
        // Small delay before updating lastScrollTop to ensure smoother transitions
        scrollTimer = setTimeout(() => {
          lastScrollTop = st <= 0 ? 0 : st // For Mobile or negative scrolling
        }, 50)
      },
      { passive: true },
    ) // Using passive event for better performance
  }

  // === GLOBAL MODAL ACCESSIBILITY & CLEANUP HANDLER ===
  $(document).on('hide.bs.modal', '.modal', function () {
      // Blur the focused element if it's inside the modal or the modal itself
      const active = document.activeElement;
      if (active && (this === active || this.contains(active))) {
          active.blur();
      }
  });

  $(document).on('hidden.bs.modal', '.modal', function () {
      // Only reset the form if it exists
      const form = $(this).find('form')[0];
      if (form && typeof form.reset === 'function') {
          form.reset();
      }
      // Remove any lingering backdrops and restore body scroll
      setTimeout(() => {
          $('.modal-backdrop').remove();
          $('body').removeClass('modal-open').css({ 'overflow': '', 'padding-right': '' });
      }, 100);
  });