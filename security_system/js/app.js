// =====================================================
// SECURITY FIRM - SHARED JAVASCRIPT UTILITIES
// app.js - Used by all pages
// =====================================================

// ---- Highlight active sidebar link ----
function SetActiveNavLink() {
    const CurrentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.SideNavLink').forEach(function(Link) {
        const LinkHref = Link.getAttribute('href');
        if (LinkHref && LinkHref === CurrentPage) {
            Link.classList.add('Active');
        }
    });
}

// ---- Show toast notification ----
function ShowToast(Message, Type) {
    Type = Type || 'success'; // 'success' or 'danger'

    let Container = document.getElementById('ToastContainer');
    if (!Container) {
        Container = document.createElement('div');
        Container.id = 'ToastContainer';
        Container.className = 'ToastContainer';
        document.body.appendChild(Container);
    }

    const Toast = document.createElement('div');
    Toast.className = 'Toast Toast' + (Type === 'success' ? 'Success' : 'Danger');

    const Icon = Type === 'success' ? '✅' : '❌';
    Toast.innerHTML = '<span>' + Icon + '</span><span>' + Message + '</span>';

    Container.appendChild(Toast);

    // Auto-remove after 3 seconds
    setTimeout(function() {
        Toast.style.opacity = '0';
        Toast.style.transform = 'translateX(100%)';
        Toast.style.transition = '0.3s ease';
        setTimeout(function() { Toast.remove(); }, 300);
    }, 3000);
}

// ---- Open modal ----
function OpenModal(ModalId) {
    const Modal = document.getElementById(ModalId);
    if (Modal) {
        Modal.classList.add('Open');
        document.body.style.overflow = 'hidden';
    }
}

// ---- Close modal ----
function CloseModal(ModalId) {
    const Modal = document.getElementById(ModalId);
    if (Modal) {
        Modal.classList.remove('Open');
        document.body.style.overflow = '';
    }
}

// ---- Close modal when clicking outside ----
function SetupModalOutsideClick() {
    document.querySelectorAll('.ModalOverlay').forEach(function(Overlay) {
        Overlay.addEventListener('click', function(Event) {
            if (Event.target === Overlay) {
                Overlay.classList.remove('Open');
                document.body.style.overflow = '';
            }
        });
    });
}

// ---- Update date/time display ----
function UpdateDateTime() {
    const DateEl = document.getElementById('CurrentDateTime');
    if (!DateEl) return;

    const Now = new Date();
    const Options = {
        weekday: 'short', year: 'numeric',
        month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    };
    DateEl.textContent = Now.toLocaleDateString('en-PK', Options);
}

// ---- Format currency in PKR ----
function FormatPKR(Amount) {
    return 'PKR ' + parseFloat(Amount).toLocaleString('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// ---- Get initials from name for avatar ----
function GetInitials(Name) {
    const Parts = Name.trim().split(' ');
    if (Parts.length >= 2) {
        return (Parts[0][0] + Parts[1][0]).toUpperCase();
    }
    return Parts[0].substring(0, 2).toUpperCase();
}

// ---- Confirm action with simple dialog ----
function ConfirmAction(Message, OnConfirm) {
    if (window.confirm(Message)) {
        OnConfirm();
    }
}

// ---- Initialize on page load ----
document.addEventListener('DOMContentLoaded', function() {
    SetActiveNavLink();
    SetupModalOutsideClick();
    UpdateDateTime();
    setInterval(UpdateDateTime, 60000); // update every minute
});
