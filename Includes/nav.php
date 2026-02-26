<?php
require_once __DIR__ . '/../Assets/Database/connectdb.php';
$isDemo = defined('APP_DEMO') && APP_DEMO;
$authUser = (is_array($_SESSION['auth_user'] ?? null) ? $_SESSION['auth_user'] : []);
$userId = (int)($_SESSION['user_id'] ?? ($authUser['user_id'] ?? 0));
$username = (string)($_SESSION['username'] ?? ($authUser['username'] ?? ''));
$role = (string)($_SESSION['role'] ?? ($authUser['role'] ?? ''));
$isLoggedIn = $userId > 0 && $username !== '';
?>
<nav class="navbar bg-gradient-light ">
    <div class="navbar-left">
        <div class="logo">
            <img src="/Assets/Images/Treakers%20Logo.png" alt="Company Logo" class="logo-img" width="70px">
        </div>
        <?php if ($isDemo): ?>
            <span class="badge bg-warning text-dark ml-2">Demo Mode</span>
        <?php endif; ?>
        <div class="navbar-center ml-5">
            <ul class="nav-links">
                <li><a href="/">Home</a></li>
                <li><a href="/other_pages/ProductPage/category_page.php">Categories</a></li>
                <li><a href="/other_pages/ProductPage/allproductsuser.php">Products</a></li>
                <li><a href="/other_pages/BasketPage/cart.php">Basket</a></li>
                <li><a href="/other_pages/AboutUsPage/aboutus.php">About</a></li>
                <li><a href="/other_pages/ContactUsPage/contactus.php">Contact Us</a></li>
            </ul>
        </div>
    </div>
    <div class="navbar-right ml-3">
        <div class="login_buttons">
            <?php if ($isLoggedIn): ?>
                <div class="dropdown bg-gradient-secondary rounded">
                    <button class="logged-button btn bg-gradient-primary rounded fs-5 mr-3">
                        <a href="#" class="nav-link text-white">
                            <span class="text-white"><i class="bi bi-person-check fs-5"></i> <?= htmlspecialchars($username, ENT_QUOTES); ?></span>
                        </a>
                    </button>
                    <div class="dropdown-content">
                        <a href="/other_pages/ProfilePage/profile.php">Your Profile</a>
                        <?php if (strtolower($role) === 'admin'): ?>
                            <a href="/other_pages/AdminPage/adminpage.php">Admin Dashboard</a>
                        <?php endif; ?>
                        <a href="/other_pages/LoginPage/logout.php">Log out</a>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($isDemo): ?>
                    <button class="btn logged-button bg-gradient-primary rounded fs-5 mr-2 mt-3">
                        <a class="nav-link text-white" href="/other_pages/LoginPage/demo_login.php">
                            <i class="bi bi-person fs-5 mr-1"></i>Demo Login
                        </a>
                    </button>
                    <button class="btn logged-button bg-gradient-primary rounded fs-5 mr-2 mt-3">
                        <a class="nav-link text-white" href="/other_pages/LoginPage/demo_admin_login.php">
                            <i class="bi bi-shield-lock fs-5 mr-1"></i>Demo Admin
                        </a>
                    </button>
                <?php endif; ?>
                <button class="btn logged-button bg-gradient-primary rounded fs-5 mr-3 mt-3">
                    <a class="nav-link text-white" href="/other_pages/LoginPage/login_page.php">
                        <i class="bi bi-person fs-5 mr-1"></i>Login/Signup
                    </a>
                </button>
            <?php endif; ?>
        </div>
        <div class="search-bar">
            <input type="text" class="form-control search-input" placeholder="Search">
            <button class="btn search-button bg-gradient-primary"><i class="bi bi-search"></i></button>
            <div class="search-suggestions"></div> <!-- Container for search suggestions -->
        </div>
    </div>
</nav>
