document.addEventListener("DOMContentLoaded", function() {
    // Auto-update Time
    const currentTime = document.getElementById("currentTime");
    if (currentTime) {
        function updateTime() {
            let now = new Date();
            currentTime.innerText = now.toLocaleString();
        }
        updateTime();
        setInterval(updateTime, 1000);
    }

    // Dark Mode Toggle
    const toggleBtn = document.getElementById("darkModeToggle");
    const body = document.body;

    if (toggleBtn) {
        toggleBtn.addEventListener("click", function () {
            body.classList.toggle("dark-mode");

            // Save preference
            const theme = body.classList.contains("dark-mode") ? "dark" : "light";
            localStorage.setItem("theme", theme);
        });

        // Load preference
        const savedTheme = localStorage.getItem("theme");
        if (savedTheme === "dark") {
            body.classList.add("dark-mode");
        } else {
            body.classList.remove("dark-mode");
        }
    }
});

$(document).ready(function () {
    $('.collapse').on('show.bs.collapse', function () {
        $(this).prev().find('.fa-chevron-down').addClass('rotate-icon');
    }).on('hide.bs.collapse', function () {
        $(this).prev().find('.fa-chevron-down').removeClass('rotate-icon');
    });
});
