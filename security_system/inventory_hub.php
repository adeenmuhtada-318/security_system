<?php
// =====================================================
// INVENTORY HUB - inventory_hub.php
// Add/manage inventory items, auto-alert for low stock
// =====================================================
session_start();
if (!isset($_SESSION['LoggedIn']) || $_SESSION['LoggedIn'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db_connect.php';
$db = getConnection();

$SuccessMsg = '';
$ErrorMsg   = '';

// ---- Handle: Add New Item ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['FormAction'] ?? '') === 'AddItem') {
    $Name      = trim($_POST['ItemName'] ?? '');
    $Category  = $_POST['Category'] ?? '';
    $Quantity  = (int)($_POST['Quantity'] ?? 0);
    $MinThresh = (int)($_POST['MinThreshold'] ?? 5);
    $AssignTo  = trim($_POST['AssignedTo'] ?? 'Warehouse');

    $AllowedCats = ['Weapons & Gear', 'Office & Logistics', 'Bulk Reserves'];

    if ($Name && in_array($Category, $AllowedCats) && $Quantity >= 0) {
        $stmt = $db->prepare("
            INSERT INTO Inventory (ItemName, Category, Quantity, MinThreshold, AssignedTo)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$Name, $Category, $Quantity, $MinThresh, $AssignTo]);
        header("Location: inventory_hub.php?added=1");
        exit;
    } else {
        $ErrorMsg = "Please fill in all required fields correctly.";
    }
}

// ---- Handle: Update Quantity ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['FormAction'] ?? '') === 'UpdateQty') {
    $ItemID      = (int)($_POST['ItemID'] ?? 0);
    $NewQuantity = (int)($_POST['NewQuantity'] ?? 0);
    if ($ItemID && $NewQuantity >= 0) {
        $db->prepare("UPDATE Inventory SET Quantity = ? WHERE ItemID = ?")->execute([$NewQuantity, $ItemID]);
        header("Location: inventory_hub.php?updated=1");
        exit;
    }
}

// ---- Handle: Archive Item ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['FormAction'] ?? '') === 'ArchiveItem') {
    $ItemID = (int)($_POST['ItemID'] ?? 0);
    if ($ItemID) {
        $db->prepare("UPDATE Inventory SET IsArchived = 1 WHERE ItemID = ?")->execute([$ItemID]);
        header("Location: inventory_hub.php?archived=1");
        exit;
    }
}

// ---- Fetch all items grouped by category ----
$AllItems = $db->query("SELECT * FROM Inventory WHERE IsArchived = 0 ORDER BY Category, ItemName")->fetchAll();

$Categories = [
    'Weapons & Gear'     => [],
    'Office & Logistics' => [],
    'Bulk Reserves'      => []
];
foreach ($AllItems as $Item) {
    $Categories[$Item['Category']][] = $Item;
}

$LowStockCount = count(array_filter($AllItems, fn($I) => $I['Quantity'] < $I['MinThreshold']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | SecureForce</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="AppShell">
    <?php include 'includes/nav.php'; ?>

    <main class="MainContent">

        <div class="TopBar">
            <div class="TopBarTitle">📦 Inventory Management</div>
            <div class="TopBarRight">
                <div class="TopBarDate">
                    <span class="StatusDot"></span>
                    <span id="CurrentDateTime"></span>
                </div>
                <button class="Btn BtnPrimary" onclick="OpenModal('AddItemModal')">
                    ➕ Add New Item
                </button>
            </div>
        </div>

        <div class="PageContent">

            <!-- Alerts -->
            <?php if (isset($_GET['added'])): ?>
                <div class="AlertBox AlertSuccess">✅ New item added to inventory!</div>
            <?php endif; ?>
            <?php if (isset($_GET['updated'])): ?>
                <div class="AlertBox AlertSuccess">✅ Quantity updated!</div>
            <?php endif; ?>
            <?php if ($ErrorMsg): ?>
                <div class="AlertBox AlertDanger">❌ <?= htmlspecialchars($ErrorMsg) ?></div>
            <?php endif; ?>

            <!-- Low Stock Warning Banner -->
            <?php if ($LowStockCount > 0): ?>
            <div class="AlertBox AlertDanger" style="font-size: 14px;">
                ⚠️ <strong><?= $LowStockCount ?> item(s) are below minimum stock threshold!</strong>
                These are highlighted in red below. Please restock immediately.
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="StatsGrid" style="margin-bottom: 24px;">
                <div class="StatCard" style="--CardAccent: #F44336;">
                    <div class="StatCardIcon" style="background: rgba(244,67,54,0.12);">🔫</div>
                    <div class="StatCardBody">
                        <div class="StatValue"><?= count($Categories['Weapons & Gear']) ?></div>
                        <div class="StatLabel">Weapons & Gear</div>
                    </div>
                </div>
                <div class="StatCard" style="--CardAccent: #2196F3;">
                    <div class="StatCardIcon" style="background: rgba(33,150,243,0.12);">🖥️</div>
                    <div class="StatCardBody">
                        <div class="StatValue"><?= count($Categories['Office & Logistics']) ?></div>
                        <div class="StatLabel">Office & Logistics</div>
                    </div>
                </div>
                <div class="StatCard" style="--CardAccent: #4CAF50;">
                    <div class="StatCardIcon" style="background: rgba(76,175,80,0.12);">📦</div>
                    <div class="StatCardBody">
                        <div class="StatValue"><?= count($Categories['Bulk Reserves']) ?></div>
                        <div class="StatLabel">Bulk Reserves</div>
                    </div>
                </div>
                <?php if ($LowStockCount > 0): ?>
                <div class="StatCard" style="--CardAccent: #F44336;">
                    <div class="StatCardIcon" style="background: rgba(244,67,54,0.12);">⚠️</div>
                    <div class="StatCardBody">
                        <div class="StatValue" style="color: #F44336;"><?= $LowStockCount ?></div>
                        <div class="StatLabel">Low Stock Alerts</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Categories Tables -->
            <?php
            $CategoryIcons = [
                'Weapons & Gear'     => '🔫',
                'Office & Logistics' => '🖥️',
                'Bulk Reserves'      => '📦'
            ];
            $CategoryClasses = [
                'Weapons & Gear'     => 'CatWeapons',
                'Office & Logistics' => 'CatOffice',
                'Bulk Reserves'      => 'CatBulk'
            ];

            foreach ($Categories as $CatName => $Items):
            ?>
            <div class="SectionPanel" style="margin-bottom: 20px;">
                <div class="SectionHeader">
                    <div class="SectionTitle">
                        <?= $CategoryIcons[$CatName] ?> <?= $CatName ?>
                    </div>
                    <span class="Badge <?= $CategoryClasses[$CatName] ?>"><?= count($Items) ?> Items</span>
                </div>

                <?php if (empty($Items)): ?>
                    <div class="EmptyState" style="padding: 30px;">
                        <p>No items in this category. Add one using the button above.</p>
                    </div>
                <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="DataTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Current Qty</th>
                                <th>Min. Required</th>
                                <th>Assigned To</th>
                                <th>Stock Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($Items as $Idx => $Item): ?>
                            <?php $IsLow = $Item['Quantity'] < $Item['MinThreshold']; ?>
                            <tr style="<?= $IsLow ? 'background: rgba(244,67,54,0.04);' : '' ?>">

                                <td style="color: var(--ColorTextMuted);"><?= $Idx + 1 ?></td>

                                <td>
                                    <strong><?= htmlspecialchars($Item['ItemName']) ?></strong>
                                </td>

                                <td>
                                    <span style="font-family: var(--FontDisplay); font-size: 18px; font-weight: 700;
                                        color: <?= $IsLow ? '#F44336' : '#4CAF50' ?>;">
                                        <?= $Item['Quantity'] ?>
                                    </span>
                                </td>

                                <td style="color: var(--ColorTextSecondary);"><?= $Item['MinThreshold'] ?></td>

                                <td>
                                    <span class="Badge BadgeBlue"><?= htmlspecialchars($Item['AssignedTo']) ?></span>
                                </td>

                                <td>
                                    <?php if ($IsLow): ?>
                                        <div class="CriticalAlert">CRITICAL STOCK DEFICIT</div>
                                    <?php else: ?>
                                        <span class="Badge BadgeSuccess">✅ Sufficient</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                        <button class="Btn BtnSecondary BtnSmall"
                                            onclick="OpenUpdateModal(<?= $Item['ItemID'] ?>, '<?= htmlspecialchars($Item['ItemName']) ?>', <?= $Item['Quantity'] ?>)">
                                            ✏️ Update Qty
                                        </button>
                                        <button class="Btn BtnDanger BtnSmall"
                                            onclick="ArchiveItem(<?= $Item['ItemID'] ?>, '<?= htmlspecialchars($Item['ItemName']) ?>')">
                                            🗃 Remove
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

        </div>
    </main>
</div>

<!-- =====================================================
     MODAL: Add New Item
     ===================================================== -->
<div class="ModalOverlay" id="AddItemModal">
    <div class="ModalCard">
        <div class="ModalHeader">
            <div class="ModalTitle">➕ Add New Inventory Item</div>
            <button class="ModalClose" onclick="CloseModal('AddItemModal')">✕</button>
        </div>
        <div class="ModalBody">
            <form method="POST" action="inventory_hub.php">
                <input type="hidden" name="FormAction" value="AddItem">

                <div class="FormRow">
                    <div class="FormGroup">
                        <label class="FormLabel">Item Name *</label>
                        <input type="text" name="ItemName" class="FormControl" placeholder="e.g. Pistol 9mm" required>
                    </div>
                    <div class="FormGroup">
                        <label class="FormLabel">Category *</label>
                        <select name="Category" class="FormControl" required>
                            <option value="">Select Category...</option>
                            <option value="Weapons & Gear">🔫 Weapons & Gear</option>
                            <option value="Office & Logistics">🖥️ Office & Logistics</option>
                            <option value="Bulk Reserves">📦 Bulk Reserves</option>
                        </select>
                    </div>
                </div>

                <div class="FormRow">
                    <div class="FormGroup">
                        <label class="FormLabel">Current Quantity *</label>
                        <input type="number" name="Quantity" class="FormControl" min="0" value="0" required>
                    </div>
                    <div class="FormGroup">
                        <label class="FormLabel">Minimum Threshold</label>
                        <input type="number" name="MinThreshold" class="FormControl" min="1" value="5">
                    </div>
                </div>

                <div class="FormGroup" style="margin-bottom: 20px;">
                    <label class="FormLabel">Assigned To / Location</label>
                    <select name="AssignedTo" class="FormControl">
                        <option value="Warehouse">Warehouse</option>
                        <option value="Armory">Armory</option>
                        <option value="Head Office">Head Office</option>
                        <option value="Control Room">Control Room</option>
                        <option value="Medical Room">Medical Room</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="Btn BtnPrimary BtnFull">✅ Add to Inventory</button>
                    <button type="button" class="Btn BtnSecondary" onclick="CloseModal('AddItemModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: Update Quantity
     ===================================================== -->
<div class="ModalOverlay" id="UpdateQtyModal">
    <div class="ModalCard" style="max-width: 360px;">
        <div class="ModalHeader">
            <div class="ModalTitle">✏️ Update Quantity</div>
            <button class="ModalClose" onclick="CloseModal('UpdateQtyModal')">✕</button>
        </div>
        <div class="ModalBody">
            <p style="color: var(--ColorTextSecondary); margin-bottom: 16px; font-size: 14px;">
                Item: <strong id="UpdateItemName" style="color: var(--ColorAccentGold);"></strong>
            </p>
            <form method="POST" action="inventory_hub.php">
                <input type="hidden" name="FormAction" value="UpdateQty">
                <input type="hidden" name="ItemID" id="UpdateItemID">

                <div class="FormGroup" style="margin-bottom: 20px;">
                    <label class="FormLabel">New Quantity</label>
                    <input type="number" name="NewQuantity" id="UpdateQtyInput" class="FormControl" min="0" required>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="Btn BtnPrimary BtnFull">✅ Update</button>
                    <button type="button" class="Btn BtnSecondary" onclick="CloseModal('UpdateQtyModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden archive form -->
<form id="ArchiveItemForm" method="POST" action="inventory_hub.php" style="display:none;">
    <input type="hidden" name="FormAction" value="ArchiveItem">
    <input type="hidden" name="ItemID" id="ArchiveItemID">
</form>

<script src="js/app.js"></script>
<script src="js/inventory.js"></script>
</body>
</html>
