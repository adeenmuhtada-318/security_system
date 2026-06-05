// recruitment.js — handles modals and dynamic UI for recruitment portal

function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('ModalOverlay')) {
        e.target.style.display = 'none';
    }
});

function viewApplication(app) {
    const dob = app.DateOfBirth
        ? new Date(app.DateOfBirth).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' })
        : '—';

    const applied = app.AppliedAt
        ? new Date(app.AppliedAt).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' })
        : '—';

    document.getElementById('ViewModalBody').innerHTML = `
        <div class="DetailGrid">
            <div class="DetailItem">
                <label>Full Name</label>
                <span>${escHtml(app.FullName)}</span>
            </div>
            <div class="DetailItem">
                <label>Father's Name</label>
                <span>${escHtml(app.FatherName)}</span>
            </div>
            <div class="DetailItem">
                <label>CNIC</label>
                <span class="Mono">${escHtml(app.CNIC)}</span>
            </div>
            <div class="DetailItem">
                <label>Phone</label>
                <span>${escHtml(app.Phone)}</span>
            </div>
            <div class="DetailItem">
                <label>Email</label>
                <span>${app.Email ? escHtml(app.Email) : '—'}</span>
            </div>
            <div class="DetailItem">
                <label>Date of Birth</label>
                <span>${dob}</span>
            </div>
            <div class="DetailItem">
                <label>Education</label>
                <span>${escHtml(app.Education)}</span>
            </div>
            <div class="DetailItem">
                <label>Experience</label>
                <span>${app.ExperienceYears} year(s)</span>
            </div>
            <div class="DetailItem">
                <label>Preferred Shift</label>
                <span>${escHtml(app.AppliedShift)}</span>
            </div>
            <div class="DetailItem">
                <label>Applied On</label>
                <span>${applied}</span>
            </div>
            <div class="DetailItem">
                <label>Status</label>
                <span><span class="StatusBadge StatusBadge--${app.Status.toLowerCase()}">${escHtml(app.Status)}</span></span>
            </div>
            <div class="DetailItem DetailItemFull">
                <label>Address</label>
                <span>${escHtml(app.Address)}</span>
            </div>
            ${app.Notes ? `
            <div class="DetailItem DetailItemFull">
                <label>Notes</label>
                <span>${escHtml(app.Notes)}</span>
            </div>` : ''}
        </div>
    `;
    openModal('ViewModal');
}

function openStatusModal(applicationID, currentStatus) {
    document.getElementById('StatusAppID').value = applicationID;
    document.getElementById('StatusSelect').value = currentStatus;
    openModal('StatusModal');
}

function confirmHire(applicationID, name) {
    document.getElementById('HireAppID').value = applicationID;
    document.getElementById('HireConfirmText').textContent =
        `You are about to hire "${name}" and add them to the active guard roster. This cannot be undone.`;
    openModal('HireModal');
}

function confirmDelete(applicationID, name) {
    document.getElementById('DeleteAppID').value = applicationID;
    document.getElementById('DeleteConfirmText').textContent =
        `Are you sure you want to delete the application for "${name}"? This action is permanent.`;
    openModal('DeleteModal');
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
