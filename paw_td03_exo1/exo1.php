<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $student_id = trim($_POST["student_id"]);
    $name       = trim($_POST["name"]);
    $group      = trim($_POST["group"]);

    $errors = [];

    if ($student_id === "" || !ctype_digit($student_id)) {
        $errors[] = "Student ID must contain only numbers.";
    }

    if ($name === "" || !preg_match("/^[A-Za-z ]+$/", $name)) {
        $errors[] = "Name must contain only letters and spaces.";
    }

    if ($group === "" || !preg_match("/^[A-Za-z0-9]+$/", $group)) {
        $errors[] = "Group must contain only letters or numbers.";
    }

    if (!empty($errors)) {
        echo "<h3>Error:</h3>";
        foreach ($errors as $e) echo "<p>$e</p>";
        echo '<a href="exo2.html">Go back</a>';
        exit;
    }

    $file = "students.json";
    $students = [];

    if (file_exists($file)) {
        $json = file_get_contents($file);
        $students = json_decode($json, true);
        if (!is_array($students)) $students = [];
    }

    $students[] = [
        "student_id" => $student_id,
        "name"       => $name,
        "group"      => $group
    ];

    file_put_contents($file, json_encode($students, JSON_PRETTY_PRINT));

    echo "<h2>Student Added Successfully ✔</h2>";
    echo "<p><strong>ID:</strong> $student_id</p>";
    echo "<p><strong>Name:</strong> $name</p>";
    echo "<p><strong>Group:</strong> $group</p>";
    echo '<br><a href="exo2.html">Add another student</a>';
}
?>
