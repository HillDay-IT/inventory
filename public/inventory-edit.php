<?php

session_start();

require __DIR__ . '/../config/db.php';

if (!isset($_SESSION['inventory_csrf_token'])) {
    $_SESSION['inventory_csrf_token'] = bin2hex(random_bytes(32));
}

$allowedAbilities = ['Working', 'Not tested', 'Not working', 'Partially working'];
$allowedConditions = ['Fair', 'Poor', 'Good', 'New'];
$errors = [];
$inventoryItem = null;
$rowId = $_POST['row_id'] ?? $_GET['id'] ?? '';
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

if ($primaryKey === '' || $rowId === '') {
    $errors[] = 'The inventory item could not be identified.';
} else {
    $selectSql = "SELECT category_id, inv_name, inv_ability, inv_quantity, inv_condition,
            inv_taggable, inv_notes, inv_loose, inv_image
        FROM inv
        WHERE `{$primaryKey}` = ?
        LIMIT 1";
    $selectStatement = $conn->prepare($selectSql);

    if ($selectStatement === false) {
        $errors[] = 'The inventory item could not be loaded.';
    } else {
        $selectStatement->bind_param('s', $rowId);
        $selectStatement->execute();
        $result = $selectStatement->get_result();
        $inventoryItem = $result->fetch_assoc();
        $selectStatement->close();

        if ($inventoryItem === null) {
            $errors[] = 'The inventory item was not found.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $inventoryItem !== null) {
    $categoryId = trim($_POST['category_id'] ?? '');
    $inventoryName = trim($_POST['inv_name'] ?? '');
    $inventoryAbility = $_POST['inv_ability'] ?? '';
    $inventoryQuantity = trim($_POST['inv_quantity'] ?? '');
    $inventoryCondition = $_POST['inv_condition'] ?? '';
    $inventoryTaggable = $_POST['inv_taggable'] ?? '';
    $inventoryNotes = trim($_POST['inv_notes'] ?? '');
    $inventoryLoose = $_POST['inv_loose'] ?? '';
    $inventoryImage = trim($_POST['inv_image'] ?? '');

    $inventoryItem = [
        'category_id' => $categoryId,
        'inv_name' => $inventoryName,
        'inv_ability' => $inventoryAbility,
        'inv_quantity' => $inventoryQuantity,
        'inv_condition' => $inventoryCondition,
        'inv_taggable' => $inventoryTaggable === 'Yes' ? 1 : 0,
        'inv_notes' => $inventoryNotes,
        'inv_loose' => $inventoryLoose === 'Yes' ? 1 : 0,
        'inv_image' => $inventoryImage,
    ];

    if (!hash_equals($_SESSION['inventory_csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'The update request could not be verified.';
    }

    if (!ctype_digit($categoryId) || (int)$categoryId < 1 || (int)$categoryId > 3) {
        $errors[] = 'Category ID must be a whole number from 1 to 3.';
    }

    if ($inventoryName === '') {
        $errors[] = 'Inventory name is required.';
    }

    if (!in_array($inventoryAbility, $allowedAbilities, true)) {
        $errors[] = 'Please choose a valid inventory status.';
    }

    if (!ctype_digit($inventoryQuantity)) {
        $errors[] = 'Quantity must be a whole number.';
    }

    if (!in_array($inventoryCondition, $allowedConditions, true)) {
        $errors[] = 'Please choose a condition.';
    }

    if (!in_array($inventoryTaggable, ['Yes', 'No'], true)) {
        $errors[] = 'Please select Yes or No for taggable.';
    }

    if (!in_array($inventoryLoose, ['Yes', 'No'], true)) {
        $errors[] = 'Please select Yes or No for loose.';
    }

    if ($inventoryImage !== '' && filter_var($inventoryImage, FILTER_VALIDATE_URL) === false) {
        $errors[] = 'Please enter a valid image URL.';
    }

    if (count($errors) === 0) {
        $taggableValue = $inventoryTaggable === 'Yes' ? 1 : 0;
        $looseValue = $inventoryLoose === 'Yes' ? 1 : 0;
        $categoryValue = (int)$categoryId;
        $quantityValue = (int)$inventoryQuantity;
        $updateSql = "UPDATE inv SET
                category_id = ?, inv_name = ?, inv_ability = ?, inv_quantity = ?,
                inv_condition = ?, inv_taggable = ?, inv_notes = ?, inv_loose = ?, inv_image = ?
            WHERE `{$primaryKey}` = ?
            LIMIT 1";
        $updateStatement = $conn->prepare($updateSql);

        if ($updateStatement === false) {
            $errors[] = 'The update request could not be prepared.';
        } else {
            $updateStatement->bind_param(
                'issisisiss',
                $categoryValue,
                $inventoryName,
                $inventoryAbility,
                $quantityValue,
                $inventoryCondition,
                $taggableValue,
                $inventoryNotes,
                $looseValue,
                $inventoryImage,
                $rowId
            );

            if ($updateStatement->execute()) {
                $updateStatement->close();
                header('Location: inventory-table.php?updated=1');
                exit;
            }

            $errors[] = 'The inventory item could not be updated.';
            $updateStatement->close();
        }
    }
}

function editValue($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Inventory Item</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="page-header">
        <h1>Update Inventory Item</h1>
    </header>

    <main class="content-box">
        <?php if (count($errors) > 0): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo editValue($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($inventoryItem !== null): ?>
            <form method="post" action="inventory-edit.php">
                <input type="hidden" name="row_id" value="<?php echo editValue($rowId); ?>">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo editValue($_SESSION['inventory_csrf_token']); ?>"
                >

                <div class="typing">
                    <label for="category_id">Category ID #:</label>
                    <input type="number" id="category_id" name="category_id" min="1" max="3" required
                        value="<?php echo editValue($inventoryItem['category_id']); ?>">
                </div>

                <div class="typing">
                    <label for="inv_name">Item Name:</label>
                    <input type="text" id="inv_name" name="inv_name" required
                        value="<?php echo editValue($inventoryItem['inv_name']); ?>">
                </div>

                <div class="select edit-field">
                    <label for="inv_ability">Ability:</label>
                    <select id="inv_ability" name="inv_ability" required>
                        <?php foreach ($allowedAbilities as $ability): ?>
                            <option value="<?php echo editValue($ability); ?>"
                                <?php echo $inventoryItem['inv_ability'] === $ability ? 'selected' : ''; ?>>
                                <?php echo editValue($ability); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="typing">
                    <label for="inv_quantity">Quantity:</label>
                    <input type="number" id="inv_quantity" name="inv_quantity" min="0" required
                        value="<?php echo editValue($inventoryItem['inv_quantity']); ?>">
                </div>

                <div class="select edit-field">
                    <label for="inv_condition">Condition:</label>
                    <select id="inv_condition" name="inv_condition" required>
                        <?php foreach ($allowedConditions as $condition): ?>
                            <option value="<?php echo editValue($condition); ?>"
                                <?php echo $inventoryItem['inv_condition'] === $condition ? 'selected' : ''; ?>>
                                <?php echo editValue($condition); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <fieldset class="choice-group">
                    <legend>Is it taggable?</legend>
                    <label><input type="radio" name="inv_taggable" value="Yes"
                        <?php echo (string)$inventoryItem['inv_taggable'] === '1' ? 'checked' : ''; ?>> Yes</label>
                    <label><input type="radio" name="inv_taggable" value="No"
                        <?php echo (string)$inventoryItem['inv_taggable'] !== '1' ? 'checked' : ''; ?>> No</label>
                </fieldset>

                <fieldset class="choice-group">
                    <legend>Is it loose?</legend>
                    <label><input type="radio" name="inv_loose" value="Yes"
                        <?php echo (string)$inventoryItem['inv_loose'] === '1' ? 'checked' : ''; ?>> Yes</label>
                    <label><input type="radio" name="inv_loose" value="No"
                        <?php echo (string)$inventoryItem['inv_loose'] !== '1' ? 'checked' : ''; ?>> No</label>
                </fieldset>

                <div class="typing">
                    <label for="inv_notes">Notes:</label>
                    <textarea id="inv_notes" name="inv_notes" rows="5"><?php echo editValue($inventoryItem['inv_notes']); ?></textarea>
                </div>

                <div class="typing">
                    <label for="inv_image">Image URL:</label>
                    <input type="url" id="inv_image" name="inv_image"
                        value="<?php echo editValue($inventoryItem['inv_image']); ?>">
                </div>

                <div class="form-actions">
                    <button type="submit">Save Changes</button>
                    <a class="cancel-link" href="inventory-table.php">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <a class="page-link" href="inventory-table.php">Return to Inventory</a>
        <?php endif; ?>
    </main>
</body>
</html>
