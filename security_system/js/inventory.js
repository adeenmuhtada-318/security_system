// =====================================================
// INVENTORY JAVASCRIPT - inventory.js
// Handles update quantity modal and archive actions
// =====================================================

// ---- Open update quantity modal ----
function OpenUpdateModal(ItemID, ItemName, CurrentQty) {
    document.getElementById('UpdateItemID').value   = ItemID;
    document.getElementById('UpdateItemName').textContent = ItemName;
    document.getElementById('UpdateQtyInput').value = CurrentQty;
    OpenModal('UpdateQtyModal');
}

// ---- Archive/Remove item ----
function ArchiveItem(ItemID, ItemName) {
    ConfirmAction(
        'Remove "' + ItemName + '" from inventory?\n\nThe record will be archived (not deleted permanently).',
        function() {
            document.getElementById('ArchiveItemID').value = ItemID;
            document.getElementById('ArchiveItemForm').submit();
        }
    );
}
