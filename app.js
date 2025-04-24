
document.getElementById('loginForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent form submission

    var username = document.getElementById('username').value;
    var password = document.getElementById('password').value;
    var errorMessage = document.getElementById('error-message');

    // Basic validation
    if (username === "" || password === "") {
        errorMessage.textContent = "Please enter both username and password.";
    } else {
        errorMessage.textContent = ""; // Clear any previous error message

        // Simulate a successful login (in a real app, you would check credentials here)
        if (username === "admin" && password === "password123") {
            alert("Login successful!");
            // You can redirect to another page or perform other actions here.
            // window.location.href = "dashboard.html"; // For example, redirect to a dashboard.
        } else {
            errorMessage.textContent = "Invalid username or password.";
        }
    }
});
