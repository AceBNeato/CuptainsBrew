document.addEventListener("DOMContentLoaded", function () {
    // Get URL parameters
    const params = new URLSearchParams(window.location.search);
    const itemName = params.get("name");
    const itemPrice = params.get("price");
    const itemDesc = params.get("desc");
    const itemImg = params.get("img");

    // Populate editor fields
    if (itemName) document.getElementById("editor-name").textContent = itemName;
    if (itemPrice) document.getElementById("editor-price").textContent = itemPrice;
    if (itemDesc) document.getElementById("editor-description").textContent = itemDesc;
    if (itemImg) document.getElementById("editor-img").src = itemImg;

    // Go back to the menu page
    document.getElementById("back-button").addEventListener("click", function () {
        window.history.back();
    });
});
