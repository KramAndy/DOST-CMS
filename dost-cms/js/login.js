
				function togglePassword() {
					let passwordField = document.getElementById("password");
					let button = document.querySelector(".showPass");

					if (passwordField.type === "password") {
						passwordField.type = "text";
						button.classList.add("activeShowPass");
						button.textContent = "👁 Hide Password";
					} else {
						passwordField.type = "password";
						button.classList.remove("activeShowPass");
						button.textContent = "👁 Show Password";
					}
				}