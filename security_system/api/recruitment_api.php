<?php
require_once '../includes/db_connect.php';

$db     = getConnection();
$Action = $_GET['Action'] ?? $_POST['Action'] ?? '';

switch ($Action) {

    case 'AddApplication':
        $FullName        = trim($_POST['FullName']        ?? '');
        $FatherName      = trim($_POST['FatherName']      ?? '');
        $CNIC            = trim($_POST['CNIC']            ?? '');
        $Phone           = trim($_POST['Phone']           ?? '');
        $Email           = trim($_POST['Email']           ?? '');
        $DateOfBirth     = $_POST['DateOfBirth']          ?? '';
        $Address         = trim($_POST['Address']         ?? '');
        $Education       = $_POST['Education']            ?? '';
        $ExperienceYears = (int)($_POST['ExperienceYears']?? 0);
        $AppliedShift    = $_POST['AppliedShift']         ?? 'Any';

        if (!$FullName || !$FatherName || !$CNIC || !$Phone || !$DateOfBirth || !$Address || !$Education) {
            header('Location: ../recruitment.php?error=missing_fields');
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO Recruitment
                (FullName, FatherName, CNIC, Phone, Email, DateOfBirth, Address, Education, ExperienceYears, AppliedShift)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$FullName, $FatherName, $CNIC, $Phone, $Email, $DateOfBirth, $Address, $Education, $ExperienceYears, $AppliedShift]);
        header('Location: ../recruitment.php?added=1');
        exit;

    case 'UpdateStatus':
        $ApplicationID = (int)($_POST['ApplicationID'] ?? 0);
        $NewStatus     = $_POST['NewStatus']           ?? '';
        $Notes         = trim($_POST['Notes']          ?? '');
        $Allowed       = ['Pending', 'Shortlisted', 'Rejected'];

        if (!$ApplicationID || !in_array($NewStatus, $Allowed)) {
            header('Location: ../recruitment.php?error=invalid');
            exit;
        }

        $stmt = $db->prepare("UPDATE Recruitment SET Status = ?, Notes = ? WHERE ApplicationID = ?");
        $stmt->execute([$NewStatus, $Notes, $ApplicationID]);
        header('Location: ../recruitment.php?updated=1');
        exit;

    case 'HireGuard':
        $ApplicationID = (int)($_POST['ApplicationID'] ?? 0);
        $JoiningDate   = $_POST['JoiningDate']          ?? '';
        $AssignedShift = $_POST['AssignedShift']         ?? 'Off Duty';

        if (!$ApplicationID || !$JoiningDate) {
            header('Location: ../recruitment.php?error=missing_fields');
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM Recruitment WHERE ApplicationID = ? AND Status = 'Shortlisted'");
        $stmt->execute([$ApplicationID]);
        $App = $stmt->fetch();

        if (!$App) {
            header('Location: ../recruitment.php?error=not_found');
            exit;
        }

        try {
            $db->beginTransaction();

            // Add to Guards table
            $insertGuard = $db->prepare("
                INSERT INTO Guards (FullName, CNIC, Phone, Address, JoiningDate, CurrentShift, Status)
                VALUES (?, ?, ?, ?, ?, ?, 'Active')
            ");
            $insertGuard->execute([
                $App['FullName'],
                $App['CNIC'],
                $App['Phone'],
                $App['Address'],
                $JoiningDate,
                $AssignedShift
            ]);

            // Mark application as Hired
            $db->prepare("UPDATE Recruitment SET Status = 'Hired' WHERE ApplicationID = ?")
               ->execute([$ApplicationID]);

            $db->commit();
            header('Location: ../recruitment.php?hired=1');
            exit;

        } catch (PDOException $e) {
            $db->rollBack();
            header('Location: ../recruitment.php?error=duplicate_cnic');
            exit;
        }

    case 'DeleteApplication':
        $ApplicationID = (int)($_POST['ApplicationID'] ?? 0);
        if ($ApplicationID) {
            $db->prepare("DELETE FROM Recruitment WHERE ApplicationID = ?")
               ->execute([$ApplicationID]);
        }
        header('Location: ../recruitment.php?deleted=1');
        exit;

    default:
        header('Location: ../recruitment.php');
        exit;
}
?>
