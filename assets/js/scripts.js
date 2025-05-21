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
  })
  
  // Function to handle login response
  function handleLoginResponse(response) {
    if (response.status === "success") {
      localStorage.setItem("jwt_token", response.token)
      document.getElementById("alertBox").classList.remove("d-none", "alert-danger").classList.add("alert-success")
      document.getElementById("alertBox").innerText = response.message
      setTimeout(() => {
        window.location.href = "../views/dashboard.php"
      }, 2000)
    }
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
  