<?php
// =====================================================
// DASHBOARD - dashboard.php
// Main command center with stats, hiring, guard roster
// =====================================================

// Step 1: Start session and check login
session_start();
if (!isset($_SESSION['LoggedIn']) || $_SESSION['LoggedIn'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db_connect.php';
$db = getConnection();

// ---- Fetch live stats from database ----
$TotalGuards   = $db->query("SELECT COUNT(*) FROM Guards WHERE IsArchived = 0 AND Status = 'Active'")->fetchColumn();
$OnDutyGuards  = $db->query("SELECT COUNT(*) FROM Guards WHERE IsArchived = 0 AND CurrentShift != 'Off Duty' AND Status = 'Active'")->fetchColumn();
$TodayAbsents  = $db->query("SELECT COUNT(*) FROM Attendance WHERE AttendanceDate = CURDATE() AND IsPresent = 0")->fetchColumn();
$TotalItems    = $db->query("SELECT COUNT(*) FROM Inventory WHERE IsArchived = 0")->fetchColumn();
$LowStockItems = $db->query("SELECT COUNT(*) FROM Inventory WHERE IsArchived = 0 AND Quantity < MinThreshold")->fetchColumn();

// ---- Fetch all active guards for roster ----
$Guards = $db->query("SELECT * FROM Guards WHERE IsArchived = 0 ORDER BY FullName ASC")->fetchAll();

// ---- Fetch archived guards ----
$ArchivedGuards = $db->query("SELECT * FROM Guards WHERE IsArchived = 1 ORDER BY FullName ASC")->fetchAll();

// ---- Handle Add Guard form submission ----
$SuccessMsg = '';
$ErrorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['FormAction']) && $_POST['FormAction'] === 'AddGuard') {
    $Name          = trim($_POST['FullName'] ?? '');
    $CNIC          = trim($_POST['CNIC'] ?? '');
    $Phone         = trim($_POST['Phone'] ?? '');
    $Emergency     = trim($_POST['EmergencyPhone'] ?? '');
    $Blood         = $_POST['BloodGroup'] ?? '';
    $Address       = trim($_POST['Address'] ?? '');
    $JoiningDate   = $_POST['JoiningDate'] ?? '';

    if ($Name && $CNIC && $JoiningDate) {
        try {
            $stmt = $db->prepare("
                INSERT INTO Guards (FullName, CNIC, Phone, EmergencyPhone, BloodGroup, Address, JoiningDate)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$Name, $CNIC, $Phone, $Emergency, $Blood, $Address, $JoiningDate]);
            $SuccessMsg = "Guard \"$Name\" has been successfully added to the system!";
            // Refresh page data
            header("Location: dashboard.php?added=1");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $ErrorMsg = "A guard with this CNIC already exists.";
            } else {
                $ErrorMsg = "Something went wrong. Please try again.";
            }
        }
    } else {
        $ErrorMsg = "Please fill in all required fields (Name, CNIC, Joining Date).";
    }
}

// ---- Handle shift change ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['FormAction']) && $_POST['FormAction'] === 'ChangeShift') {
    $GuardID  = (int)($_POST['GuardID'] ?? 0);
    $NewShift = $_POST['NewShift'] ?? '';
    $Allowed  = ['Morning', 'Evening', 'Night', 'Off Duty'];

    if ($GuardID && in_array($NewShift, $Allowed)) {
        $stmt = $db->prepare("UPDATE Guards SET CurrentShift = ? WHERE GuardID = ?");
        $stmt->execute([$NewShift, $GuardID]);
        header("Location: dashboard.php?shifted=1");
        exit;
    }
}

// ---- Handle archive guard ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['FormAction']) && $_POST['FormAction'] === 'ArchiveGuard') {
    $GuardID = (int)($_POST['GuardID'] ?? 0);
    if ($GuardID) {
        $db->prepare("UPDATE Guards SET IsArchived = 1, Status = 'Inactive' WHERE GuardID = ?")->execute([$GuardID]);
        header("Location: dashboard.php?archived=1");
        exit;
    }
}

// ---- Handle unarchive (restore) guard ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['FormAction']) && $_POST['FormAction'] === 'UnarchiveGuard') {
    $GuardID = (int)($_POST['GuardID'] ?? 0);
    if ($GuardID) {
        $db->prepare("UPDATE Guards SET IsArchived = 0, Status = 'Active' WHERE GuardID = ?")->execute([$GuardID]);
        header("Location: dashboard.php?restored=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SecureForce</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="AppShell">

    <?php include 'includes/nav.php'; ?>

    <main class="MainContent">

        <!-- Top Bar -->
        <div class="TopBar">
            <div class="TopBarTitle">🏠 Command Dashboard</div>
            <div class="TopBarRight">
                <div class="TopBarDate">
                    <span class="StatusDot"></span>
                    <span id="CurrentDateTime">Loading...</span>
                </div>
                <button class="Btn BtnPrimary" onclick="OpenModal('AddGuardModal')">
                    ➕ Add New Guard
                </button>
            </div>
        </div>

        <div class="PageContent">

            <!-- Alerts -->
            <?php if (isset($_GET['added'])): ?>
                <div class="AlertBox AlertSuccess">✅ New guard has been added successfully!</div>
            <?php endif; ?>
            <?php if (isset($_GET['shifted'])): ?>
                <div class="AlertBox AlertSuccess">✅ Guard shift has been updated!</div>
            <?php endif; ?>
            <?php if (isset($_GET['archived'])): ?>
                <div class="AlertBox AlertWarning">🗃 Guard has been archived.</div>
            <?php endif; ?>
            <?php if (isset($_GET['restored'])): ?>
                <div class="AlertBox AlertSuccess">♻️ Guard has been restored to active duty!</div>
            <?php endif; ?>
            <?php if ($ErrorMsg): ?>
                <div class="AlertBox AlertDanger">❌ <?= htmlspecialchars($ErrorMsg) ?></div>
            <?php endif; ?>

            <!-- Live Stats Cards -->
            <div class="StatsGrid">

                <div class="StatCard" style="--CardAccent: #FF9800;">
                    <div class="StatCardIcon" style="background: rgba(255,152,0,0.12); font-size: 24px;">👮</div>
                    <div class="StatCardBody">
                        <div class="StatValue"><?= $TotalGuards ?></div>
                        <div class="StatLabel">Total Guards</div>
                    </div>
                </div>

                <div class="StatCard" style="--CardAccent: #4CAF50;">
                    <div class="StatCardIcon" style="background: rgba(76,175,80,0.12); font-size: 24px;">🟢</div>
                    <div class="StatCardBody">
                        <div class="StatValue"><?= $OnDutyGuards ?></div>
                        <div class="StatLabel">Currently On Duty</div>
                    </div>
                </div>

                <div class="StatCard" style="--CardAccent: #F44336;">
                    <div class="StatCardIcon" style="background: rgba(244,67,54,0.12); font-size: 24px;">❌</div>
                    <div class="StatCardBody">
                        <div class="StatValue"><?= $TodayAbsents ?></div>
                        <div class="StatLabel">Today's Absents</div>
                    </div>
                </div>

                <div class="StatCard" style="--CardAccent: #5B7F95;">
                    <div class="StatCardIcon" style="background: rgba(91,127,149,0.12); font-size: 24px;">📦</div>
                    <div class="StatCardBody">
                        <div class="StatValue"><?= $TotalItems ?></div>
                        <div class="StatLabel">Inventory Items</div>
                    </div>
                </div>

                <?php if ($LowStockItems > 0): ?>
                <div class="StatCard" style="--CardAccent: #F44336;">
                    <div class="StatCardIcon" style="background: rgba(244,67,54,0.12); font-size: 24px;">⚠️</div>
                    <div class="StatCardBody">
                        <div class="StatValue" style="color: #F44336;"><?= $LowStockItems ?></div>
                        <div class="StatLabel">Low Stock Alerts</div>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Guard Roster Table -->
            <div class="SectionPanel">
                <div class="SectionHeader">
                    <div class="SectionTitle">👥 Guard Roster</div>
                    <span class="Badge BadgeBlue"><?= count($Guards) ?> Guards</span>
                </div>

                <div style="overflow-x: auto;">
                    <table class="DataTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Guard Name</th>
                                <th>CNIC</th>
                                <th>Phone</th>
                                <th>Blood Group</th>
                                <th>Joining Date</th>
                                <th>Current Shift</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($Guards)): ?>
                                <tr>
                                    <td colspan="9">
                                        <div class="EmptyState">
                                            <div class="EmptyIcon">👮</div>
                                            <p>No guards in the system yet. Add your first guard!</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($Guards as $Index => $Guard): ?>
                                <tr>
                                    <td style="color: var(--ColorTextMuted);"><?= $Index + 1 ?></td>
                                    <td>
                                        <strong style="cursor: pointer; color: var(--ColorAccentGold);"
                                            onclick="ViewGuardProfile(<?= $Guard['GuardID'] ?>)">
                                            <?= htmlspecialchars($Guard['FullName']) ?>
                                        </strong>
                                    </td>
                                    <td style="font-family: monospace; font-size: 12px;"><?= htmlspecialchars($Guard['CNIC']) ?></td>
                                    <td><?= htmlspecialchars($Guard['Phone']) ?></td>
                                    <td>
                                        <span class="Badge BadgeWarning"><?= htmlspecialchars($Guard['BloodGroup']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($Guard['JoiningDate']) ?></td>
                                    <td>
                                        <?php
                                            $ShiftClass = match($Guard['CurrentShift']) {
                                                'Morning'  => 'Badge ShiftMorning',
                                                'Evening'  => 'Badge ShiftEvening',
                                                'Night'    => 'Badge ShiftNight',
                                                default    => 'Badge ShiftOff'
                                            };
                                            $ShiftIcon = match($Guard['CurrentShift']) {
                                                'Morning'  => '🌅',
                                                'Evening'  => '🌆',
                                                'Night'    => '🌙',
                                                default    => '🔴'
                                            };
                                        ?>
                                        <span class="<?= $ShiftClass ?>">
                                            <?= $ShiftIcon ?> <?= $Guard['CurrentShift'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="Badge <?= $Guard['Status'] === 'Active' ? 'BadgeSuccess' : 'BadgeMuted' ?>">
                                            <?= $Guard['Status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                            <button class="Btn BtnSecondary BtnSmall"
                                                onclick="ViewGuardProfile(<?= $Guard['GuardID'] ?>)">
                                                👁 View
                                            </button>
                                            <button class="Btn BtnSuccess BtnSmall"
                                                onclick="OpenShiftModal(<?= $Guard['GuardID'] ?>, '<?= htmlspecialchars($Guard['FullName']) ?>')">
                                                🔄 Shift
                                            </button>
                                            <button class="Btn BtnDanger BtnSmall"
                                                onclick="ArchiveGuard(<?= $Guard['GuardID'] ?>, '<?= htmlspecialchars($Guard['FullName']) ?>')">
                                                🗃 Archive
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /PageContent -->

        <!-- =====================================================
             ARCHIVED GUARDS SECTION
             ===================================================== -->
        <?php if (!empty($ArchivedGuards)): ?>
        <div class="PageContent" style="padding-top: 0;">
            <div class="SectionPanel">
                <div class="SectionHeader">
                    <div class="SectionTitle">🗃 Archived Guards</div>
                    <span class="ArchiveLabel"><?= count($ArchivedGuards) ?> Archived</span>
                </div>
                <div style="overflow-x: auto;">
                    <table class="DataTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Guard Name</th>
                                <th>CNIC</th>
                                <th>Phone</th>
                                <th>Joining Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ArchivedGuards as $Idx => $Archived): ?>
                            <tr style="opacity: 0.75;">
                                <td style="color: var(--ColorTextMuted);"><?= $Idx + 1 ?></td>
                                <td>
                                    <strong style="color: var(--ColorTextSecondary);">
                                        <?= htmlspecialchars($Archived['FullName']) ?>
                                    </strong>
                                </td>
                                <td style="font-family: var(--FontMono); font-size: 12px; color: var(--ColorTextMuted);">
                                    <?= htmlspecialchars($Archived['CNIC']) ?>
                                </td>
                                <td style="color: var(--ColorTextSecondary);">
                                    <?= htmlspecialchars($Archived['Phone'] ?: '—') ?>
                                </td>
                                <td style="color: var(--ColorTextSecondary);">
                                    <?= htmlspecialchars($Archived['JoiningDate']) ?>
                                </td>
                                <td>
                                    <span class="Badge BadgeMuted">Archived</span>
                                </td>
                                <td>
                                    <!-- Restore button: sets IsArchived=0, Status=Active -->
                                    <form method="POST" action="dashboard.php" style="display:inline;">
                                        <input type="hidden" name="FormAction" value="UnarchiveGuard">
                                        <input type="hidden" name="GuardID" value="<?= $Archived['GuardID'] ?>">
                                        <button type="submit" class="Btn BtnRestore BtnSmall"
                                            onclick="return confirm('Restore <?= htmlspecialchars($Archived['FullName']) ?> to active duty?')">
                                            ♻️ Restore
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- =====================================================
     MODAL: Add New Guard
     ===================================================== -->
<div class="ModalOverlay" id="AddGuardModal">
    <div class="ModalCard">
        <div class="ModalHeader">
            <div class="ModalTitle">➕ Add New Guard</div>
            <button class="ModalClose" onclick="CloseModal('AddGuardModal')">✕</button>
        </div>
        <div class="ModalBody">
            <form method="POST" action="dashboard.php">
                <input type="hidden" name="FormAction" value="AddGuard">

                <div class="FormRow">
                    <div class="FormGroup">
                        <label class="FormLabel">Full Name *</label>
                        <input type="text" name="FullName" class="FormControl" placeholder="e.g. Muhammad Ali" required>
                    </div>
                    <div class="FormGroup">
                        <label class="FormLabel">CNIC *</label>
                        <input type="text" name="CNIC" class="FormControl" placeholder="35201-1234567-1" required>
                    </div>
                </div>

                <div class="FormRow">
                    <div class="FormGroup">
                        <label class="FormLabel">Phone Number</label>
                        <input type="text" name="Phone" class="FormControl" placeholder="0300-1234567">
                    </div>
                    <div class="FormGroup">
                        <label class="FormLabel">Emergency Contact</label>
                        <input type="text" name="EmergencyPhone" class="FormControl" placeholder="0301-1234567">
                    </div>
                </div>

                <div class="FormRow">
                    <div class="FormGroup">
                        <label class="FormLabel">Blood Group</label>
                        <select name="BloodGroup" class="FormControl">
                            <option value="">Select...</option>
                            <option>A+</option><option>A-</option>
                            <option>B+</option><option>B-</option>
                            <option>O+</option><option>O-</option>
                            <option>AB+</option><option>AB-</option>
                        </select>
                    </div>
                    <div class="FormGroup">
                        <label class="FormLabel">Joining Date *</label>
                        <input type="date" name="JoiningDate" class="FormControl" required>
                    </div>
                </div>

                <div class="FormGroup" style="margin-bottom: 20px;">
                    <label class="FormLabel">Home Address</label>
                    <input type="text" name="Address" class="FormControl" placeholder="City, Province">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="Btn BtnPrimary BtnFull">✅ Add Guard to System</button>
                    <button type="button" class="Btn BtnSecondary" onclick="CloseModal('AddGuardModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: Guard Profile View
     ===================================================== -->
<div class="ModalOverlay" id="GuardProfileModal">
    <div class="ModalCard">
        <div class="ModalHeader">
            <div class="ModalTitle">👮 Guard Profile</div>
            <button class="ModalClose" onclick="CloseModal('GuardProfileModal')">✕</button>
        </div>
        <div class="ModalBody" id="GuardProfileBody">
            <div class="Spinner"></div>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: Change Shift
     ===================================================== -->
<div class="ModalOverlay" id="ShiftModal">
    <div class="ModalCard" style="max-width: 380px;">
        <div class="ModalHeader">
            <div class="ModalTitle">🔄 Change Shift</div>
            <button class="ModalClose" onclick="CloseModal('ShiftModal')">✕</button>
        </div>
        <div class="ModalBody">
            <p style="color: var(--ColorTextSecondary); margin-bottom: 18px; font-size: 14px;">
                Assigning new shift for: <strong id="ShiftGuardName" style="color: var(--ColorAccentGold);"></strong>
            </p>
            <form method="POST" action="dashboard.php">
                <input type="hidden" name="FormAction" value="ChangeShift">
                <input type="hidden" name="GuardID" id="ShiftGuardID">

                <div class="FormGroup" style="margin-bottom: 20px;">
                    <label class="FormLabel">Select New Shift</label>
                    <select name="NewShift" class="FormControl">
                        <option value="Morning">🌅 Morning Shift</option>
                        <option value="Evening">🌆 Evening Shift</option>
                        <option value="Night">🌙 Night Shift</option>
                        <option value="Off Duty">🔴 Off Duty (Relieve)</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="Btn BtnPrimary BtnFull">✅ Update Shift</button>
                    <button type="button" class="Btn BtnSecondary" onclick="CloseModal('ShiftModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden form for archive action -->
<form id="ArchiveForm" method="POST" action="dashboard.php" style="display:none;">
    <input type="hidden" name="FormAction" value="ArchiveGuard">
    <input type="hidden" name="GuardID" id="ArchiveGuardID">
</form>

<script src="js/app.js"></script>
<script src="js/dashboard.js"></script>
</body>
</html>
