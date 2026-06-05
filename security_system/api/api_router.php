<?php
// =====================================================
// API ROUTER - api/api_router.php
// All JavaScript fetch() calls come here
// Action parameter tells which function to run
// =====================================================
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

$db     = getConnection();
$Action = $_GET['Action'] ?? $_POST['Action'] ?? '';

// =====================================================
// ROUTE TO CORRECT ACTION
// =====================================================
switch ($Action) {

    // ---- Get single guard profile ----
    case 'GetGuardProfile':
        $GuardID = (int)($_GET['GuardID'] ?? 0);
        if (!$GuardID) {
            echo json_encode(['success' => false, 'message' => 'Invalid Guard ID']);
            break;
        }
        $stmt = $db->prepare("SELECT * FROM Guards WHERE GuardID = ? AND IsArchived = 0");
        $stmt->execute([$GuardID]);
        $Guard = $stmt->fetch();

        if ($Guard) {
            echo json_encode(['success' => true, 'guard' => $Guard]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Guard not found']);
        }
        break;

    // ---- Lock attendance row and calculate fines ----
    case 'LockAttendance':
        $AttendanceID = (int)($_POST['AttendanceID'] ?? 0);
        $UniformFine  = (float)($_POST['UniformFine'] ?? 0);
        $WeaponFine   = (float)($_POST['WeaponFine'] ?? 0);
        $LateFine     = (float)($_POST['LateFine'] ?? 0);
        $ConductFine  = (float)($_POST['ConductFine'] ?? 0);
        $IsPresent    = (int)($_POST['IsPresent'] ?? 1);

        $TotalFine = $UniformFine + $WeaponFine + $LateFine + $ConductFine;

        if (!$AttendanceID) {
            echo json_encode(['success' => false, 'message' => 'Invalid Attendance ID']);
            break;
        }

        try {
            $stmt = $db->prepare("
                UPDATE Attendance
                SET UniformFine = ?, WeaponFine = ?, LateFine = ?, ConductFine = ?,
                    TotalFine = ?, IsPresent = ?, IsLocked = 1
                WHERE AttendanceID = ?
            ");
            $stmt->execute([$UniformFine, $WeaponFine, $LateFine, $ConductFine, $TotalFine, $IsPresent, $AttendanceID]);
            echo json_encode([
                'success'    => true,
                'message'    => 'Row locked successfully!',
                'TotalFine'  => number_format($TotalFine, 2)
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    // ---- Mark attendance record as absent ----
    case 'TogglePresent':
        $AttendanceID = (int)($_POST['AttendanceID'] ?? 0);
        $IsPresent    = (int)($_POST['IsPresent'] ?? 1);

        if (!$AttendanceID) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            break;
        }

        $stmt = $db->prepare("UPDATE Attendance SET IsPresent = ? WHERE AttendanceID = ? AND IsLocked = 0");
        $stmt->execute([$IsPresent, $AttendanceID]);
        echo json_encode(['success' => true]);
        break;

    // ---- Generate payroll for a specific month ----
    case 'GeneratePayroll':
        $PayMonth = $_POST['PayMonth'] ?? '';
        if (!$PayMonth) {
            echo json_encode(['success' => false, 'message' => 'Select a month first']);
            break;
        }

        try {
            $db->beginTransaction();

            // Get all active guards
            $Guards = $db->query("SELECT GuardID, FullName FROM Guards WHERE IsArchived = 0 AND Status = 'Active'")->fetchAll();

            $DailyRate = 45000 / 30; // PKR 45,000 / 30 days

            foreach ($Guards as $Guard) {
                $GID = $Guard['GuardID'];

                // Check if payroll already exists for this month
                $Exists = $db->prepare("SELECT PayrollID FROM Payroll WHERE GuardID = ? AND PayMonth = ?");
                $Exists->execute([$GID, $PayMonth]);
                if ($Exists->fetch()) continue;

                // Count absents for the month
                $AbsentStmt = $db->prepare("
                    SELECT COUNT(*) FROM Attendance
                    WHERE GuardID = ? AND IsPresent = 0
                    AND DATE_FORMAT(AttendanceDate, '%Y-%m') = ?
                ");
                $AbsentStmt->execute([$GID, $PayMonth]);
                $TotalAbsents = (int)$AbsentStmt->fetchColumn();

                // Sum all fines for the month
                $FineStmt = $db->prepare("
                    SELECT COALESCE(SUM(TotalFine), 0) FROM Attendance
                    WHERE GuardID = ? AND IsLocked = 1
                    AND DATE_FORMAT(AttendanceDate, '%Y-%m') = ?
                ");
                $FineStmt->execute([$GID, $PayMonth]);
                $TotalFines = (float)$FineStmt->fetchColumn();

                $BaseSalary      = 45000.00;
                $AbsentDeduction = round($TotalAbsents * $DailyRate, 2);
                $NetSalary       = $BaseSalary - $TotalFines - $AbsentDeduction;
                if ($NetSalary < 0) $NetSalary = 0;

                $InsertStmt = $db->prepare("
                    INSERT INTO Payroll (GuardID, PayMonth, BaseSalary, TotalFines, TotalAbsents, AbsentDeduction, NetSalary)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $InsertStmt->execute([$GID, $PayMonth, $BaseSalary, $TotalFines, $TotalAbsents, $AbsentDeduction, $NetSalary]);
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Payroll generated for ' . $PayMonth]);

        } catch (PDOException $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;

    // ---- Mark payroll as paid ----
    case 'MarkPaid':
        $PayrollID = (int)($_POST['PayrollID'] ?? 0);
        if (!$PayrollID) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            break;
        }
        $db->prepare("UPDATE Payroll SET IsPaid = 1 WHERE PayrollID = ?")->execute([$PayrollID]);
        echo json_encode(['success' => true, 'message' => 'Marked as paid!']);
        break;

    // ---- Default: unknown action ----
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($Action)]);
        break;
}
?>
