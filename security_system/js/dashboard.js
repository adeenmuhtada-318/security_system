// =====================================================
// DASHBOARD JAVASCRIPT - dashboard.js
// Handles guard profile view, shift modal, archive
// =====================================================

// ---- Load Guard Profile into Modal ----
function ViewGuardProfile(GuardID) {
    OpenModal('GuardProfileModal');
    document.getElementById('GuardProfileBody').innerHTML = '<div class="Spinner"></div>';

    // Fetch guard data from API
    fetch('api/api_router.php?Action=GetGuardProfile&GuardID=' + GuardID)
        .then(function(Response) { return Response.json(); })
        .then(function(Data) {
            if (Data.success) {
                RenderGuardProfile(Data.guard);
            } else {
                document.getElementById('GuardProfileBody').innerHTML =
                    '<div class="AlertBox AlertDanger">❌ Could not load guard profile. ' + Data.message + '</div>';
            }
        })
        .catch(function() {
            document.getElementById('GuardProfileBody').innerHTML =
                '<div class="AlertBox AlertDanger">❌ Network error. Please try again.</div>';
        });
}

// ---- Render guard profile HTML ----
function RenderGuardProfile(Guard) {
    const Initials = GetInitials(Guard.FullName);

    const ShiftColors = {
        'Morning':  '#FFB74D',
        'Evening':  '#7986CB',
        'Night':    '#4FC3F7',
        'Off Duty': '#607D8B'
    };

    const ShiftColor = ShiftColors[Guard.CurrentShift] || '#607D8B';

    const HTML = `
        <div class="ProfileCardHeader">
            <div class="ProfileAvatar">${Initials}</div>
            <div>
                <div style="font-family: var(--FontDisplay); font-size: 20px; font-weight: 700;">
                    ${Guard.FullName}
                </div>
                <div style="margin-top: 4px;">
                    <span class="Badge" style="color: ${ShiftColor}; background: ${ShiftColor}20;">
                        ${Guard.CurrentShift}
                    </span>
                    <span class="Badge BadgeSuccess" style="margin-left: 6px;">${Guard.Status}</span>
                </div>
            </div>
        </div>

        <div class="ProfileInfoGrid">
            <div class="ProfileInfoItem">
                <div class="InfoKey">🪪 CNIC</div>
                <div class="InfoValue" style="font-family: monospace;">${Guard.CNIC}</div>
            </div>
            <div class="ProfileInfoItem">
                <div class="InfoKey">🩸 Blood Group</div>
                <div class="InfoValue">
                    <span class="Badge BadgeWarning">${Guard.BloodGroup || 'Not Set'}</span>
                </div>
            </div>
            <div class="ProfileInfoItem">
                <div class="InfoKey">📱 Phone</div>
                <div class="InfoValue">${Guard.Phone || 'Not Provided'}</div>
            </div>
            <div class="ProfileInfoItem">
                <div class="InfoKey">🚨 Emergency Contact</div>
                <div class="InfoValue">${Guard.EmergencyPhone || 'Not Provided'}</div>
            </div>
            <div class="ProfileInfoItem">
                <div class="InfoKey">📅 Joined On</div>
                <div class="InfoValue">${Guard.JoiningDate}</div>
            </div>
            <div class="ProfileInfoItem">
                <div class="InfoKey">📍 Address</div>
                <div class="InfoValue">${Guard.Address || 'Not Provided'}</div>
            </div>
        </div>
    `;

    document.getElementById('GuardProfileBody').innerHTML = HTML;
}

// ---- Open Shift Change Modal ----
function OpenShiftModal(GuardID, GuardName) {
    document.getElementById('ShiftGuardID').value = GuardID;
    document.getElementById('ShiftGuardName').textContent = GuardName;
    OpenModal('ShiftModal');
}

// ---- Archive Guard ----
function ArchiveGuard(GuardID, GuardName) {
    ConfirmAction(
        'Are you sure you want to archive "' + GuardName + '"?\n\nThey will be removed from active duty but records will be kept.',
        function() {
            document.getElementById('ArchiveGuardID').value = GuardID;
            document.getElementById('ArchiveForm').submit();
        }
    );
}
