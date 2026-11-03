<?php include 'session.php'; ?>

<?php include 'db.php'; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Appointment</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h2>Book Appointment</h2>

<form method="POST">
    <input type="text" name="name" placeholder="Your Name" required>
    <input type="text" name="phone" placeholder="Phone Number" required>
    <input type="date" name="date" required>
    <input type="time" name="time" required>
    <button type="submit" name="submit">Book</button>
</form>

<?php
if (isset($_POST['submit'])) {
    $stmt = $conn->prepare(
        "INSERT INTO appointments (name, phone, date, time) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss",
        $_POST['name'],
        $_POST['phone'],
        $_POST['date'],
        $_POST['time']
    );
    $stmt->execute();
    echo "<p class='success'>Appointment booked successfully!</p>";
}
?>

</body>
</html>
