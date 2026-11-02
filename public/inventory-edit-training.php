<?php
/*
 * INVENTORY EDIT — BEGINNER TRAINING
 *
 * WHAT YOU WILL LEARN
 * 1. Open an edit page for ONE inventory record using a GET parameter.
 * 2. SELECT that record using WHERE and a prepared statement.
 * 3. Fill an HTML form with the record's current values.
 * 4. Submit changes using POST and save them with UPDATE.
 * 5. Redirect back to the inventory table after saving.
 *
 * WORKFLOW:
 * inventory table -> Edit link (?id=...) -> edit form -> POST -> UPDATE -> table
 *
 *
 * BEFORE STARTING THE EXERCISE:
 * 
 * 1. Pull the latest changes from github to your local machine
 *
 *
 * 3. Right click in the project folder and select "Open in Integrated Terminal":
 *
 *    php -S localhost:8000 -t public
 *
 * 4. Open the training table first:
 *
 *    http://localhost:8000/inventory-table-basic-training.php
 *
 * The edit page needs an ?id= value, so it should be opened using the Edit
 * link the student adds to the training table. Keep PowerShell open while
 * testing. Press Ctrl+C to stop PHP.
 */

// STEP 1 — CONNECT TO MYSQL
// This file is in public/, while db.php is in config/.
// __DIR__ starts in public/ and /../ moves up to the project folder.
require __DIR__ . '/../config/db.php';

// ADVANCED SETUP — SKIP FOR NOW.
// The original inventory table lesson detects the primary-key column
// instead of assuming it is named "id". We use the same approach here.
// The session token protects the POST form. Leave this block unchanged.
session_start();
if (!isset($_SESSION['inventory_edit_token'])) {
    $_SESSION['inventory_edit_token'] = bin2hex(random_bytes(32));
}
$keyResult = $conn->query("SHOW KEYS FROM inv WHERE Key_name = 'PRIMARY'");
$keyRow = $keyResult ? $keyResult->fetch_assoc() : null;
$idColumn = $keyRow['Column_name'] ?? '';
if ($keyResult) {
    $keyResult->free();
}
if ($idColumn === '' || !preg_match('/^[A-Za-z0-9_]+$/', $idColumn)) {
    die('The inv table needs a primary key.');
}

// STEP 2 — IDENTIFY WHICH RECORD TO EDIT
// Clicking "Edit" on the inventory table will open a URL such as:
// inventory-edit-training.php?id=5
// $_GET['id'] reads the value after ?id= in that URL.
//
// The form below also sends the ID as a hidden POST field when saving.
// GET identifies the record to display; POST submits changes to save.
// This comparison creates a Boolean value: either true or false.
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

// The lines below line uses the ternary operator,
// condition ? value_if_true : value_if_false
//
// The ? means "if the condition is true, use the value after me."
// The : means "otherwise, use the value after me." It does NOT mean OR.
//
// If $isPost is true, get item_id from the submitted form ($_POST).
// Otherwise, get id from the page URL ($_GET).
// In both places, ?? '' means "use an empty string if this value is missing."
$itemId = $isPost
    ? ($_POST['item_id'] ?? '')
    : ($_GET['id'] ?? '');

// || is PHP's OR operator. The if block runs if EITHER condition is true:
// 1. !is_string($itemId) means the item ID is not a string.
// 2. $itemId === '' means the item ID is an empty string.
if (!is_string($itemId) || $itemId === '') {
    die('No inventory item was selected. Open this page using an Edit link.');
}

$errorMessage = '';

// STEP 3 — HANDLE A SAVE REQUEST (READ AFTER STEP 4)
// The browser sends POST when the user clicks "Save Changes".
// This block validates the new values, then updates ONLY the selected row.
if ($isPost) {
    // ADVANCED SECURITY CHECK — LEAVE UNCHANGED FOR NOW.
    $submittedToken = $_POST['edit_token'] ?? '';
    if (!is_string($submittedToken) ||
        !hash_equals($_SESSION['inventory_edit_token'], $submittedToken)) {
        die('Edit request could not be verified.');
    }

    // These names match the name="..." attributes in the HTML form below.
    $name = trim((string)($_POST['inv_name'] ?? ''));
    $quantity = $_POST['inv_quantity'] ?? '';
    $condition = trim((string)($_POST['inv_condition'] ?? ''));

    // You already know validation! Reuse it here before writing to MySQL.
    if ($name === '' || $condition === '') {
        $errorMessage = 'Item name and condition are required.';
    } elseif (filter_var($quantity, FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0]]) === false) {
        $errorMessage = 'Quantity must be a whole number of zero or more.';
    } else {
        // UPDATE changes existing data; SET names the columns to change.
        // WHERE limits the update to the record with this ID.
        // The ? placeholders are supplied by bind_param().
        $updateSql = "UPDATE inv
                      SET inv_name = ?, inv_quantity = ?, inv_condition = ?
                      WHERE `{$idColumn}` = ?
                      LIMIT 1";

        $updateStatement = $conn->prepare($updateSql);
        if (!$updateStatement) {
            die('Could not prepare the update query.');
        }

        // s = string, i = integer. The four letters match four ? placeholders.
        $quantityNumber = (int)$quantity;
        $updateStatement->bind_param('siss', $name, $quantityNumber, $condition, $itemId);

        if (!$updateStatement->execute()) {
            die('Could not update this inventory item.');
        }
        $updateStatement->close();

        // Redirect so a browser refresh doesn't submit the UPDATE again.
        header('Location: inventory-table-basic-training.php?updated=1');
        exit;
    }
}

// STEP 4 — SELECT ONE RECORD TO PRE-FILL THE FORM
// SELECT * retrieves the columns for the selected record.
// WHERE is essential: without it, the query would retrieve every item.
// A prepared statement keeps the submitted ID separate from the SQL.
$selectSql = "SELECT inv_name, inv_quantity, inv_condition
              FROM inv
              WHERE `{$idColumn}` = ?
              LIMIT 1";
$selectStatement = $conn->prepare($selectSql);
if (!$selectStatement) {
    die('Could not prepare the select query.');
}
$selectStatement->bind_param('s', $itemId);
if (!$selectStatement->execute()) {
    die('Could not load this inventory item.');
}
$result = $selectStatement->get_result();
$item = $result->fetch_assoc(); // One row as an associative array.
$selectStatement->close();

if (!$item) {
    die('That inventory item does not exist.');
}

// If validation failed, show the user's attempted values instead of
// resetting the form to the old database values.
if ($isPost && $errorMessage !== '') {
    $item['inv_name'] = $name;
    $item['inv_quantity'] = is_scalar($quantity) ? (string)$quantity : '';
    $item['inv_condition'] = $condition;
}

// Helper: safely display database/user text inside HTML attributes or text.
function displayValue($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Inventory — Training</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 650px; margin: 40px auto; padding: 0 16px; }
        label { display: block; font-weight: bold; margin: 16px 0 5px; }
        input { box-sizing: border-box; width: 100%; padding: 9px; }
        button { margin-top: 20px; padding: 9px 16px; cursor: pointer; }
        .error { color: #a00; }
    </style>
</head>
<body>
    <h1>Edit Inventory Item</h1>
    <p><a href="inventory-table-basic-training.php">Back to inventory table</a></p>

    <?php if ($errorMessage !== ''): ?>
        <p class="error"><?php echo displayValue($errorMessage); ?></p>
    <?php endif; ?>

    <!-- STEP 5 — BUILD A FORM FILLED WITH EXISTING DATA -->
    <!-- method="post" sends edited values back to THIS PHP file. -->
    <!-- value="..." inserts a PHP value into an HTML input. -->
    <form method="post" action="inventory-edit-training.php">
        <!-- Hidden ID: tells PHP which record to UPDATE when saving. -->
        <input type="hidden" name="item_id" value="<?php echo displayValue($itemId); ?>">

        <!-- ADVANCED SECURITY FIELD — LEAVE UNCHANGED FOR NOW. -->
        <input type="hidden" name="edit_token"
               value="<?php echo displayValue($_SESSION['inventory_edit_token']); ?>">

        <label for="inv_name">Item Name</label>
        <input id="inv_name" name="inv_name" type="text" required
               value="<?php echo displayValue($item['inv_name']); ?>">

        <label for="inv_quantity">Quantity</label>
        <input id="inv_quantity" name="inv_quantity" type="number" min="0" step="1" required
               value="<?php echo displayValue($item['inv_quantity']); ?>">

        <label for="inv_condition">Condition</label>
        <input id="inv_condition" name="inv_condition" type="text" required
               value="<?php echo displayValue($item['inv_condition']); ?>">

        <button type="submit">Save Changes</button>
    </form>

    <!-- PRACTICE QUESTIONS
         1. Why does the Edit link use GET while Save Changes uses POST?
         2. What does WHERE do in the SELECT query? In the UPDATE query?
         3. Why do we set the item_id fiel as a hidden input?
         4. How does the input's value attribute display existing data?
         5. What would happen if the UPDATE statement had no WHERE clause?
            (Explain only — NEVER test that on your database.)
         6. Why do we redirect after a successful POST?

         CHALLENGE:
         Add an Edit link to each row in inventory-table-basic-training.php.
         Use that row's item_id in the URL:
         inventory-edit-training.php?id=THE_ITEM_ID
         Use displayValue()/htmlspecialchars() when outputting the ID.
         After saving, add a success message to the table page when
         $_GET['updated'] is set (similar to its existing deleted message).
    -->
</body>
</html>
<?php $conn->close(); ?>
