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
$identifier = [
    "1(Camera)",
    "2(AV)",
    "3(IT)"
];

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

    if ($inventoryStatus === "") {
        $errors[] = "Status is required.";
    }

    if ($inventoryQuantity === "") {
        $errors[] = "Quantity required.";
    }

    if ($inventoryQuantity !== "" && (!ctype_digit((string)$inventoryQuantity) || (int)$inventoryQuantity < 0)) {
        $errors[] = "Quantity must be a whole number.";
    }
$allowedCategoryIds = ["1", "2", "3"];

if (!in_array($category_id, $allowedCategoryIds, true)) {
    $errors[] = "Please choose a valid category.";
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

            $categoryValue = (int)$category_id;
            $quantityValue = (int)$inventoryQuantity;

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
                $successMessage = "The inventory item entry is valid and ready to be processed.";
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
    <title>Inventory Application</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="page-header">
    <h1>Inventory Application</h1>
</header>
   <?php if ($message !== ''): ?>
    <div class="message-box">
        <p><?php echo htmlspecialchars($message); ?></p>
    </div>
<?php endif; ?>

    

    <div class="content-box">
    <h2>Enter Inventory Item</h2>

    <form method="POST" action="">

<div class="select">
    <label for="category_id">Category ID #:</label>

    <select id="category_id" name="category_id" required>
        <option value="">Choose a category</option>

        <option value="1"
            <?php if ($category_id === "1") echo "selected"; ?>>
            1 (Camera)
        </option>

        <option value="2"
            <?php if ($category_id === "2") echo "selected"; ?>>
            2 (AV)
        </option>

        <option value="3"
            <?php if ($category_id === "3") echo "selected"; ?>>
            3 (IT)
        </option>
    </select>
</div>

        <br><br>
<div class="typing">
        <label for="inv_name">Item Name:</label>
        <input
            type="text"
            id="inv_name"
            name="inv_name"
             class="typing"
            value="<?php echo htmlspecialchars($inventoryName === "Not Submitted" ? "" : $inventoryName); ?>"
        >
</div>
        <br><br>
<div class="select">
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
    </div>
        <br><br>
<div class="typing">
        <label for="inv_quantity">Quantity:</label>
        <input
            type="number"
            id="inv_quantity"
            name="inv_quantity"
            step="1"
            min="0"
             class="typing"
            value="<?php echo htmlspecialchars($inventoryQuantity === "Not Submitted" ? "" : $inventoryQuantity); ?>"
        >
</div>
        <br><br>
<div class="select">
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
    </div>
        <br><br>
<div class="drop">
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
    </div>
        <br><br>
<div class="drop">
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
    </div>
        <br><br>
<div class="typing">
        <label for="inv_notes">Notes:</label>
        <br>

        <textarea
            id="inv_notes"
            name="inv_notes"
            rows="5"
            cols="50"
             class="typing"
        ><?php echo htmlspecialchars($inventoryNotes === "None" ? "" : $inventoryNotes); ?></textarea>
</div>
        <br><br>
<div class="typing">
        <label for="inv_image">Image URL:</label>
        <input
            type="url"
            id="inv_image"
            name="inv_image"
             class="typing"
            placeholder="https://example.com/image.jpg"
            value="<?php echo htmlspecialchars($inventoryImage === "Not Submitted" ? "" : $inventoryImage); ?>"
        >
</div>
        <br><br>

        <button type="submit">Add Item</button>

      </form>
</div>

<div class="content-box submitted-box">

    <h2>Submitted Values</h2>

    <p>Item Name: <?php echo htmlspecialchars($inventoryName); ?></p>

    <p>Category ID #: <?php echo htmlspecialchars($category_id); ?></p>

    <p>Ability: <?php echo htmlspecialchars($inventoryStatus); ?></p>

    <p>Quantity: <?php echo htmlspecialchars((string)$inventoryQuantity); ?></p>

    <p>Condition: <?php echo htmlspecialchars($inventoryCondition); ?></p>

    <p>Taggability:<?php echo htmlspecialchars($inventoryTaggable); ?></p>

    <p>Notes: <?php echo htmlspecialchars($inventoryNotes); ?></p>

    <p>Loose: <?php echo htmlspecialchars($inventoryLoose); ?></p>

    
<p>Image:</p>

<?php if (
    filter_var($inventoryImage, FILTER_VALIDATE_URL) &&
    in_array(parse_url($inventoryImage, PHP_URL_SCHEME), ['http', 'https'], true)
) { ?>
    <img
        src="<?php echo htmlspecialchars($inventoryImage); ?>"
        alt="Inventory item"
        width="200"
    >
<?php } else { ?>
    <p>No valid image URL provided.</p>
<?php } ?>

   </div>
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
