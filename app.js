 
document.getElementById('loginForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent form submission

    var username = document.getElementById('username').value;
    var password = document.getElementById('password').value;
    var errorMessage = document.getElementById('error-message');

    
    if (username === "" || password === "") {
        errorMessage.textContent = "Please enter both username and password.";
    } else {
        errorMessage.textContent = ""; // Clear any previous error message

         
        if (username === "admin" && password === "password123") {
            alert("Login successful!");
            
        } else {
            errorMessage.textContent = "Invalid username or password.";
        }
    }
});


document.getElementById('signupForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent form submission

    var username = document.getElementById('new-username').value;
    var email = document.getElementById('new-email').value;
    var password = document.getElementById('new-password').value;
    var signupErrorMessage = document.getElementById('signup-error-message');

   
    if (username === "" || email === "" || password === "") {
        signupErrorMessage.textContent = "Please fill in all fields.";
    } else {
        signupErrorMessage.textContent = ""; // Clear any previous error message

         
        alert("Sign up successful!");
        
    }
});


document.getElementById('show-signup').addEventListener('click', function() {
    document.getElementById('login-form').style.display = 'none';
    document.getElementById('signup-form').style.display = 'block';
});

document.getElementById('show-login').addEventListener('click', function() {
    document.getElementById('signup-form').style.display = 'none';
    document.getElementById('login-form').style.display = 'block';
});
