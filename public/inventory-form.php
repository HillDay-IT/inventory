<?php
/*
|--------------------------------------------------------------------------
| INVENTORY FORM APPLICATION
|--------------------------------------------------------------------------
| This file demonstrates how to:
| 1. Display form choices using PHP arrays and foreach loops.
| 2. Receive values through $_POST.
| 3. Validate the submitted values.
| 4. Insert a new inventory item using a prepared SQL statement.
*/

require __DIR__ . '/../config/db.php';

// -------------------------------------------------------------------------
// 1. ARRAYS THAT HOLD THE FORM OPTIONS
// -------------------------------------------------------------------------

// The array key is the value saved in the database.
// The array value is the name displayed to the user.
$categories = [
    "1" => "Camera",
    "2" => "AV",
    "3" => "IT"
];

$subcategories = [
    1 => "Accessories",
    2 => "Audio",
    3 => "Lenses",
    4 => "Lighting",
    5 => "Camera Bodies"
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

// -------------------------------------------------------------------------
// 2. DEFAULT VALUES
// -------------------------------------------------------------------------

$errors = [];
$message = '';

$category_id = '';
$subcategory_id = '';
$inventoryName = '';
$inventoryStatus = '';
$inventoryQuantity = '';
$inventoryCondition = '';
$inventoryTaggable = '';
$inventoryLoose = '';
$inventoryNotes = '';
$inventoryImage = '';

// -------------------------------------------------------------------------
// 3. RECEIVE AND VALIDATE THE FORM SUBMISSION
// -------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get the submitted values. The names match the form's name attributes.
    $category_id = $_POST['category_id'] ?? '';
    $subcategory_id = $_POST['subcategory_id'] ?? '';
    $inventoryName = trim($_POST['inv_name'] ?? '');
    $inventoryStatus = $_POST['inv_ability'] ?? '';
    $inventoryQuantity = trim($_POST['inv_quantity'] ?? '');
    $inventoryCondition = $_POST['inv_condition'] ?? '';
    $inventoryTaggable = $_POST['inv_taggable'] ?? '';
    $inventoryLoose = $_POST['inv_loose'] ?? '';
    $inventoryNotes = trim($_POST['inv_notes'] ?? '');
    $inventoryImage = trim($_POST['inv_image'] ?? '');

    // Validate category.
    if (!array_key_exists($category_id, $categories)) {
        $errors[] = "Please choose a valid category.";
    }

    // A subcategory is required only when Camera is selected.
    if ($category_id === "1") {
        if (
            !ctype_digit((string)$subcategory_id) ||
            !array_key_exists((int)$subcategory_id, $subcategories)
        ) {
            $errors[] = "Please choose a valid camera subcategory.";
        }
    }

    // Validate item name.
    if ($inventoryName === '') {
        $errors[] = "Inventory name is required.";
    }

    // Validate status.
    if (!in_array($inventoryStatus, $allowedAbility, true)) {
        $errors[] = "Please choose a valid inventory status.";
    }

    // Validate quantity.
    if (
        $inventoryQuantity === '' ||
        !ctype_digit((string)$inventoryQuantity)
    ) {
        $errors[] = "Quantity must be a whole number.";
    }

    // Validate condition.
    if (!in_array($inventoryCondition, $allowedCondition, true)) {
        $errors[] = "Please choose a condition.";
    }

    // Validate Yes/No choices.
    if (!in_array($inventoryTaggable, $isTaggable, true)) {
        $errors[] = "Please select Yes or No for taggable.";
    }

    if (!in_array($inventoryLoose, $isLoose, true)) {
        $errors[] = "Please select Yes or No for loose.";
    }

    // Validate the image URL only when one was entered.
    if (
        $inventoryImage !== '' &&
        (
            filter_var($inventoryImage, FILTER_VALIDATE_URL) === false ||
            !in_array(
                parse_url($inventoryImage, PHP_URL_SCHEME),
                ['http', 'https'],
                true
            )
        )
    ) {
        $errors[] = "Please enter a valid image URL.";
    }

    // ---------------------------------------------------------------------
    // 4. INSERT THE NEW INVENTORY ITEM
    // ---------------------------------------------------------------------

    if (count($errors) === 0) {

        // Convert Yes/No into the integer values stored in MySQL.
        $taggableValue = ($inventoryTaggable === "Yes") ? 1 : 0;
        $looseValue = ($inventoryLoose === "Yes") ? 1 : 0;

        // Convert numeric form values into integers.
        $categoryValue = (int)$category_id;

        // Only Camera items have a subcategory.
        $subcategoryValue = ($category_id === "1")
            ? (int)$subcategory_id
            : null;

        $quantityValue = (int)$inventoryQuantity;

        $sql = "INSERT INTO inv
                (category_id, sub_id, inv_name, inv_ability, inv_quantity,
                 inv_condition, inv_taggable, inv_notes, inv_loose, inv_image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $statement = $conn->prepare($sql);

        if ($statement === false) {
            $errors[] = "The SQL statement could not be prepared.";
        } else {

            // The type letters match the ten values in the INSERT statement.
            // i = integer; s = string.
            $statement->bind_param(
                'iissisisis',
                $categoryValue,
                $subcategoryValue,
                $inventoryName,
                $inventoryStatus,
                $quantityValue,
                $inventoryCondition,
                $taggableValue,
                $inventoryNotes,
                $looseValue,
                $inventoryImage
            );

            if ($statement->execute()) {
                $message = "Inventory item saved.";
            } else {
                $errors[] = "Database error: " . $statement->error;
            }

            $statement->close();
        }
    }
}

// -------------------------------------------------------------------------
// 5. HELPER FUNCTION FOR SAFE HTML OUTPUT
// -------------------------------------------------------------------------

function formValue($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Display the subcategory name in the submitted-values section.
$subcategoryDisplay = '';
if (
    $category_id === "1" &&
    ctype_digit((string)$subcategory_id) &&
    array_key_exists((int)$subcategory_id, $subcategories)
) {
    $subcategoryDisplay = $subcategories[(int)$subcategory_id];
} elseif ($category_id !== "1") {
    $subcategoryDisplay = "Not applicable";
} else {
    $subcategoryDisplay = "Not submitted";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Application</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="page-header">
    <p>HillDay PR</p>
    <h1>Inventory Form</h1>
</header>

<?php if ($message !== ''): ?>
    <div class="message-box">
        <p><?php echo formValue($message); ?></p>
    </div>
<?php endif; ?>

<div class="content-box">
    <h2>Enter Inventory Item</h2>

    <?php if (count($errors) > 0): ?>
        <div class="error-message">
            <h3>Error Log:</h3>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo formValue($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">

        <!-- CATEGORY -->
        <div class="select">
            <label for="category_id">Category ID #:</label>

            <select
                id="category_id"
                name="category_id"
                class="colored-select"
                required
            >
                <option value="">Choose a category</option>

                <?php foreach ($categories as $optionCategoryId => $categoryName): ?>
                    <option
                        value="<?php echo formValue($optionCategoryId); ?>"
                        <?php if ($category_id === (string)$optionCategoryId) echo "selected"; ?>
                    >
                        <?php echo formValue($optionCategoryId . " (" . $categoryName . ")"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- CAMERA SUBCATEGORY -->
        <div id="subcategory_container" style="display: none;">
            <div class="select">
                <label for="subcategory_id">Camera Subcategory:</label>

                <select
                    id="subcategory_id"
                    name="subcategory_id"
                    class="colored-select"
                >
                    <option value="">Choose a subcategory</option>

                    <?php foreach ($subcategories as $subId => $subName): ?>
                        <option
                            value="<?php echo formValue($subId); ?>"
                            <?php if ($subcategory_id === (string)$subId) echo "selected"; ?>
                        >
                            <?php echo formValue($subName); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- SHOW THE SUBCATEGORY ONLY FOR CAMERA -->
        <script>
            const categoryDropdown = document.getElementById("category_id");
            const subcategoryContainer = document.getElementById("subcategory_container");
            const subcategoryDropdown = document.getElementById("subcategory_id");

            function updateSubcategory() {
                if (categoryDropdown.value === "1") {
                    subcategoryContainer.style.display = "block";
                    subcategoryDropdown.required = true;
                } else {
                    subcategoryContainer.style.display = "none";
                    subcategoryDropdown.required = false;
                    subcategoryDropdown.value = "";
                }
            }

            categoryDropdown.addEventListener("change", updateSubcategory);
            updateSubcategory();
        </script>

        <br><br>

        <!-- ITEM NAME -->
        <div class="typing">
            <label for="inv_name">Item Name:</label>
            <input
                class="colored-type"
                type="text"
                id="inv_name"
                name="inv_name"
                required
                value="<?php echo formValue($inventoryName); ?>"
            >
        </div>

        <br><br>

        <!-- INVENTORY STATUS -->
        <div class="select">
            <label for="inv_ability">Working or not?:</label>

            <select
                class="colored-select"
                id="inv_ability"
                name="inv_ability"
                required
            >
                <option value="">Choose a status</option>

                <?php foreach ($allowedAbility as $ability): ?>
                    <option
                        value="<?php echo formValue($ability); ?>"
                        <?php if ($inventoryStatus === $ability) echo "selected"; ?>
                    >
                        <?php echo formValue($ability); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <br><br>

        <!-- QUANTITY -->
        <div class="typing">
            <label for="inv_quantity">Quantity:</label>
            <input
                class="colored-type"
                type="number"
                id="inv_quantity"
                name="inv_quantity"
                min="0"
                step="1"
                required
                value="<?php echo formValue($inventoryQuantity); ?>"
            >
        </div>

        <br><br>

        <!-- CONDITION -->
        <div class="select">
            <label for="inv_condition">Item Condition:</label>

            <select
                class="colored-select"
                id="inv_condition"
                name="inv_condition"
                required
            >
                <option value="">Choose a condition</option>

                <?php foreach ($allowedCondition as $condition): ?>
                    <option
                        value="<?php echo formValue($condition); ?>"
                        <?php if ($inventoryCondition === $condition) echo "selected"; ?>
                    >
                        <?php echo formValue($condition); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <br><br>

        <!-- TAGGABLE -->
        <div class="drop">
            <label>Is it taggable?</label>
            <br>

            <?php foreach ($isTaggable as $taggableOption): ?>
                <input
                    type="radio"
                    name="inv_taggable"
                    value="<?php echo formValue($taggableOption); ?>"
                    <?php if ($inventoryTaggable === $taggableOption) echo "checked"; ?>
                    required
                >
                <?php echo formValue($taggableOption); ?>
                <br>
            <?php endforeach; ?>
        </div>

        <br><br>

        <!-- LOOSE -->
        <div class="drop">
            <label>Is it loose?</label>
            <br>

            <?php foreach ($isLoose as $looseOption): ?>
                <input
                    type="radio"
                    name="inv_loose"
                    value="<?php echo formValue($looseOption); ?>"
                    <?php if ($inventoryLoose === $looseOption) echo "checked"; ?>
                    required
                >
                <?php echo formValue($looseOption); ?>
                <br>
            <?php endforeach; ?>
        </div>

        <br><br>

        <!-- NOTES -->
        <div class="typing">
            <label for="inv_notes">Notes:</label>
            <br>
            <textarea
                class="colored-type"
                id="inv_notes"
                name="inv_notes"
                rows="5"
                cols="50"
            ><?php echo formValue($inventoryNotes); ?></textarea>
        </div>

        <br><br>

        <!-- IMAGE URL -->
        <div class="typing">
            <label for="inv_image">Image URL:</label>
            <input
                class="colored-type"
                type="url"
                id="inv_image"
                name="inv_image"
                placeholder="https://example.com/image.jpg"
                value="<?php echo formValue($inventoryImage); ?>"
            >
        </div>

        <br><br>

        <button type="submit">Add Item</button>

    </form>
</div>

<!-- DISPLAY THE SUBMITTED VALUES -->
<div class="content-box submitted-box">
    <h2>Submitted Values</h2>

    <p>Item Name: <?php echo formValue($inventoryName !== '' ? $inventoryName : "Not Submitted"); ?></p>
    <p>Category: <?php
        echo formValue(
            array_key_exists($category_id, $categories)
                ? $category_id . " (" . $categories[$category_id] . ")"
                : "Not Submitted"
        );
    ?></p>
    <p>Subcategory: <?php echo formValue($subcategoryDisplay); ?></p>
    <p>Ability: <?php echo formValue($inventoryStatus !== '' ? $inventoryStatus : "Not Submitted"); ?></p>
    <p>Quantity: <?php echo formValue($inventoryQuantity !== '' ? $inventoryQuantity : "Not Submitted"); ?></p>
    <p>Condition: <?php echo formValue($inventoryCondition !== '' ? $inventoryCondition : "Not Submitted"); ?></p>
    <p>Taggability: <?php echo formValue($inventoryTaggable !== '' ? $inventoryTaggable : "Not Submitted"); ?></p>
    <p>Loose: <?php echo formValue($inventoryLoose !== '' ? $inventoryLoose : "Not Submitted"); ?></p>
    <p>Notes: <?php echo formValue($inventoryNotes !== '' ? $inventoryNotes : "None"); ?></p>

    <p>Image:</p>

    <?php if (
        filter_var($inventoryImage, FILTER_VALIDATE_URL) &&
        in_array(parse_url($inventoryImage, PHP_URL_SCHEME), ['http', 'https'], true)
    ): ?>
        <img
            src="<?php echo formValue($inventoryImage); ?>"
            alt="Inventory item"
            width="200"
        >
    <?php else: ?>
        <p>No valid image URL provided.</p>
    <?php endif; ?>
</div>

</body>
</html>
