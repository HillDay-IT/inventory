<?php

require __DIR__ . '/../config/db.php';



$errors = [];
$message = '';

$staff_id = '';
$staff_first = '';
$staff_last = '';
$staff_email = '';
$checkoutDate = '';
$checkinDate = '';
$status = 'pending';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $staff_first = $_POST['staff_first'] ?? '';
     $staff_last = $_POST['staff_last'] ?? '';
    $staff_email = $_POST['staff_email'] ?? '';
$checkoutDate = str_replace('T', ' ', $_POST['checkout_date'] ?? '');
$checkinDate = str_replace('T', ' ', $_POST['checkin_date'] ?? '');
 if ($staff_first === '') {
        $errors[] = "Staff first name is required.";
    }
if ($staff_last === '') {
        $errors[] = "Staff last name is required.";
    }
    if ($staff_email === '') {
        $errors[] = "Staff email address is required.";
    }

   if ($checkinDate === '') {
        $errors[] = "Check-in date is required.";
    }

    if ($checkoutDate === '') {
        $errors[] = "Check-out date is required";
    }


    if (count($errors) === 0) {

}

    
   


$sql = "SELECT staffid FROM tblstaff
        WHERE firstname = ?  AND lastname = ? AND email= ?";


$statement = $conn->prepare($sql);
$statement->bind_param('sss', $staff_first, $staff_last, $staff_email);
$statement->execute();

$result = $statement->get_result();
$staff = $result->fetch_assoc();

if ($staff === null) {
    $errors[] = "No staff member matched that name and email.";
} else {
    $staff_id = (int)$staff['staffid'];
}

if ($staff_first===$staff_first ) {
    $errors[] = "No staff member matched that name and email.";
} else {
    $staff_id = (int)$staff['staffid'];
}

$statement->close();
        $sql = "INSERT INTO inv_reserve
                (staffid, checkout_date, checkin_date)
                VALUES (?, ?, ?)";

        $statement = $conn->prepare($sql);

        if ($statement === false) {
            $errors[] = "The SQL statement could not be prepared.";
        } else {

            // The type letters match the ten values in the INSERT statement.
            // i = integer; s = string.
            $statement->bind_param(
                'iss',
                $staff_id,
                $checkinDate,
                $checkoutDate,
            );

            if ($statement->execute()) {
                $message = "User information has been stored";
            } else {
                $errors[] = "Database error: " . $statement->error;
            }

            $statement->close();
        }
    }


function reserveValue($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Checkout Login</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="page-header">
    <p>HillDay PR</p>
    <h1>Inventory Checkout Login</h1>
</header>

<?php if ($message !== ''): ?>
    <div class="message-box">
        <p><?php echo reserveValue($message); ?></p>
    </div>
<?php endif; ?>

<div class="content-box">
    <h2>Enter User/session Information</h2>

    <?php if (count($errors) > 0): ?>
        <div class="error-message">
            <h3>Error Log:</h3>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo reserveValue($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">

        <div class="typing">
            <label for="staff_first">First Name:</label>

            <input
                class="colored-type"
                type="text"
                id="staff_first"
                name="staff_first"
                required
                value="<?php echo reserveValue($staff_first); ?>"
            >
        </div>

        <br><br>

       <div class="typing">
            <label for="staff_last">Last Name:</label>

            <input
                class="colored-type"
                type="text"
                id="staff_last"
                name="staff_last"
                required
                value="<?php echo reserveValue($staff_last); ?>"
            >
        </div>

        <br><br>


        <div class="typing">
            <label for="staff_email">Email Address:</label>
            <input
                class="colored-type"
                type="text"
                id="staff_email"
                name="staff_email"
                required
                value="<?php echo reserveValue($staff_email); ?>"
            >
        </div>

        <br><br>

        
<label class="typing" for="checkout_date">Checkout date and time:</label>
<input class="colored-type" type="datetime-local" id="checkout_date" name="checkout_date" required>

<br><br>

<label class="typing" for="checkin_date">Check-in date and time:</label>
<input class="colored-type" type="datetime-local" id="checkin_date" name="checkin_date" required>
        

        <br><br>

        <button type="submit">Submit</button>

<div class="content-box submitted-box">
    <h2>Submitted Values</h2>

    <p>User Name: <?php
    $fullName = trim($staff_first . ' ' . $staff_last);
    echo reserveValue($fullName !== '' ? $fullName : 'Not Submitted');
?></p>
    <p>User Email: <?php echo reserveValue($staff_email); ?></p>
    <p>Check-Out date/time: <?php echo reserveValue($checkoutDate !== '' ? $checkoutDate : "Not Submitted"); ?></p>
    <p>Check-In date/time: <?php echo reserveValue($checkinDate !== '' ? $checkinDate : "Not Submitted"); ?></p>

</body>
                </div>
                </form>
                </div>
</html>
