<?php

session_start();

require 'db.php';

$inventoryItems = [];
$errorMessage = '';
$statusMessage = '';

if (!isset($_SESSION['inventory_csrf_token'])) {
    $_SESSION['inventory_csrf_token'] = bin2hex(random_bytes(32));
}

$primaryKey = '';
$keyResult = $conn->query("SHOW KEYS FROM inv WHERE Key_name = 'PRIMARY'");

if ($keyResult !== false && ($keyRow = $keyResult->fetch_assoc())) {
    $candidateKey = $keyRow['Column_name'] ?? '';

    if (preg_match('/^[A-Za-z0-9_]+$/', $candidateKey)) {
        $primaryKey = $candidateKey;
    }
}

if ($keyResult !== false) {
    $keyResult->free();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $rowId = $_POST['row_id'] ?? '';

    if (!hash_equals($_SESSION['inventory_csrf_token'], $submittedToken)) {
        $errorMessage = 'The delete request could not be verified.';
    } elseif ($primaryKey === '' || $rowId === '') {
        $errorMessage = 'The inventory item could not be identified.';
    } else {
        $deleteSql = "DELETE FROM inv WHERE `{$primaryKey}` = ? LIMIT 1";
        $deleteStatement = $conn->prepare($deleteSql);

        if ($deleteStatement === false) {
            $errorMessage = 'The delete request could not be prepared.';
        } else {
            $deleteStatement->bind_param('s', $rowId);

            if ($deleteStatement->execute()) {
                $deleteStatement->close();
                header('Location: inventory-table.php?deleted=1');
                exit;
            }

            $errorMessage = 'The inventory item could not be deleted.';
            $deleteStatement->close();
        }
    }
}

if (isset($_GET['deleted'])) {
    $statusMessage = 'Inventory item deleted.';
} elseif (isset($_GET['updated'])) {
    $statusMessage = 'Inventory item updated.';
}

$sql = $primaryKey === ''
    ? ''
    : "SELECT `{$primaryKey}` AS row_id, category_id, inv_name, inv_ability, inv_quantity,
            inv_condition, inv_taggable, inv_notes, inv_loose, inv_image
        FROM inv
        ORDER BY inv_name ASC";

$result = $sql === '' ? false : $conn->query($sql);

if ($result === false) {
    if ($errorMessage === '') {
        $errorMessage = 'The inventory could not be loaded because the table needs a primary key.';
    }
} else {
    while ($row = $result->fetch_assoc()) {
        $inventoryItems[] = $row;
    }

    $result->free();
}

function displayValue($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function displayBoolean($value)
{
    return (string)$value === '1' ? 'Yes' : 'No';
}

function isValidImageUrl($url)
{
    return filter_var($url, FILTER_VALIDATE_URL)
        && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Table</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="page-header">
        <h1>Inventory Table</h1>
    </header>

    <main class="content-box inventory-list">
        <div class="table-heading">
            <h2>Collected Inventory</h2>
            <a class="page-link" href="inventory_training_fixed.php">Add Inventory Item</a>
        </div>

        <?php if ($statusMessage !== ''): ?>
            <p class="status-message"><?php echo displayValue($statusMessage); ?></p>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <p class="error-message"><?php echo displayValue($errorMessage); ?></p>
        <?php elseif (count($inventoryItems) === 0): ?>
            <p>No inventory items have been collected yet.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="table table-striped table-bordered align-middle w-100 inventory-table">
                    <colgroup>
                        <col class="column-category">
                        <col class="column-name">
                        <col class="column-ability">
                        <col class="column-quantity">
                        <col class="column-condition">
                        <col class="column-taggable">
                        <col class="column-loose">
                        <col class="column-notes">
                        <col class="column-image">
                        <col class="column-actions">
                    </colgroup>
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">Category</th>
                            <th scope="col">Item Name</th>
                            <th scope="col">Ability</th>
                            <th scope="col">Quantity</th>
                            <th scope="col">Condition</th>
                            <th scope="col">Taggable</th>
                            <th scope="col">Loose</th>
                            <th scope="col">Notes</th>
                            <th scope="col">Image</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventoryItems as $item): ?>
                            <tr>
                                <td><?php echo displayValue($item['category_id']); ?></td>
                                <td><?php echo displayValue($item['inv_name']); ?></td>
                                <td><?php echo displayValue($item['inv_ability']); ?></td>
                                <td><?php echo displayValue($item['inv_quantity']); ?></td>
                                <td><?php echo displayValue($item['inv_condition']); ?></td>
                                <td><?php echo displayBoolean($item['inv_taggable']); ?></td>
                                <td><?php echo displayBoolean($item['inv_loose']); ?></td>
                                <td class="notes-cell"><?php echo nl2br(displayValue($item['inv_notes'])); ?></td>
                                <td>
                                    <?php if (isValidImageUrl($item['inv_image'])): ?>
                                        <button
                                            class="btn btn-sm btn-primary inventory-action-button"
                                            type="button"
                                            data-image-url="<?php echo displayValue($item['inv_image']); ?>"
                                            onclick="window.open(this.dataset.imageUrl, '_blank', 'noopener,noreferrer');"
                                        >View Image</button>
                                    <?php else: ?>
                                        No image
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <form class="action-form" method="get" action="inventory-edit.php">
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?php echo displayValue($item['row_id']); ?>"
                                            >
                                            <button
                                                class="btn btn-sm btn-secondary inventory-action-button"
                                                type="submit"
                                            >Update</button>
                                        </form>
                                        <form
                                            class="delete-form"
                                            method="post"
                                            action="inventory-table.php"
                                            onsubmit="return confirm('Delete this inventory item?');"
                                        >
                                            <input type="hidden" name="action" value="delete">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo displayValue($_SESSION['inventory_csrf_token']); ?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="row_id"
                                                value="<?php echo displayValue($item['row_id']); ?>"
                                            >
                                            <button
                                                class="btn btn-sm btn-danger inventory-action-button"
                                                type="submit"
                                            >Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
