<?php include 'db.php'; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Queue Number</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h2>Your Queue Number</h2>

<?php
$result = $conn->query("SELECT MAX(queue_number) AS max_q FROM queue");
$row = $result->fetch_assoc();
$next = $row['max_q'] + 1;

$conn->query("INSERT INTO queue (queue_number) VALUES ($next)");

echo "<h1>$next</h1>";
?>

</body>
</html>
