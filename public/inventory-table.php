<?php

require 'db.php';

$inventoryItems = [];
$errorMessage = '';

$sql = "SELECT category_id, inv_name, inv_ability, inv_quantity, inv_condition,
        inv_taggable, inv_notes, inv_loose, inv_image
    FROM inv
    ORDER BY inv_name ASC";

$result = $conn->query($sql);

if ($result === false) {
    $errorMessage = 'The inventory could not be loaded.';
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

        <?php if ($errorMessage !== ''): ?>
            <p class="error-message"><?php echo displayValue($errorMessage); ?></p>
        <?php elseif (count($inventoryItems) === 0): ?>
            <p>No inventory items have been collected yet.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
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
                                        <img
                                            class="inventory-image"
                                            src="<?php echo displayValue($item['inv_image']); ?>"
                                            alt="<?php echo displayValue($item['inv_name']); ?>"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        No image
                                    <?php endif; ?>
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
