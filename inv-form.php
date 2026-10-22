<?php

/*
|--------------------------------------------------------------------------
| INVENTORY TRAINING APPLICATION
|--------------------------------------------------------------------------
|
| This file demonstrates the complete path of a form submission:
|
| HTML Form
|    ↓
| $_POST
|    ↓
| PHP Variables
|    ↓
| Prepared SQL Statement
|    ↓
| MySQL Database
|
| This version does NOT build the form for the student.
| Instead, it explains how db.php creates the database connection and how
| this file uses that connection to insert form data into MySQL.
|
*/


// -------------------------------------------------------------------------
// 1. LOAD THE DATABASE CONNECTION
// -------------------------------------------------------------------------

/*
| require tells PHP to load and run another PHP file.
|
| db.php creates the $conn variable that represents our connection
| to MySQL.
|
| After this line runs, we can use $conn inside this file.
*/

require 'db.php';


// This variable will eventually hold a success or failure message.
$message = '';

$allowedAbility = [
    "Working",
    "Not tested",
    "Not working",
    "Partially working"
];

$allowedCondition = [
    "Fair",
    "Poor",
    "Good",
    "New"
];

$isTaggable = [
    "Yes",
    "No"
];

$isLoose = [
    "Yes",
    "No"
];

$errors = [];

// These are the default values shown when nothing has been submitted.
$category_id = "Not Submitted";
$inventoryName = "Not Submitted";
$inventoryStatus = "Not Submitted";
$inventoryQuantity = "Not Submitted";
$inventoryCondition = "Not Submitted";
$inventoryTaggable = "Not Submitted";
$inventoryLoose = "Not Submitted";
$inventoryNotes = "None";
$inventoryImage = "Not Submitted";

$successMessage = null;
$projectCost = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------------------------------------------------------------------
    // 2. GET THE VALUES SENT BY THE FORM
    // ---------------------------------------------------------------------

    /*
    | $_POST is a PHP superglobal containing data submitted using POST.
    |
    | The text inside the brackets matches the "name" attribute of the
    | corresponding HTML form field.
    |
    | Example:
    |
    | <input name="name">
    |
    | becomes:
    |
    | $_POST['name']
    |
    | We store each submitted value in a PHP variable so it is easier
    | to work with.
    */

    $category_id = $_POST['category_id'] ?? "";
    $inventoryName = trim($_POST['inv_name'] ?? "");
    $inventoryStatus = $_POST['inv_ability'] ?? "";
    $inventoryQuantity = $_POST['inv_quantity'] ?? "";
    $inventoryCondition = $_POST['inv_condition'] ?? "";

    /*
    | CHECKBOXES / RADIO BUTTONS WORK DIFFERENTLY
    |
    | A radio button that is not selected normally does not appear in
    | $_POST at all.
    |
    | The ?? operator gives us a default value if the field was not submitted.
    |
    | Since "Yes" and "No" are choices, we first store the selected answer
    | as a string in the PHP variable.
    */

    $inventoryTaggable = $_POST['inv_taggable'] ?? "";
    $inventoryLoose = $_POST['inv_loose'] ?? "";

    $inventoryNotes = trim($_POST['inv_notes'] ?? "");
    $inventoryImage = trim($_POST['inv_image'] ?? "");

    // ---------------------------------------------------------------------
    // 3. VALIDATE THE FORM VALUES
    // ---------------------------------------------------------------------

    if ($inventoryName === "") {
        $errors[] = "Inventory name is required.";
    }

    if ($category_id === "") {
        $errors[] = "Category ID is required.";
    }

    if ($inventoryStatus === "") {
        $errors[] = "Status is required.";
    }

    if ($inventoryQuantity === "") {
        $errors[] = "Quantity required.";
    }

    if ($category_id !== "" && $category_id < 1  || $category_id > 3) {
        $errors[] = "Category ID must be a whole number from 1 to 3.";
    }

    if ($inventoryQuantity !== "" && $inventoryQuantity < 0) {
        $errors[] = "Quantity must be a whole number.";
    }

    if (!in_array($inventoryStatus, $allowedAbility, true)) {
        $errors[] = "Please choose a valid inventory status.";
    }

    if (!in_array($inventoryCondition, $allowedCondition, true)) {
        $errors[] = "Please choose a condition.";
    }

    if (!in_array($inventoryTaggable, $isTaggable, true)) {
        $errors[] = "Please select Yes or No for taggable.";
    }

    if (!in_array($inventoryLoose, $isLoose, true)) {
        $errors[] = "Please select Yes or No for loose.";
    }

    // ---------------------------------------------------------------------
    // 4. WRITE THE SQL COMMAND
    // ---------------------------------------------------------------------

    /*
    | At this point, $sql is ONLY a PHP string.
    |
    | MySQL has not done anything yet.
    |
    | The ? symbols are placeholders. We will supply their values later.
    |
    | Prepared statements allow us to keep the SQL command separate from
    | the data being inserted into it.
    */

    if (count($errors) === 0) {

        /*
        | If your MySQL columns inv_taggable and inv_loose are TINYINT,
        | MySQL stores Yes as 1 and No as 0.
        |
        | The form still shows the user Yes/No.
        */

        $taggableValue = ($inventoryTaggable === "Yes") ? 1 : 0;
        $looseValue = ($inventoryLoose === "Yes") ? 1 : 0;

        $sql = "INSERT INTO inv
            (category_id, inv_name, inv_ability, inv_quantity, inv_condition, inv_taggable, inv_notes, inv_loose, inv_image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // ---------------------------------------------------------------------
        // 5. PREPARE THE SQL STATEMENT
        // ---------------------------------------------------------------------

        /*
        | $conn came from db.php.
        |
        | -> is PHP's object operator.
        |
        | You can read this line approximately as:
        |
        | "Use the prepare method belonging to $conn and give it $sql."
        |
        | prepare() sends the SQL structure to MySQL so MySQL can prepare
        | the command.
        |
        | The prepared statement is stored in $statement.
        */

        $statement = $conn->prepare($sql);

        if ($statement === false) {
            $errors[] = "The SQL statement could not be prepared.";
        } else {

            // ---------------------------------------------------------------------
            // 6. BIND PHP VARIABLES TO THE ? PLACEHOLDERS
            // ---------------------------------------------------------------------

            /*
            | bind_param() connects our PHP variables to the placeholders in SQL.
            |
            | The values are matched BY POSITION.
            |
            | First ?  → $category_id
            | Second ? → $inventoryName
            | Third ?  → $inventoryStatus
            | ...and so on.
            |
            | The first argument is the TYPE STRING.
            |
            | Common mysqli types:
            |
            | i = integer
            | d = decimal/double
            | s = string
            | b = binary/blob
            |
            | There must be one type letter for every variable being bound.
            */

            $categoryValue = $category_id;
            $quantityValue = $inventoryQuantity;

            $statement->bind_param(
                'isisisiss',
                $categoryValue,
                $inventoryName,
                $inventoryStatus,
                $quantityValue,
                $inventoryCondition,
                $taggableValue,
                $inventoryNotes,
                $looseValue,
                $inventoryImage
            );

            // ---------------------------------------------------------------------
            // 7. EXECUTE THE STATEMENT
            // ---------------------------------------------------------------------

            /*
            | prepare() prepared the SQL.
            | bind_param() attached the values.
            | execute() actually tells MySQL to run the statement.
            |
            | execute() returns true if it succeeds and false if it fails,
            | so we can use it directly as the condition of an IF statement.
            */

            if ($statement->execute()) {
                $message = 'Inventory item saved.';
            } else {
                $message = 'The inventory item could not be saved.';
                $errors[] = "Database error: " . $statement->error;
            }

            // ---------------------------------------------------------------------
            // 8. CLOSE THE PREPARED STATEMENT
            // ---------------------------------------------------------------------

            /*
            | We are finished using this prepared statement, so we close it.
            |
            | Notice again that -> means we are calling a method that belongs
            | to the $statement object.
            */

            $statement->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Training Application</title>
</head>
<body>

    <h1>Inventory Training Application</h1>

    <?php if ($message !== ''): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <h2>Add Inventory Item</h2>

    <p>
        This file intentionally does not build the form for you yet.
        Your job is to create the form section and make each field's
        <code>name</code> attribute match the <code>$_POST</code> keys
        used at the top of this file.
    </p>

    <h2>How db.php Fits In</h2>

    <p>
        The line below loads the database connection file:
    </p>

    <pre>require 'db.php';</pre>

    <p>
        Inside <code>db.php</code>, PHP creates a variable called
        <code>$conn</code>. That variable represents the open connection
        to MySQL.
    </p>

    <pre>$conn = new mysqli($host, $username, $password, $database);</pre>

    <p>
        After <code>require 'db.php';</code> runs, this file can use
        <code>$conn</code> to prepare and run SQL commands.
    </p>

    <h2>Using the Connection</h2>

    <p>
        This line uses the database connection to prepare the SQL command:
    </p>

    <pre>$statement = $conn-&gt;prepare($sql);</pre>

    <p>
        Plain English: use the <code>prepare()</code> method that belongs
        to the <code>$conn</code> database connection, and give it the
        SQL string stored in <code>$sql</code>.
    </p>

    <h2>Form Requirements</h2>

    <p>Create a form that submits back to this page using POST:</p>

    <pre>&lt;form method="post" action=""&gt;</pre>

    <p>Your form should include fields with these exact names:</p>

    <ul>
        <li><code>category_id</code> for the inventory category.</li>
        <li><code>inv_name</code> for the equipment name.</li>
        <li><code>inv_ability</code> for whether the item works.</li>
        <li><code>inv_quantity</code> for the item count.</li>
        <li><code>inv_condition</code> for the item's condition.</li>
        <li><code>inv_taggable</code> for whether it can receive an asset tag.</li>
        <li><code>inv_loose</code> for whether it is a loose item.</li>
        <li><code>inv_notes</code> for extra details.</li>
        <li><code>inv_image</code> for an image URL.</li>
    </ul>

    <h2>Data Path</h2>

    <p>Follow one value all the way through the file:</p>

    <ol>
        <li>The form field uses <code>name="inv_name"</code>.</li>
        <li>PHP receives that value as <code>$_POST['inv_name']</code>.</li>
        <li>The value is stored in <code>$inventoryName</code>.</li>
        <li><code>bind_param()</code> attaches <code>$inventoryName</code> to the second <code>?</code>.</li>
        <li><code>execute()</code> sends the prepared statement to MySQL.</li>
        <li>MySQL stores the value in the <code>inv_name</code> column.</li>
    </ol>

    <h2>Student Task</h2>

    <p>
        Build the missing form below this section. After the form is built,
        submit a test item and confirm that the success message appears.
    </p>

    <form method="POST" action="">

        <label for="category_id">Category ID #:</label>
        <input
            type="number"
            id="category_id"
            name="category_id"
            min="1"
            max="3"
            step="1"
            required
            value="<?php echo htmlspecialchars($category_id === "Not Submitted" ? "" : $category_id); ?>"
        >

        <br><br>

        <label for="inv_name">Item Name:</label>
        <input
            type="text"
            id="inv_name"
            name="inv_name"
            value="<?php echo htmlspecialchars($inventoryName === "Not Submitted" ? "" : $inventoryName); ?>"
        >

        <br><br>

        <label for="inv_ability">Working or not?:</label>

        <select id="inv_ability" name="inv_ability">
            <option value="">Choose a status</option>

            <option value="Not tested"
                <?php if ($inventoryStatus === "Not tested") { echo "selected"; } ?>>
                Not tested
            </option>

            <option value="Partially working"
                <?php if ($inventoryStatus === "Partially working") { echo "selected"; } ?>>
                Partially working
            </option>

            <option value="Working"
                <?php if ($inventoryStatus === "Working") { echo "selected"; } ?>>
                Working
            </option>

            <option value="Not working"
                <?php if ($inventoryStatus === "Not working") { echo "selected"; } ?>>
                Not working
            </option>
        </select>

        <br><br>

        <label for="inv_quantity">Quantity:</label>
        <input
            type="number"
            id="inv_quantity"
            name="inv_quantity"
            step="1"
            min="0"
            value="<?php echo htmlspecialchars($inventoryQuantity === "Not Submitted" ? "" : $inventoryQuantity); ?>"
        >

        <br><br>

        <label for="inv_condition">Item Condition:</label>

        <select id="inv_condition" name="inv_condition">
            <option value="">Choose a condition</option>

            <option value="Fair"
                <?php if ($inventoryCondition === "Fair") { echo "selected"; } ?>>
                Fair
            </option>

            <option value="Poor"
                <?php if ($inventoryCondition === "Poor") { echo "selected"; } ?>>
                Poor
            </option>

            <option value="Good"
                <?php if ($inventoryCondition === "Good") { echo "selected"; } ?>>
                Good
            </option>

            <option value="New"
                <?php if ($inventoryCondition === "New") { echo "selected"; } ?>>
                New
            </option>
        </select>

        <br><br>

        <label>Is it taggable?</label>
        <br>

        <input
            type="radio"
            name="inv_taggable"
            value="Yes"
            <?php if ($inventoryTaggable === "Yes") { echo "checked"; } ?>
        >
        Yes

        <br>

        <input
            type="radio"
            name="inv_taggable"
            value="No"
            <?php if ($inventoryTaggable === "No") { echo "checked"; } ?>
        >
        No

        <br><br>

        <label>Is it loose?</label>
        <br>

        <input
            type="radio"
            name="inv_loose"
            value="Yes"
            <?php if ($inventoryLoose === "Yes") { echo "checked"; } ?>
        >
        Yes

        <br>

        <input
            type="radio"
            name="inv_loose"
            value="No"
            <?php if ($inventoryLoose === "No") { echo "checked"; } ?>
        >
        No

        <br><br>

        <label for="inv_notes">Notes:</label>
        <br>

        <textarea
            id="inv_notes"
            name="inv_notes"
            rows="5"
            cols="50"
        ><?php echo htmlspecialchars($inventoryNotes === "None" ? "" : $inventoryNotes); ?></textarea>

        <br><br>

        <label for="inv_image">Image URL:</label>
        <input
            type="url"
            id="inv_image"
            name="inv_image"
            placeholder="https://example.com/image.jpg"
            value="<?php echo htmlspecialchars($inventoryImage === "Not Submitted" ? "" : $inventoryImage); ?>"
        >

        <br><br>

        <button type="submit">Add Item</button>

    </form>

    <h2>Submitted Values</h2>

    <p>Item Name: <?php echo htmlspecialchars($inventoryName); ?></p>

    <p>Category ID #: <?php echo htmlspecialchars($category_id); ?></p>

    <p>Working or not?: <?php echo htmlspecialchars($inventoryStatus); ?></p>

    <p>Quantity: <?php echo htmlspecialchars($inventoryQuantity); ?></p>

    <p>Condition: <?php echo htmlspecialchars($inventoryCondition); ?></p>

    <p>Taggable?: <?php echo htmlspecialchars($inventoryTaggable); ?></p>

    <p>Notes: <?php echo htmlspecialchars($inventoryNotes); ?></p>

    <p>Loose?: <?php echo htmlspecialchars($inventoryLoose); ?></p>

    <p>Image: <?php echo htmlspecialchars($inventoryImage); ?></p>

    <?php if (count($errors) > 0) { ?>

        <h3>Error Log:</h3>

        <ul>

            <?php foreach ($errors as $error) { ?>

                <li><?php echo htmlspecialchars($error); ?></li>

            <?php } ?>

        </ul>

    <?php } ?>

</body>
</html>
