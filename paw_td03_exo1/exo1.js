document.getElementById("studentForm").addEventListener("submit", function(e) {
  e.preventDefault();

  // Clear previous errors
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

  if (!valid) return;

  // Prepare data to send
  const formData = new FormData();
  formData.append('student_id', id);
  formData.append('name', `${last} ${first}`);
  formData.append('group', 'G1'); // adjust if you have a group input

  // Send to PHP via fetch
  fetch('exo1.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(student => {
    // Add new row to table
    const tableBody = document.querySelector("#attendance tbody");
    const row = document.createElement("tr");

    row.innerHTML = `
      <td>${student.student_id}</td>
      <td>${student.name}</td>
      <td>${student.group}</td>
      <td>PAW</td>
      <td><input type="checkbox"></td><td><input type="checkbox"></td><td><input type="checkbox"></td>
      <td><input type="checkbox"></td><td><input type="checkbox"></td><td><input type="checkbox"></td>
      <td><input type="checkbox"></td><td><input type="checkbox"></td><td><input type="checkbox"></td>
      <td><input type="checkbox"></td><td><input type="checkbox"></td><td><input type="checkbox"></td>
      <td></td>
    `;

    tableBody.appendChild(row);
    document.getElementById("studentForm").reset();
  })
  .catch(err => console.error(err));
});

// Your analyze function stays the same
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
