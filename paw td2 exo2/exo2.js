document.getElementById("studentForm").addEventListener("submit", function(e) {
      e.preventDefault();

      
      document.querySelectorAll(".error").forEach(el => el.textContent = "");

      const id = document.getElementById("studentID").value.trim();
      const last = document.getElementById("lastName").value.trim();
      const first = document.getElementById("firstName").value.trim();
      const email = document.getElementById("email").value.trim();

      let valid = true;

      
      if (id === "" || !/^[0-9]+$/.test(id)) {
        document.getElementById("idError").textContent = "Student ID must contain only numbers.";
        valid = false;
      }

      
      if (last === "" || !/^[A-Za-z]+$/.test(last)) {
        document.getElementById("lastError").textContent = "Last name must contain only letters.";
        valid = false;
      }

      
      if (first === "" || !/^[A-Za-z]+$/.test(first)) {
        document.getElementById("firstError").textContent = "First name must contain only letters.";
        valid = false;
      }

      
      const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
      if (!emailPattern.test(email)) {
        document.getElementById("emailError").textContent = "Please enter a valid email address.";
        valid = false;
      }

      
    });