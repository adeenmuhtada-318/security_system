// =====================================================
// PAYROLL JAVASCRIPT - payroll.js
// Handles generate payroll and mark as paid actions
// =====================================================

// ---- Generate payroll for selected month ----
function GeneratePayroll() {
    const Month = document.getElementById('MonthPicker').value;

    if (!Month) {
        ShowToast('Please select a month first!', 'danger');
        return;
    }

    const Data = new FormData();
    Data.append('Action',   'GeneratePayroll');
    Data.append('PayMonth', Month);

    ShowToast('Generating payroll... please wait ⏳', 'success');

    fetch('api/api_router.php', { method: 'POST', body: Data })
        .then(function(Res) { return Res.json(); })
        .then(function(Resp) {
            if (Resp.success) {
                ShowToast('✅ ' + Resp.message, 'success');
                setTimeout(function() {
                    window.location.href = 'payroll_hub.php?month=' + Month;
                }, 1500);
            } else {
                ShowToast('❌ ' + Resp.message, 'danger');
            }
        })
        .catch(function() {
            ShowToast('Network error. Please try again.', 'danger');
        });
}

// ---- Mark payroll record as paid ----
function MarkAsPaid(PayrollID) {
    const Data = new FormData();
    Data.append('Action',    'MarkPaid');
    Data.append('PayrollID', PayrollID);

    fetch('api/api_router.php', { method: 'POST', body: Data })
        .then(function(Res) { return Res.json(); })
        .then(function(Resp) {
            if (Resp.success) {
                ShowToast('✅ Marked as paid!', 'success');

                // Update UI without page reload
                const Card = document.getElementById('PayCard_' + PayrollID);
                if (Card) {
                    const NameEl = Card.querySelector('.PayrollCardName');
                    if (NameEl && !NameEl.querySelector('.Badge')) {
                        NameEl.innerHTML += ' <span class="Badge BadgeSuccess" style="font-size: 10px; vertical-align: middle; margin-left: 6px;">PAID</span>';
                    }

                    const Btn = Card.querySelector('button');
                    if (Btn) {
                        Btn.remove();
                    }
                }
            } else {
                ShowToast('Error: ' + Resp.message, 'danger');
            }
        });
}
