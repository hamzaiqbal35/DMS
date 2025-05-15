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

});

$(document).ready(function () {
    $('.collapse').on('show.bs.collapse', function () {
        $(this).prev().find('.fa-chevron-down').addClass('rotate-icon');
    }).on('hide.bs.collapse', function () {
        $(this).prev().find('.fa-chevron-down').removeClass('rotate-icon');
    });
});

