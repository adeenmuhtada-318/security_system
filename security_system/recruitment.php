<?php
require_once 'includes/db_connect.php';
$db = getConnection();

// Fetch stats
$TotalApps      = $db->query("SELECT COUNT(*) FROM Recruitment")->fetchColumn();
$Pending        = $db->query("SELECT COUNT(*) FROM Recruitment WHERE Status = 'Pending'")->fetchColumn();
$Shortlisted    = $db->query("SELECT COUNT(*) FROM Recruitment WHERE Status = 'Shortlisted'")->fetchColumn();
$Hired          = $db->query("SELECT COUNT(*) FROM Recruitment WHERE Status = 'Hired'")->fetchColumn();

// Filter by status
$FilterStatus = $_GET['status'] ?? 'All';
$AllowedFilters = ['All', 'Pending', 'Shortlisted', 'Hired', 'Rejected'];
if (!in_array($FilterStatus, $AllowedFilters)) $FilterStatus = 'All';

if ($FilterStatus === 'All') {
    $Applications = $db->query("SELECT * FROM Recruitment ORDER BY AppliedAt DESC")->fetchAll();
} else {
    $stmt = $db->prepare("SELECT * FROM Recruitment WHERE Status = ? ORDER BY AppliedAt DESC");
    $stmt->execute([$FilterStatus]);
    $Applications = $stmt->fetchAll();
}

// Success/error messages
$SuccessMsg = '';
$ErrorMsg   = '';
if (isset($_GET['added']))     $SuccessMsg = 'Application submitted successfully!';
if (isset($_GET['updated']))   $SuccessMsg = 'Application status updated.';
if (isset($_GET['hired']))     $SuccessMsg = 'Guard hired and added to active roster!';
if (isset($_GET['deleted']))   $SuccessMsg = 'Application removed.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruitment Portal – SecureForce</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/recruitment.css">
</head>
<body>
<div class="AppShell">

    <?php include 'includes/nav.php'; ?>

    <main class="PageContent">

        <div class="PageHeader">
            <div>
                <h1 class="PageTitle">Recruitment Portal</h1>
                <p class="PageSubtitle">Manage guard applications — from submission to hiring</p>
            </div>
            <button class="BtnPrimary" onclick="openModal('AddApplicationModal')">+ New Application</button>
        </div>

        <?php if ($SuccessMsg): ?>
            <div class="AlertSuccess"><?= htmlspecialchars($SuccessMsg) ?></div>
        <?php endif; ?>

        <?php if ($ErrorMsg): ?>
            <div class="AlertDanger"><?= htmlspecialchars($ErrorMsg) ?></div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="StatsRow">
            <div class="StatCard">
                <div class="StatValue"><?= $TotalApps ?></div>
                <div class="StatLabel">Total Applications</div>
            </div>
            <div class="StatCard StatCardWarning">
                <div class="StatValue"><?= $Pending ?></div>
                <div class="StatLabel">Pending Review</div>
            </div>
            <div class="StatCard StatCardBlue">
                <div class="StatValue"><?= $Shortlisted ?></div>
                <div class="StatLabel">Shortlisted</div>
            </div>
            <div class="StatCard StatCardSuccess">
                <div class="StatValue"><?= $Hired ?></div>
                <div class="StatLabel">Hired</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="FilterTabs">
            <?php foreach ($AllowedFilters as $f): ?>
                <a href="recruitment.php?status=<?= $f ?>"
                   class="FilterTab <?= $FilterStatus === $f ? 'active' : '' ?>">
                    <?= $f ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Applications Table -->
        <div class="Card">
            <div class="CardHeader">
                <span>Applications (<?= count($Applications) ?>)</span>
            </div>
            <div class="TableWrapper">
                <table class="DataTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>CNIC</th>
                            <th>Phone</th>
                            <th>Education</th>
                            <th>Experience</th>
                            <th>Preferred Shift</th>
                            <th>Status</th>
                            <th>Applied</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($Applications)): ?>
                            <tr>
                                <td colspan="10" style="text-align:center; padding:30px; color:var(--ColorTextMuted);">
                                    No applications found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($Applications as $App): ?>
                                <tr>
                                    <td><?= $App['ApplicationID'] ?></td>
                                    <td class="FontBold"><?= htmlspecialchars($App['FullName']) ?></td>
                                    <td class="Mono"><?= htmlspecialchars($App['CNIC']) ?></td>
                                    <td><?= htmlspecialchars($App['Phone']) ?></td>
                                    <td><?= htmlspecialchars($App['Education']) ?></td>
                                    <td><?= $App['ExperienceYears'] ?> yr<?= $App['ExperienceYears'] != 1 ? 's' : '' ?></td>
                                    <td><?= htmlspecialchars($App['AppliedShift']) ?></td>
                                    <td>
                                        <span class="StatusBadge StatusBadge--<?= strtolower($App['Status']) ?>">
                                            <?= $App['Status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($App['AppliedAt'])) ?></td>
                                    <td class="ActionCell">
                                        <button class="BtnIcon BtnView"
                                            title="View Details"
                                            onclick="viewApplication(<?= htmlspecialchars(json_encode($App)) ?>)">
                                            👁
                                        </button>
                                        <?php if ($App['Status'] !== 'Hired' && $App['Status'] !== 'Rejected'): ?>
                                            <button class="BtnIcon BtnEdit"
                                                title="Update Status"
                                                onclick="openStatusModal(<?= $App['ApplicationID'] ?>, '<?= $App['Status'] ?>')">
                                                ✏️
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($App['Status'] === 'Shortlisted'): ?>
                                            <button class="BtnIcon BtnHire"
                                                title="Hire This Guard"
                                                onclick="confirmHire(<?= $App['ApplicationID'] ?>, '<?= htmlspecialchars($App['FullName']) ?>')">
                                                ✅
                                            </button>
                                        <?php endif; ?>
                                        <button class="BtnIcon BtnDelete"
                                            title="Delete Application"
                                            onclick="confirmDelete(<?= $App['ApplicationID'] ?>, '<?= htmlspecialchars($App['FullName']) ?>')">
                                            🗑
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- Add Application Modal -->
<div id="AddApplicationModal" class="ModalOverlay" style="display:none;">
    <div class="Modal">
        <div class="ModalHeader">
            <h2 class="ModalTitle">New Application</h2>
            <button class="ModalClose" onclick="closeModal('AddApplicationModal')">✕</button>
        </div>
        <form method="POST" action="api/recruitment_api.php?Action=AddApplication" class="ModalForm">
            <div class="FormGrid">
                <div class="FormGroup">
                    <label>Full Name *</label>
                    <input type="text" name="FullName" required placeholder="Muhammad Ali">
                </div>
                <div class="FormGroup">
                    <label>Father's Name *</label>
                    <input type="text" name="FatherName" required placeholder="Haji Akbar">
                </div>
                <div class="FormGroup">
                    <label>CNIC *</label>
                    <input type="text" name="CNIC" required placeholder="35201-1234567-1">
                </div>
                <div class="FormGroup">
                    <label>Phone *</label>
                    <input type="text" name="Phone" required placeholder="0300-1234567">
                </div>
                <div class="FormGroup">
                    <label>Email</label>
                    <input type="email" name="Email" placeholder="optional@email.com">
                </div>
                <div class="FormGroup">
                    <label>Date of Birth *</label>
                    <input type="date" name="DateOfBirth" required>
                </div>
                <div class="FormGroup">
                    <label>Education *</label>
                    <select name="Education" required>
                        <option value="">-- Select --</option>
                        <option value="Matric">Matric</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Bachelor">Bachelor</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="FormGroup">
                    <label>Experience (Years)</label>
                    <input type="number" name="ExperienceYears" min="0" max="30" value="0">
                </div>
                <div class="FormGroup">
                    <label>Preferred Shift</label>
                    <select name="AppliedShift">
                        <option value="Any">Any</option>
                        <option value="Morning">Morning</option>
                        <option value="Evening">Evening</option>
                        <option value="Night">Night</option>
                    </select>
                </div>
                <div class="FormGroup FormGroupFull">
                    <label>Address *</label>
                    <textarea name="Address" required rows="2" placeholder="Full residential address"></textarea>
                </div>
            </div>
            <div class="ModalFooter">
                <button type="button" class="BtnSecondary" onclick="closeModal('AddApplicationModal')">Cancel</button>
                <button type="submit" class="BtnPrimary">Submit Application</button>
            </div>
        </form>
    </div>
</div>

<!-- View Details Modal -->
<div id="ViewModal" class="ModalOverlay" style="display:none;">
    <div class="Modal">
        <div class="ModalHeader">
            <h2 class="ModalTitle">Application Details</h2>
            <button class="ModalClose" onclick="closeModal('ViewModal')">✕</button>
        </div>
        <div id="ViewModalBody" class="ModalBody"></div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="StatusModal" class="ModalOverlay" style="display:none;">
    <div class="Modal ModalSmall">
        <div class="ModalHeader">
            <h2 class="ModalTitle">Update Status</h2>
            <button class="ModalClose" onclick="closeModal('StatusModal')">✕</button>
        </div>
        <form method="POST" action="api/recruitment_api.php?Action=UpdateStatus">
            <div class="ModalBody">
                <input type="hidden" name="ApplicationID" id="StatusAppID">
                <div class="FormGroup">
                    <label>New Status</label>
                    <select name="NewStatus" id="StatusSelect">
                        <option value="Pending">Pending</option>
                        <option value="Shortlisted">Shortlisted</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
                <div class="FormGroup">
                    <label>Notes (optional)</label>
                    <textarea name="Notes" rows="3" placeholder="Add any remarks..."></textarea>
                </div>
            </div>
            <div class="ModalFooter">
                <button type="button" class="BtnSecondary" onclick="closeModal('StatusModal')">Cancel</button>
                <button type="submit" class="BtnPrimary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Hire Confirmation Modal -->
<div id="HireModal" class="ModalOverlay" style="display:none;">
    <div class="Modal ModalSmall">
        <div class="ModalHeader">
            <h2 class="ModalTitle">Confirm Hire</h2>
            <button class="ModalClose" onclick="closeModal('HireModal')">✕</button>
        </div>
        <form method="POST" action="api/recruitment_api.php?Action=HireGuard">
            <div class="ModalBody">
                <input type="hidden" name="ApplicationID" id="HireAppID">
                <p id="HireConfirmText" style="color:var(--ColorTextSecondary); margin-bottom:16px;"></p>
                <div class="FormGroup">
                    <label>Joining Date *</label>
                    <input type="date" name="JoiningDate" required
                        value="<?= date('Y-m-d') ?>">
                </div>
                <div class="FormGroup">
                    <label>Assign Shift</label>
                    <select name="AssignedShift">
                        <option value="Morning">Morning</option>
                        <option value="Evening">Evening</option>
                        <option value="Night">Night</option>
                        <option value="Off Duty">Off Duty</option>
                    </select>
                </div>
            </div>
            <div class="ModalFooter">
                <button type="button" class="BtnSecondary" onclick="closeModal('HireModal')">Cancel</button>
                <button type="submit" class="BtnHireConfirm">Hire & Add to System</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="DeleteModal" class="ModalOverlay" style="display:none;">
    <div class="Modal ModalSmall">
        <div class="ModalHeader">
            <h2 class="ModalTitle">Delete Application</h2>
            <button class="ModalClose" onclick="closeModal('DeleteModal')">✕</button>
        </div>
        <form method="POST" action="api/recruitment_api.php?Action=DeleteApplication">
            <div class="ModalBody">
                <input type="hidden" name="ApplicationID" id="DeleteAppID">
                <p id="DeleteConfirmText" style="color:var(--ColorTextSecondary);"></p>
            </div>
            <div class="ModalFooter">
                <button type="button" class="BtnSecondary" onclick="closeModal('DeleteModal')">Cancel</button>
                <button type="submit" class="BtnDanger">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<script src="js/recruitment.js"></script>
</body>
</html>
