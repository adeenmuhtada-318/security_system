// =====================================================
// ATTENDANCE JAVASCRIPT - attendance.js
// Handles fines, locking rows, present toggle
// =====================================================

// ---- Recalculate displayed fine total when checkboxes change ----
function RecalcFine(AttendanceID) {
    const Checkboxes = document.querySelectorAll('.FineCheck[data-aid="' + AttendanceID + '"]');
    let Total = 0;

    Checkboxes.forEach(function(CB) {
        if (CB.checked) {
            Total += parseFloat(CB.dataset.amount || 0);
        }
    });

    const TotalEl = document.getElementById('TotalFine_' + AttendanceID);
    if (TotalEl) {
        TotalEl.textContent = 'PKR ' + Total.toLocaleString();
        TotalEl.style.color = Total > 0 ? '#F44336' : '#4CAF50';
    }
}

// ---- Toggle present/absent ----
function TogglePresent(AttendanceID, IsPresent) {
    const Data = new FormData();
    Data.append('Action', 'TogglePresent');
    Data.append('AttendanceID', AttendanceID);
    Data.append('IsPresent', IsPresent ? 1 : 0);

    fetch('api/api_router.php', { method: 'POST', body: Data })
        .then(function(Res) { return Res.json(); })
        .then(function(Data) {
            if (Data.success) {
                ShowToast('Attendance updated!', 'success');
            } else {
                ShowToast('Error: ' + Data.message, 'danger');
            }
        });
}

// ---- Lock attendance row ----
function LockRow(AttendanceID) {
    // Get checkbox states for all fines
    const UniformCB = document.getElementById('Uniform_' + AttendanceID);
    const WeaponCB  = document.getElementById('Weapon_'  + AttendanceID);
    const LateCB    = document.getElementById('Late_'    + AttendanceID);
    const ConductCB = document.getElementById('Conduct_' + AttendanceID);
    const PresentCB = document.getElementById('Present_' + AttendanceID);

    const UniformFine = (UniformCB && UniformCB.checked) ? parseFloat(UniformCB.dataset.amount) : 0;
    const WeaponFine  = (WeaponCB  && WeaponCB.checked)  ? parseFloat(WeaponCB.dataset.amount)  : 0;
    const LateFine    = (LateCB    && LateCB.checked)    ? parseFloat(LateCB.dataset.amount)    : 0;
    const ConductFine = (ConductCB && ConductCB.checked) ? parseFloat(ConductCB.dataset.amount) : 0;
    const IsPresent   = (PresentCB && PresentCB.checked) ? 1 : 0;

    const Data = new FormData();
    Data.append('Action',        'LockAttendance');
    Data.append('AttendanceID',  AttendanceID);
    Data.append('UniformFine',   UniformFine);
    Data.append('WeaponFine',    WeaponFine);
    Data.append('LateFine',      LateFine);
    Data.append('ConductFine',   ConductFine);
    Data.append('IsPresent',     IsPresent);

    fetch('api/api_router.php', { method: 'POST', body: Data })
        .then(function(Res) { return Res.json(); })
        .then(function(Resp) {
            if (Resp.success) {
                ShowToast('Row locked! Fine: PKR ' + Resp.TotalFine, 'success');

                // Update UI to show locked state
                const Row = document.getElementById('Row_' + AttendanceID);
                if (Row) {
                    Row.style.opacity = '0.7';

                    // Disable all checkboxes in this row
                    Row.querySelectorAll('input[type="checkbox"]').forEach(function(CB) {
                        CB.disabled = true;
                    });

                    // Replace lock button with "Done" text
                    const LockBtn = Row.querySelector('button');
                    if (LockBtn) {
                        LockBtn.outerHTML = '<span style="color: var(--ColorTextMuted); font-size: 12px;">Done</span>';
                    }

                    // Update status badge
                    const StatusCell = Row.cells[9];
                    if (StatusCell) {
                        StatusCell.innerHTML = '<span class="Badge BadgeMuted">🔒 Locked</span>';
                    }
                }
            } else {
                ShowToast('Error: ' + Resp.message, 'danger');
            }
        })
        .catch(function() {
            ShowToast('Network error. Please try again.', 'danger');
        });
}
