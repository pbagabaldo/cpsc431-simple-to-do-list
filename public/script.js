document.addEventListener("DOMContentLoaded", function () {
  // Event listener for the Register button
  document
    .getElementById("signupButton")
    .addEventListener("click", function () {
      var data = {
        operation: "register",
        username: document.getElementById("reg_username").value,
        email: document.getElementById("reg_email").value,
        password: document.getElementById("reg_password").value,
      };

      fetch("../api/users.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      })
        .then((response) => response.json())
        .then((data) => alert(data.message))
        .catch((error) => console.error("Error:", error));
    });

  // Event listener for the Login button
  document.getElementById("loginButton").addEventListener("click", function () {
    var data = {
      operation: "login",
      username: document.getElementById("login_username").value,
      password: document.getElementById("login_password").value,
    };

    fetch("../api/users.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
    })
      .then((response) => response.json())
      .then((data) => {
        alert(data.message);
        if (data.user_id) {
          console.log("Logged in user ID:", data.user_id);
          window.location.href = 'homePage.html';
        }
      })
      .catch((error) => console.error("Error:", error));
  });

  function switchForm(isLoginForm) {
    var loginBox = document.getElementById("login-box");
    var signupBox = document.getElementById("signup-box");
    var loginButton = document.getElementById("toLoginButton");
    var signupButton = document.getElementById("toSignupButton");

    if (isLoginForm) {
      loginBox.style.display = "block";
      signupBox.style.display = "none";
      loginButton.classList.add("inactive");
      signupButton.classList.remove("inactive");
    } else {
      loginBox.style.display = "none";
      signupBox.style.display = "block";
      loginButton.classList.remove("inactive");
      signupButton.classList.add("inactive");
    }
  }

  // Event listeners for the buttons
  document
    .getElementById("toLoginButton")
    .addEventListener("click", function () {
      switchForm(true);
    });

  document
    .getElementById("toSignupButton")
    .addEventListener("click", function () {
      switchForm(false);
    });


  switchForm(false); 
});

