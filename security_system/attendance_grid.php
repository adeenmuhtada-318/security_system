<?php
// =====================================================
// ATTENDANCE GRID - attendance_grid.php
// Daily attendance sheet with fines management
// =====================================================
session_start();
if (!isset($_SESSION['LoggedIn']) || $_SESSION['LoggedIn'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db_connect.php';
$db = getConnection();

// ---- Get date to view (default: today) ----
$ViewDate = $_GET['date'] ?? date('Y-m-d');

// ---- Make sure all active guards have an attendance record for this date ----
$ActiveGuards = $db->query("SELECT GuardID FROM Guards WHERE IsArchived = 0 AND Status = 'Active'")->fetchAll();
foreach ($ActiveGuards as $G) {
    $Check = $db->prepare("SELECT AttendanceID FROM Attendance WHERE GuardID = ? AND AttendanceDate = ?");
    $Check->execute([$G['GuardID'], $ViewDate]);
    if (!$Check->fetch()) {
        $db->prepare("INSERT INTO Attendance (GuardID, AttendanceDate) VALUES (?, ?)")
           ->execute([$G['GuardID'], $ViewDate]);
    }
}

// ---- Fetch attendance records for the selected date ----
$AttendanceList = $db->prepare("
    SELECT a.*, g.FullName, g.CurrentShift
    FROM Attendance a
    JOIN Guards g ON a.GuardID = g.GuardID
    WHERE a.AttendanceDate = ? AND g.IsArchived = 0
    ORDER BY g.FullName ASC
");
$AttendanceList->execute([$ViewDate]);
$Records = $AttendanceList->fetchAll();

// ---- Summary counts ----
$TotalPresent = 0;
$TotalAbsent  = 0;
$TotalFines   = 0;
foreach ($Records as $R) {
    if ($R['IsPresent']) $TotalPresent++; else $TotalAbsent++;
    $TotalFines += $R['TotalFine'];
}

// Fine amounts (fixed by admin)
$FineAmounts = [
    'UniformFine'  => 500,
    'WeaponFine'   => 1000,
    'LateFine'     => 300,
    'ConductFine'  => 500
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Attendance | SecureForce</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="AppShell">
    <?php include 'includes/nav.php'; ?>

    <main class="MainContent">

        <div class="TopBar">
            <div class="TopBarTitle">📋 Daily Attendance Sheet</div>
            <div class="TopBarRight">
                <div class="TopBarDate">
                    <span class="StatusDot"></span>
                    <span id="CurrentDateTime"></span>
                </div>
            </div>
        </div>

        <div class="PageContent">

            <!-- Date Selector & Stats -->
            <div class="SectionPanel" style="margin-bottom: 20px;">
                <div class="SectionBody" style="padding: 16px 22px;">
                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label class="FormLabel" style="margin: 0; white-space: nowrap;">📅 View Date:</label>
                            <input type="date" id="DatePicker" class="FormControl" style="width: auto;"
                                value="<?= htmlspecialchars($ViewDate) ?>"
                                onchange="window.location.href='attendance_grid.php?date=' + this.value">
                        </div>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-left: auto;">
                            <div style="text-align: center; padding: 8px 20px; background: rgba(76,175,80,0.1); border-radius: 8px; border: 1px solid rgba(76,175,80,0.3);">
                                <div style="font-family: var(--FontDisplay); font-size: 22px; font-weight: 700; color: #4CAF50;"><?= $TotalPresent ?></div>
                                <div style="font-size: 11px; color: var(--ColorTextMuted); text-transform: uppercase;">Present</div>
                            </div>
                            <div style="text-align: center; padding: 8px 20px; background: rgba(244,67,54,0.1); border-radius: 8px; border: 1px solid rgba(244,67,54,0.3);">
                                <div style="font-family: var(--FontDisplay); font-size: 22px; font-weight: 700; color: #F44336;"><?= $TotalAbsent ?></div>
                                <div style="font-size: 11px; color: var(--ColorTextMuted); text-transform: uppercase;">Absent</div>
                            </div>
                            <div style="text-align: center; padding: 8px 20px; background: rgba(255,152,0,0.1); border-radius: 8px; border: 1px solid rgba(255,152,0,0.3);">
                                <div style="font-family: var(--FontDisplay); font-size: 18px; font-weight: 700; color: #FF9800;">PKR <?= number_format($TotalFines, 0) ?></div>
                                <div style="font-size: 11px; color: var(--ColorTextMuted); text-transform: uppercase;">Total Fines</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fine Legend -->
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
                <div class="AlertBox AlertWarning" style="margin: 0; padding: 8px 14px; font-size: 12px;">
                    👗 Uniform Fine: PKR <?= number_format($FineAmounts['UniformFine']) ?>
                </div>
                <div class="AlertBox AlertWarning" style="margin: 0; padding: 8px 14px; font-size: 12px;">
                    🔫 Weapon Fine: PKR <?= number_format($FineAmounts['WeaponFine']) ?>
                </div>
                <div class="AlertBox AlertWarning" style="margin: 0; padding: 8px 14px; font-size: 12px;">
                    ⏰ Late Fine: PKR <?= number_format($FineAmounts['LateFine']) ?>
                </div>
                <div class="AlertBox AlertWarning" style="margin: 0; padding: 8px 14px; font-size: 12px;">
                    ⚖ Conduct Fine: PKR <?= number_format($FineAmounts['ConductFine']) ?>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="SectionPanel">
                <div class="SectionHeader">
                    <div class="SectionTitle">📊 Attendance Register — <?= date('D, d M Y', strtotime($ViewDate)) ?></div>
                    <span class="Badge BadgeBlue"><?= count($Records) ?> Records</span>
                </div>

                <?php if (empty($Records)): ?>
                    <div class="EmptyState">
                        <div class="EmptyIcon">📋</div>
                        <p>No guards found in the system.</p>
                    </div>
                <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="DataTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Guard Name</th>
                                <th>Shift</th>
                                <th>Present?</th>
                                <th>👗 Uniform</th>
                                <th>🔫 Weapon</th>
                                <th>⏰ Late</th>
                                <th>⚖ Conduct</th>
                                <th>Total Fine</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($Records as $Index => $Row): ?>
                            <tr id="Row_<?= $Row['AttendanceID'] ?>"
                                style="<?= $Row['IsLocked'] ? 'opacity: 0.7;' : '' ?>">

                                <td style="color: var(--ColorTextMuted);"><?= $Index + 1 ?></td>

                                <td><strong><?= htmlspecialchars($Row['FullName']) ?></strong></td>

                                <td>
                                    <?php
                                        $ShiftClass = match($Row['CurrentShift']) {
                                            'Morning'  => 'Badge ShiftMorning',
                                            'Evening'  => 'Badge ShiftEvening',
                                            'Night'    => 'Badge ShiftNight',
                                            default    => 'Badge ShiftOff'
                                        };
                                    ?>
                                    <span class="<?= $ShiftClass ?>"><?= $Row['CurrentShift'] ?></span>
                                </td>

                                <!-- Present Toggle -->
                                <td>
                                    <label class="FineCheckbox">
                                        <input type="checkbox"
                                            id="Present_<?= $Row['AttendanceID'] ?>"
                                            <?= $Row['IsPresent'] ? 'checked' : '' ?>
                                            <?= $Row['IsLocked'] ? 'disabled' : '' ?>
                                            onchange="TogglePresent(<?= $Row['AttendanceID'] ?>, this.checked)">
                                        <span style="color: <?= $Row['IsPresent'] ? '#4CAF50' : '#F44336' ?>;">
                                            <?= $Row['IsPresent'] ? '✅ Yes' : '❌ No' ?>
                                        </span>
                                    </label>
                                </td>

                                <!-- Fine Checkboxes -->
                                <td>
                                    <label class="FineCheckbox">
                                        <input type="checkbox"
                                            class="FineCheck"
                                            id="Uniform_<?= $Row['AttendanceID'] ?>"
                                            data-amount="<?= $FineAmounts['UniformFine'] ?>"
                                            data-aid="<?= $Row['AttendanceID'] ?>"
                                            <?= $Row['UniformFine'] > 0 ? 'checked' : '' ?>
                                            <?= $Row['IsLocked'] ? 'disabled' : '' ?>
                                            onchange="RecalcFine(<?= $Row['AttendanceID'] ?>)">
                                        Apply
                                    </label>
                                </td>

                                <td>
                                    <label class="FineCheckbox">
                                        <input type="checkbox"
                                            class="FineCheck"
                                            id="Weapon_<?= $Row['AttendanceID'] ?>"
                                            data-amount="<?= $FineAmounts['WeaponFine'] ?>"
                                            data-aid="<?= $Row['AttendanceID'] ?>"
                                            <?= $Row['WeaponFine'] > 0 ? 'checked' : '' ?>
                                            <?= $Row['IsLocked'] ? 'disabled' : '' ?>
                                            onchange="RecalcFine(<?= $Row['AttendanceID'] ?>)">
                                        Apply
                                    </label>
                                </td>

                                <td>
                                    <label class="FineCheckbox">
                                        <input type="checkbox"
                                            class="FineCheck"
                                            id="Late_<?= $Row['AttendanceID'] ?>"
                                            data-amount="<?= $FineAmounts['LateFine'] ?>"
                                            data-aid="<?= $Row['AttendanceID'] ?>"
                                            <?= $Row['LateFine'] > 0 ? 'checked' : '' ?>
                                            <?= $Row['IsLocked'] ? 'disabled' : '' ?>
                                            onchange="RecalcFine(<?= $Row['AttendanceID'] ?>)">
                                        Apply
                                    </label>
                                </td>

                                <td>
                                    <label class="FineCheckbox">
                                        <input type="checkbox"
                                            class="FineCheck"
                                            id="Conduct_<?= $Row['AttendanceID'] ?>"
                                            data-amount="<?= $FineAmounts['ConductFine'] ?>"
                                            data-aid="<?= $Row['AttendanceID'] ?>"
                                            <?= $Row['ConductFine'] > 0 ? 'checked' : '' ?>
                                            <?= $Row['IsLocked'] ? 'disabled' : '' ?>
                                            onchange="RecalcFine(<?= $Row['AttendanceID'] ?>)">
                                        Apply
                                    </label>
                                </td>

                                <!-- Total Fine Display -->
                                <td>
                                    <span id="TotalFine_<?= $Row['AttendanceID'] ?>"
                                        style="font-weight: 600; color: <?= $Row['TotalFine'] > 0 ? '#F44336' : '#4CAF50' ?>;">
                                        PKR <?= number_format($Row['TotalFine'], 0) ?>
                                    </span>
                                </td>

                                <!-- Lock Status -->
                                <td>
                                    <?php if ($Row['IsLocked']): ?>
                                        <span class="Badge BadgeMuted">🔒 Locked</span>
                                    <?php else: ?>
                                        <span class="Badge BadgeSuccess">✏️ Open</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Lock Button -->
                                <td>
                                    <?php if (!$Row['IsLocked']): ?>
                                        <button class="Btn BtnPrimary BtnSmall"
                                            onclick="LockRow(<?= $Row['AttendanceID'] ?>)">
                                            🔒 Lock Row
                                        </button>
                                    <?php else: ?>
                                        <span style="color: var(--ColorTextMuted); font-size: 12px;">Done</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<script src="js/app.js"></script>
<script src="js/attendance.js"></script>
</body>
</html>
