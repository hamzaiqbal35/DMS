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

// Function to handle login response
function handleLoginResponse(response) {
    if (response.status === "success") {
        localStorage.setItem("jwt_token", response.token);
        $("#alertBox").removeClass("d-none alert-danger")
                     .addClass("alert-success")
                     .text(response.message);
        setTimeout(() => {
            window.location.href = "../views/dashboard.php";
        }, 2000);
    }
}

$(document).ready(function () {
    $('.collapse').on('show.bs.collapse', function () {
        $(this).prev().find('.fa-chevron-down').addClass('rotate-icon');
    }).on('hide.bs.collapse', function () {
        $(this).prev().find('.fa-chevron-down').removeClass('rotate-icon');
    });
});