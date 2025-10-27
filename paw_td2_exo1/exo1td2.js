function analyze() {
  const rows = document.querySelectorAll("#attendance tbody tr");

  rows.forEach(row => {
    const inputs = row.querySelectorAll("input[type='checkbox']");
    const sessions = Array.from(inputs).slice(0, 6);
    //const participations = Array.from(inputs).slice(6, 12);

    const absences = sessions.length - sessions.filter(c => c.checked).length;
    //const parts = participations.filter(c => c.checked).length;

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
