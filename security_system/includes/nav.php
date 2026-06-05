<?php
// =====================================================
// SHARED NAVIGATION SIDEBAR - nav.php
// Include this in every page with: include 'includes/nav.php';
// =====================================================
?>
<nav class="SideNav">

    <div class="SideNavBrand">
        <!-- FAST Logo: use image if available, else text fallback -->
        <?php if (file_exists(__DIR__ . '/../logo.png') || file_exists(__DIR__ . '/../logo.jpg')): ?>
            <?php $LogoFile = file_exists(__DIR__ . '/../logo.png') ? 'logo.png' : 'logo.jpg'; ?>
            <img src="<?= $LogoFile ?>" alt="FAST Logo" class="BrandLogoImg">
        <?php else: ?>
            <div class="BrandLogo">FAST</div>
        <?php endif; ?>
        <div class="BrandTitle">SecureForce</div>
        <div class="BrandSubtitle">FAST Management System</div>
    </div>

    <div class="SideNavMenu">

        <div class="SideNavSection">
            <div class="SideNavSectionLabel">Main</div>

            <a href="dashboard.php" class="SideNavLink">
                <span class="NavIcon">🏠</span>
                <span>Dashboard</span>
            </a>
        </div>

        <div class="SideNavSection">
            <div class="SideNavSectionLabel">Operations</div>

            <a href="attendance_grid.php" class="SideNavLink">
                <span class="NavIcon">📋</span>
                <span>Daily Attendance</span>
            </a>

            <a href="payroll_hub.php" class="SideNavLink">
                <span class="NavIcon">💰</span>
                <span>Payroll & Salaries</span>
            </a>

            <a href="inventory_hub.php" class="SideNavLink">
                <span class="NavIcon">📦</span>
                <span>Inventory</span>
            </a>
        </div>

    </div>

    <div class="SideNavFooter">
        &copy; 2025 SecureForce System<br>
        Academic Project
    </div>

</nav>
