<?php
// =====================================================
// PAYROLL HUB - payroll_hub.php
// Salary generation, fines deduction, pay records
// =====================================================
session_start();
if (!isset($_SESSION['LoggedIn']) || $_SESSION['LoggedIn'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db_connect.php';
$db = getConnection();

// ---- Default to current month ----
$SelectedMonth = $_GET['month'] ?? date('Y-m');

// ---- Fetch payroll records for selected month ----
$PayrollStmt = $db->prepare("
    SELECT p.*, g.FullName
    FROM Payroll p
    JOIN Guards g ON p.GuardID = g.GuardID
    WHERE p.PayMonth = ?
    ORDER BY g.FullName ASC
");
$PayrollStmt->execute([$SelectedMonth]);
$PayrollRecords = $PayrollStmt->fetchAll();

// ---- Stats ----
$TotalNet  = array_sum(array_column($PayrollRecords, 'NetSalary'));
$PaidCount = count(array_filter($PayrollRecords, fn($R) => $R['IsPaid']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Hub | SecureForce</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .PayrollGrid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
        }
    </style>
</head>
<body>

<div class="AppShell">
    <?php include 'includes/nav.php'; ?>

    <main class="MainContent">

        <div class="TopBar">
            <div class="TopBarTitle">💰 Payroll & Salary Hub</div>
            <div class="TopBarRight">
                <div class="TopBarDate">
                    <span class="StatusDot"></span>
                    <span id="CurrentDateTime"></span>
                </div>
            </div>
        </div>

        <div class="PageContent">

            <!-- Month Selector + Generate Button -->
            <div class="SectionPanel" style="margin-bottom: 20px;">
                <div class="SectionBody" style="padding: 16px 22px;">
                    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label class="FormLabel" style="margin: 0; white-space: nowrap;">📅 Select Month:</label>
                            <input type="month" id="MonthPicker" class="FormControl" style="width: auto;"
                                value="<?= htmlspecialchars($SelectedMonth) ?>"
                                onchange="window.location.href='payroll_hub.php?month=' + this.value">
                        </div>
                        <button class="Btn BtnPrimary" onclick="GeneratePayroll()">
                            ⚡ Generate Payroll for <?= date('F Y', strtotime($SelectedMonth . '-01')) ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <?php if (!empty($PayrollRecords)): ?>
            <div class="StatsGrid" style="margin-bottom: 20px;">

                <div class="StatCard" style="--CardAccent: #FF9800;">
                    <div class="StatCardIcon" style="background: rgba(255,152,0,0.12);">👮</div>
                    <div class="StatCardBody">
                        <div class="StatValue"><?= count($PayrollRecords) ?></div>
                        <div class="StatLabel">Guards in Payroll</div>
                    </div>
                </div>

                <div class="StatCard" style="--CardAccent: #4CAF50;">
                    <div class="StatCardIcon" style="background: rgba(76,175,80,0.12);">✅</div>
                    <div class="StatCardBody">
                        <div class="StatValue"><?= $PaidCount ?></div>
                        <div class="StatLabel">Paid</div>
                    </div>
                </div>

                <div class="StatCard" style="--CardAccent: #5B7F95;">
                    <div class="StatCardIcon" style="background: rgba(91,127,149,0.12);">💵</div>
                    <div class="StatCardBody">
                        <div class="StatValue" style="font-size: 18px;">PKR <?= number_format($TotalNet, 0) ?></div>
                        <div class="StatLabel">Total Net Payout</div>
                    </div>
                </div>

            </div>
            <?php endif; ?>

            <!-- Payroll Cards -->
            <?php if (empty($PayrollRecords)): ?>
                <div class="SectionPanel">
                    <div class="EmptyState">
                        <div class="EmptyIcon">💰</div>
                        <p>No payroll generated for <?= date('F Y', strtotime($SelectedMonth . '-01')) ?>.</p>
                        <p style="margin-top: 8px; font-size: 13px;">Click "Generate Payroll" above to create salary records.</p>
                    </div>
                </div>
            <?php else: ?>

                <div class="SectionPanel">
                    <div class="SectionHeader">
                        <div class="SectionTitle">📄 Salary Records — <?= date('F Y', strtotime($SelectedMonth . '-01')) ?></div>
                    </div>
                    <div class="SectionBody">
                        <div class="PayrollGrid">
                            <?php foreach ($PayrollRecords as $P): ?>
                            <div class="PayrollCard" id="PayCard_<?= $P['PayrollID'] ?>">

                                <div class="PayrollCardName">
                                    <?= htmlspecialchars($P['FullName']) ?>
                                    <?php if ($P['IsPaid']): ?>
                                        <span class="Badge BadgeSuccess" style="font-size: 10px; vertical-align: middle; margin-left: 6px;">PAID</span>
                                    <?php endif; ?>
                                </div>

                                <div class="PayrollRow">
                                    <span>Base Salary</span>
                                    <span>PKR <?= number_format($P['BaseSalary'], 0) ?></span>
                                </div>

                                <div class="PayrollRow">
                                    <span>Days Absent</span>
                                    <span class="PayrollDeduct"><?= $P['TotalAbsents'] ?> days</span>
                                </div>

                                <div class="PayrollRow">
                                    <span>Absent Deduction</span>
                                    <span class="PayrollDeduct">- PKR <?= number_format($P['AbsentDeduction'], 0) ?></span>
                                </div>

                                <div class="PayrollRow">
                                    <span>Fine Deductions</span>
                                    <span class="PayrollDeduct">- PKR <?= number_format($P['TotalFines'], 0) ?></span>
                                </div>

                                <div class="PayrollRow" style="border-top: 2px solid var(--ColorBorder); margin-top: 8px; padding-top: 8px;">
                                    <span style="font-weight: 600;">Net Salary</span>
                                    <span class="PayrollNet">PKR <?= number_format($P['NetSalary'], 0) ?></span>
                                </div>

                                <?php if (!$P['IsPaid']): ?>
                                <div style="margin-top: 14px;">
                                    <button class="Btn BtnSuccess BtnFull BtnSmall"
                                        onclick="MarkAsPaid(<?= $P['PayrollID'] ?>)">
                                        ✅ Mark as Paid
                                    </button>
                                </div>
                                <?php endif; ?>

                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </main>
</div>

<script src="js/app.js"></script>
<script src="js/payroll.js"></script>
<script>
    // Pass selected month to JS
    const SelectedPayMonth = '<?= htmlspecialchars($SelectedMonth) ?>';
</script>
</body>
</html>
