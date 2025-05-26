document.addEventListener("DOMContentLoaded", function () {
    let ctx1 = document.getElementById("salesChart").getContext("2d");
    let ctx2 = document.getElementById("inventoryChart").getContext("2d");

    new Chart(ctx1, {
        type: "line",
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "May"],
            datasets: [{
                label: "Sales",
                data: [1200, 1900, 3000, 5000, 6200],
                borderColor: "#007bff",
                fill: false
            }]
        }
    });

    new Chart(ctx2, {
        type: "pie",
        data: {
            labels: ["Item A", "Item B", "Item C"],
            datasets: [{
                label: "Stock",
                data: [500, 750, 900],
                backgroundColor: ["#007bff", "#28a745", "#ffc107"]
            }]
        }
    });
});
