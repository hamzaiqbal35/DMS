document.addEventListener("DOMContentLoaded", function() {
    // Update Time
    function updateTime() {
        let now = new Date();
        let formattedTime = now.toLocaleString();
        document.getElementById("currentTime").innerText = formattedTime;
    }
    setInterval(updateTime, 1000);
    updateTime();

    // Dark Mode Toggle
    const darkModeToggle = document.getElementById("darkModeToggle");
    darkModeToggle.addEventListener("click", function() {
        document.body.classList.toggle("dark-mode");
        localStorage.setItem("dark-mode", document.body.classList.contains("dark-mode") ? "enabled" : "disabled");
    });

    // Load Dark Mode Preference
    if (localStorage.getItem("dark-mode") === "enabled") {
        document.body.classList.add("dark-mode");
    }
});
$(document).ready(function () {
    $('.collapse').on('show.bs.collapse', function () {
        $(this).prev().find('.fa-chevron-down').addClass('rotate-icon');
    }).on('hide.bs.collapse', function () {
        $(this).prev().find('.fa-chevron-down').removeClass('rotate-icon');
    });
});
