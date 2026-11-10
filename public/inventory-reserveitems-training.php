<?php

/*
|--------------------------------------------------------------------------
| RESERVATION INVENTORY SELECTION — STARTER
|--------------------------------------------------------------------------
|
| The previous page will eventually redirect here with a reservation ID:
|
| inventory-reserveitems-training.php?reservation_id=25
|
| This starter works with inventory-reserveform.php. That first page collects
| reservation details and sends the new reservation ID here. This page then
| displays its own item-selection form using inventory that already exists.
|
| It does not use inventory-form.php. That separate page creates new inventory
| records and is not part of the reservation workflow.
|
*/

require __DIR__ . '/../config/db.php';

$errors = [];
$inventoryItems = [];

/*
| GET supplies the ID when this page first opens. Later, a hidden form field
| will carry the same value through POST when the selection form is submitted.
*/
$reservationId = $_POST['reservation_id'] ?? $_GET['reservation_id'] ?? '';

if (!ctype_digit((string)$reservationId) || (int)$reservationId < 1) {
    $errors[] = 'A valid reservation ID was not provided.';
}

/*
| ADVANCED SETUP — PROVIDED FOR THIS EXERCISE
|
| This block asks MySQL for the primary-key column used by inv. The student
| does not need to recreate this block yet.
*/
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

/*
| INVENTORY QUERY — PROVIDED EXAMPLE
|
| This is the only completed database query on the page. It reads inventory
| and uses the subcategory table to include a readable subcategory name.
*/
if ($primaryKey === '') {
    $errors[] = 'The inventory table needs a primary key.';
} else {
    $inventorySql = "SELECT
            inv.`{$primaryKey}` AS item_id,
            inv.inv_name,
            inv.inv_quantity,
            COALESCE(s.subcat_name, 'Other') AS subcategory_name
        FROM inv
        LEFT JOIN inventory_subcat AS s
            ON inv.sub_id = s.id
            AND inv.category_id = s.invcat_id
        WHERE inv.inv_quantity > 0
        ORDER BY subcategory_name ASC, inv.inv_name ASC";

    $inventoryResult = $conn->query($inventorySql);

    if ($inventoryResult === false) {
        $errors[] = 'The inventory list could not be loaded.';
    } else {
        /*
        | fetch_assoc() reads one MySQL row and turns it into a PHP
        | associative array. Its keys match the selected column names:
        |
        | [
        |     'item_id' => 15,
        |     'inv_name' => 'Camera Tripod',
        |     'inv_quantity' => 3,
        |     'subcategory_name' => 'Accessories'
        | ]
        |
        | Each pass gets the next row. When no rows remain, fetch_assoc()
        | returns null and the while loop stops.
        */
        while ($item = $inventoryResult->fetch_assoc()) {
            $inventoryItems[] = $item;
        }

        $inventoryResult->free();
    }
}

/*
| STUDENT WORK AREA: RECEIVE THIS PAGE'S ITEM SELECTION
|
| The checkbox and quantity fields near the bottom of THIS page submit back
| to this same PHP file. When the request method is POST:
|
| - Read the selected item IDs from $_POST.
| - Read the quantity values from $_POST.
| - Confirm both values are arrays before looping over them.
| - Make sure at least one item was selected.
| - Validate each selected ID and quantity against $inventoryItems.
|
| Leave database saving for a later step. First display the submitted values
| and confirm that each selected item is paired with the correct quantity.
*/
$selectionItems = [];
$postedSelectedItems = [];
$postedQuantities = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedSelectedItems = $_POST['selected_items'] ?? [];

   
    $postedQuantities = $_POST['quantities'] ?? [];

    if (!is_array($postedSelectedItems)) {
        $errors[] = "Invalid inventory selection.";
        $postedSelectedItems = [];
    }

    if (!is_array($postedQuantities)) {
        $errors[] = "Invalid quantity information.";
        $postedQuantities = [];
    }


    if (count($postedSelectedItems) === 0) {
        $errors[] = "Please select at least one inventory item.";
    }

    $availableItems = [];

    foreach ($inventoryItems as $item) {
        $availableItems[(string)$item['item_id']] = $item;
    }

    $processedIds = [];

    foreach ($postedSelectedItems as $itemId) {

        if (
            !is_scalar($itemId) ||
            !ctype_digit((string)$itemId) ||
            (int)$itemId < 1
        ) {
            $errors[] = "An invalid inventory ID was submitted.";
            continue;
        }

        $itemId = (string)(int)$itemId;

    
        if (isset($processedIds[$itemId])) {
            $errors[] = "A duplicate inventory item was submitted.";
            continue;
        }

        $processedIds[$itemId] = true;

        if (!isset($availableItems[$itemId])) {
            $errors[] = "A selected inventory item is unavailable.";
            continue;
        }

        $quantity = $postedQuantities[$itemId] ?? null;

        if (
            !is_scalar($quantity) ||
            filter_var($quantity, FILTER_VALIDATE_INT) === false ||
            (int)$quantity < 1
        ) {
            $errors[] = "Please enter a valid quantity.";
            continue;
        }

        $quantity = (int)$quantity;

        $availableQuantity = (int)$availableItems[$itemId]['inv_quantity'];

        if ($quantity > $availableQuantity) {
            $errors[] = "Requested quantity exceeds available inventory for "
                . $availableItems[$itemId]['inv_name'] . ".";
            continue;
        }

        $selectionItems[] = [
            'item_id' => (int)$itemId,
            'inv_name' => $availableItems[$itemId]['inv_name'],
            'quantity' => $quantity,
            'available' => $availableQuantity
        ];
    }
}



$orginventory = [];

foreach ($inventoryItems as $item) {

    $subcategory = $item['subcategory_name'];

    $orgInventory[$subcategory][] = $item;

}
function reserveItemValue($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Reservation Inventory</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .reservation-summary {
            margin-bottom: 24px;
            padding: 16px;
            border-radius: 6px;
            background: #eef3ff;
        }

        .inventory-group {
            margin-bottom: 18px;
            overflow: hidden;
            border: 1px solid #c7c7c7;
            border-radius: 6px;
        }

        .inventory-group summary {
            padding: 14px 16px;
            cursor: pointer;
            font-weight: bold;
            background: #edf1f8;
        }

        .inventory-choice {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) 120px;
            gap: 14px;
            align-items: center;
            padding: 14px 16px;
            border-top: 1px solid #ddd;
        }

        .inventory-name,
        .inventory-quantity {
            display: block;
        }

        .inventory-name {
            font-weight: bold;
        }

        .inventory-quantity {
            margin-top: 3px;
            color: #555;
            font-size: 0.9rem;
        }

        .quantity-field {
            width: 100%;
            box-sizing: border-box;
            padding: 8px;
        }

        .student-work-area {
            min-height: 90px;
            margin: 18px 0;
            padding: 16px;
            border: 2px dashed #9aa7bd;
            border-radius: 6px;
            color: #4c5668;
            background: #fafbfe;
        }

        @media (max-width: 600px) {
            .inventory-choice {
                grid-template-columns: auto 1fr;
            }

            .quantity-field {
                grid-column: 2;
            }
        }
    </style>
</head>
<body>

<header class="page-header">
    <p>HillDay PR</p>
    <h1>Select Reservation Inventory</h1>
</header>

<main class="content-box">
    <section class="reservation-summary">
        <h2>Reservation #<?php echo reserveItemValue($reservationId !== '' ? $reservationId : 'Not provided'); ?></h2>
        <p>
            The previous page supplies this ID. The hidden field below keeps it
            available when this form is submitted.
        </p>
    </section>

    <?php if (count($errors) > 0): ?>
        <div class="error-message">
            <h2>Check the following:</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo reserveItemValue($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

  
<?php if ($reservationId !== '' && count($inventoryItems) > 0): ?>

    <form method="post" action="">

        <input
            type="hidden"
            name="reservation_id"
            value="<?php echo reserveItemValue($reservationId); ?>"
        >

        <h2>Available Inventory</h2>

        <?php foreach ($orgInventory as $subcategory => $items): ?>

            <details class="inventory-group" open>

                <summary>
                    <?php echo reserveItemValue($subcategory); ?>
                    (<?php echo count($items); ?> items)
                </summary>

                <?php foreach ($items as $item): ?>

                    <?php
                    $itemId = (string)$item['item_id'];

                    $isChecked = in_array(
                        $itemId,
                        array_map('strval', $postedSelectedItems),
                        true
                    );

                    $quantityValue = $postedQuantities[$itemId] ?? 1;
                    ?>

                    <div class="inventory-choice">

                        <input
                            type="checkbox"
                            id="item-<?php echo reserveItemValue($itemId); ?>"
                            name="selected_items[]"
                            value="<?php echo reserveItemValue($itemId); ?>"
                            <?php echo $isChecked ? 'checked' : ''; ?>
                        >

                        <label for="item-<?php echo reserveItemValue($itemId); ?>">

                            <span class="inventory-name">
                                <?php echo reserveItemValue($item['inv_name']); ?>
                            </span>

                            <span class="inventory-quantity">
                                <?php echo reserveItemValue($item['inv_quantity']); ?>
                                available
                            </span>

                        </label>

                        <input
                            class="quantity-field"
                            type="number"
                            name="quantities[<?php echo reserveItemValue($itemId); ?>]"
                            min="1"
                            max="<?php echo reserveItemValue($item['inv_quantity']); ?>"
                            value="<?php echo reserveItemValue($quantityValue); ?>"
                            aria-label="Quantity for <?php echo reserveItemValue($item['inv_name']); ?>"
                        >

                    </div>

                <?php endforeach; ?>

            </details>

        <?php endforeach; ?>

        <button type="submit">Continue</button>

    </form>

<?php if (count($selectionItems) > 0): ?>

    <div class="content-box submitted-box">

        <h2>Submitted Inventory Selections</h2>

        <p>
            Reservation ID:
            <?php echo reserveItemValue($reservationId); ?>
        </p>

        <table class="submitted-inventory-table">
            <thead>
                <tr>
                    <th>Inventory ID</th>
                    <th>Item Name</th>
                    <th>Requested Quantity</th>
                    <th>Available Quantity</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($selectionItems as $selected): ?>

                    <tr>
                        <td>
                            <?php echo reserveItemValue($selected['item_id']); ?>
                        </td>

                        <td>
                            <?php echo reserveItemValue($selected['inv_name']); ?>
                        </td>

                        <td>
                            <?php echo reserveItemValue($selected['quantity']); ?>
                        </td>

                        <td>
                            <?php echo reserveItemValue($selected['available']); ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>
        </table>

    </div>

<?php endif; ?>

<?php elseif (count($errors) === 0): ?>

    <p>No inventory items are currently available.</p>

<?php endif; ?>


</main>

</body>
</html>
