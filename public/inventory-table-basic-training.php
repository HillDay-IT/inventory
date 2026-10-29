<?php
/*
 * INVENTORY TABLE — BEGINNER TRAINING
 *
 * GOAL 1: Read records from the 'inv' database table and display them in HTML.
 * GOAL 2: Give every row a Delete button that deletes that specific record.
 *
 * FOLLOW THE NUMBERED STEPS BELOW.
 *
 * PROJECT LOCATION:
 *
 * BEFORE STARTING THE EXERCISE:
 *
 * 1. Start PHP from the inventory project folder (right click inventory project folder and open in integrated terminal):
 *
 *    php -S localhost:8000 -t public
 *
 * 2. Open this page in a browser:
 *
 *    http://localhost:8000/inventory-table-basic-training.php
 *
 * Keep the PowerShell window open while testing. Press Ctrl+C to stop PHP.
 */

// STEP 1 — CONNECT TO MYSQL
// This file belongs in public/, while db.php belongs in config/.
// __DIR__ means "the folder containing this training file."
// /../ moves up from public/ to inventory/, then config/db.php goes into config/.
require __DIR__ . '/../config/db.php';

// STEP 2 — IDENTIFY EACH RECORD
// Every row needs a unique ID so Delete knows which record to remove.
// This small setup block finds the primary-key column in your existing 'inv' table.
// You do not need to study SHOW KEYS yet; focus on what $idColumn is used for.
$keyResult = $conn->query("SHOW KEYS FROM inv WHERE Key_name = 'PRIMARY'");
$keyRow = $keyResult ? $keyResult->fetch_assoc() : null;
$idColumn = $keyRow['Column_name'] ?? '';
if ($keyResult) {
    $keyResult->free();
}
if ($idColumn === '' || !preg_match('/^[A-Za-z0-9_]+$/', $idColumn)) {
    die('The inv table needs a primary key before items can be deleted.');
}

// STEP 3 — HANDLE THE DELETE BUTTON (READ THIS AFTER BUILDING THE TABLE)
// When someone clicks Delete, its form sends a POST request to this page.
// $_POST['item_id'] contains the ID of the row whose button was clicked.
// We use a prepared statement so the submitted ID is treated as data.
// SECURITY BOILERPLATE: The token check helps prevent unauthorized form submissions.
// Leave this token code in place; we will explain it in a later lesson.
session_start();
if (!isset($_SESSION['inventory_delete_token'])) {
    $_SESSION['inventory_delete_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $token = $_POST['delete_token'] ?? '';
    $itemId = $_POST['item_id'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['inventory_delete_token'], $token)) {
        die('Delete request could not be verified.');
    }

    if (!is_string($itemId) || $itemId === '') {
        die('No inventory item was selected.');
    }

    // DELETE FROM chooses the table; WHERE chooses ONE matching record.
    // The ? placeholder is filled by bind_param() below.
    $deleteSql = "DELETE FROM inv WHERE `{$idColumn}` = ? LIMIT 1";
    $deleteStatement = $conn->prepare($deleteSql);
    if (!$deleteStatement) {
        die('Could not prepare the delete query.');
    }

    $deleteStatement->bind_param('s', $itemId);
    if (!$deleteStatement->execute()) {
        die('Could not delete the inventory item.');
    }
    $deleteStatement->close();

    // Redirect after POST so refreshing does not resubmit the delete request.
    header('Location: inventory-table-basic-training.php?deleted=1');
    exit;
}

// STEP 4 — RETRIEVE THE INVENTORY RECORDS
// SELECT chooses the columns we want to display.
// FROM inv identifies the table. ORDER BY sorts names alphabetically.
// AS item_id gives the primary-key column a simple name in our PHP code.
$sql = "SELECT `{$idColumn}` AS item_id, inv_name, inv_quantity, inv_condition
        FROM inv
        ORDER BY inv_name ASC";

// query() sends the SQL to MySQL and returns a result object.
$result = $conn->query($sql);
if ($result === false) {
    die('Could not load inventory records.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Table Training</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #eee; }
        button { cursor: pointer; padding: 6px 12px; }
    </style>
</head>
<body>
    <h1>Inventory Items</h1>
    <p><a href="inventory-form.php">Add an inventory item</a></p>

    <?php if (isset($_GET['deleted'])): ?>
        <p>Inventory item deleted.</p>
    <?php endif; ?>

    <!-- STEP 5 — CREATE THE HTML TABLE -->
    <!-- table contains the whole table; thead holds headings; tbody holds data. -->
    <!-- tr = table row, th = heading cell, td = data cell. -->
    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Condition</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <!-- STEP 6 — TURN MYSQL RECORDS INTO HTML ROWS -->
            <!-- fetch_assoc() reads the next record as an associative array. -->
            <!-- The while loop repeats the <tr> below for every record. -->
            <?php while ($item = $result->fetch_assoc()): ?>
                <tr>
                    <!-- Array keys match the column names in our SELECT query. -->
                    <!-- htmlspecialchars() displays database text safely in HTML. -->
                    <td><?php echo htmlspecialchars((string)$item['inv_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string)$item['inv_quantity'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string)$item['inv_condition'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <!-- STEP 7 — GIVE EACH RECORD ITS OWN DELETE BUTTON -->
                        <!-- This form uses POST because deleting changes database data. -->
                        <!-- The hidden item_id sends THIS row's unique ID to PHP. -->
                        <form method="post" action="inventory-table-basic-training.php"
                              onsubmit="return confirm('Delete this inventory item?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="item_id"
                                   value="<?php echo htmlspecialchars((string)$item['item_id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <!-- SECURITY BOILERPLATE: Keep this hidden token field unchanged. -->
                            <input type="hidden" name="delete_token"
                                   value="<?php echo htmlspecialchars($_SESSION['inventory_delete_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- PRACTICE QUESTIONS:
         1. What happens if you change ORDER BY inv_name ASC to DESC?
         2. Why do we need a while loop instead of writing one <tr> manually?
         3. How does each Delete button know which database row to delete?
         4. Why is the Delete form method="post" instead of method="get"?
         5. What would happen if you removed WHERE from the DELETE query?
            (Do not test question 5 on a real database!)
    -->
</body>
</html>
<?php
// Free the query result and close the connection after displaying the table.
$result->free();
$conn->close();
?>
