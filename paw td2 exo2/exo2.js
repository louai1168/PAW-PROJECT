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
    function analyze() {
  const rows = document.querySelectorAll("#attendance tbody tr");

  rows.forEach(row => {
    const inputs = row.querySelectorAll("input[type='checkbox']");
    const sessions = Array.from(inputs).slice(0, 6);

    const absences = sessions.length - sessions.filter(c => c.checked).length;

    let message = "";
    row.classList.remove("green", "yellow", "red");

    if (absences < 3) {
      row.classList.add("green");
      message = "Good attendance – Excellent participation";
    } else if (absences >= 3 && absences <= 4) {
      row.classList.add("yellow");
      message = "Warning – attendance low – You need to participate more";
    } else {
      row.classList.add("red");
      message = "Excluded – too many absences – You need to participate more";
    }

    row.lastElementChild.textContent = `${absences} Abs /  ${message}`;
  });
}
