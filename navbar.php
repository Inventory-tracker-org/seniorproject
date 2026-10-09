<?php
require_once __DIR__ . '/config.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dataworks</title>
  <!-- <link rel="icon" href="https://dataworks-7b7x.onrender.com/pictures/home/favicon-32x32.png" sizes="32x32" -->
  <link rel="icon" href="<?= url('/pictures/home/favicon-32x32.png') ?>" sizes="32x32"
    type="image/x-icon" />
  <link rel="stylesheet" href="/navbar.css">
</head>

<body>
  <div class="has-navbar">
    <header>
      <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== '' && $_SESSION['role'] !== NULL) { ?>
        <span class="dropdown">

          <a class="dropbtn" href="#"><span>
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="18" x2="21" y2="18" />
              </svg>
              <div class="dropdown-content">
                <!-- <a href="http://localhost:3000/home.php"><img src="https://th.bing.com/th/id/OIP.jwU-GwZPqzDTyOxeKaZ2XgHaEz?w=247&h=180&c=7&r=0&o=7&pid=1.7&rm=3" alt="CSUB Roadrunner" height="130" width="180"> -->
                <a href="<?= url('/home.php') ?>"><img src="https://th.bing.com/th/id/OIP.jwU-GwZPqzDTyOxeKaZ2XgHaEz?w=247&h=180&c=7&r=0&o=7&pid=1.7&rm=3" alt="CSUB Roadrunner" height="130" width="180">
                </a>
                <span class="dropdown2">
                  <a href="#">Audit<svg width="13" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg></a>
                  <span class="dropdown-content2">
                    <!--
                  <a href="http://localhost:3000/audit/upload.php" class="dropdown-element">Start</a>
                    <a href="http://localhost:3000/audit/auditing.php" class="dropdown-element">Continue</a>
                    <a href="http://localhost:3000/audit/audit-history/search-history.php" class="dropdown-element">History</a>
-->
                    <a href="<?= url('/audit/upload.php') ?>" class="dropdown-element">Start</a>
                    <a href="<?= url('/audit/auditing.php') ?>" class="dropdown-element">Continue</a>
                    <a href="<?= url('/audit/audit-history/search-history.php') ?>" class="dropdown-element">History</a>
                  </span>
                </span>
                <!-- <a href="http://localhost:3000/search/search.php">Search</a> -->
                <a href="<?= url('/search/search.php') ?>">Search</a>
                <?php if ($_SESSION['role'] === 'admin') { ?>
                  <span class="dropdown2">
                    <a href="#">Add/Remove<svg width="13" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      </svg></a>
                    <span class="dropdown-content2">
                      <!--
                    <a href="http://localhost:3000/add/add-asset.php" class="dropdown-element">Asset</a>
                      <a href="http://localhost:3000/add/bulk_add.php" class="dropdown-element">Bulk Add Assets</a>
                      <a href="http://localhost:3000/add/add-dept.php" class="dropdown-element">Department</a>
                      <a href="http://localhost:3000/add/add-bldg.php" class="dropdown-element">Building/Rooms</a>
-->
                      <a href="<?= url('/add/add-asset.php') ?>" class="dropdown-element">Asset</a>
                      <a href="<?= url('/add/bulk_add.php') ?>" class="dropdown-element">Bulk Add Assets</a>
                      <a href="<?= url('/add/add-dept.php') ?>" class="dropdown-element">Department</a>
                      <a href="<?= url('/add/add-bldg.php') ?>" class="dropdown-element">Building/Rooms</a>
                    </span>
                  </span>
                <?php } ?>
                <span class="dropdown2">
                  <a href="#">Inventory<svg width="13" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg></a>
                  <span class="dropdown-content2">
                    <!--
                  <a href="http://localhost:3000/asset-manager/manage/manage-profile.php" class="dropdown-element">Profiles</a>
                    <a href="http://localhost:3000/asset-manager/asset-ui.php" class="dropdown-element">Track Assets</a>
-->
                    <a href="<?= url('/asset-manager/manage/manage-profile.php') ?>" class="dropdown-element">Profiles</a>
                    <a href="<?= url('/asset-manager/asset-ui.php') ?>" class="dropdown-element">Track Assets</a>
                  </span>
                </span>
                <h4 class="">Management</h4>
                <!--
                <a href="http://localhost:3000/auth/settings/settings.php">Settings</a>
                <a href="http://localhost:3000/auth/logout.php">Logout</a>
-->
                <a href="<?= url('/auth/settings/settings.php') ?>">Settings</a>
                <a href="<?= url('/auth/logout.php') ?>">Logout</a>
                <?php if ($_SESSION['role'] === 'admin') { ?>
                  <span class="dropdown2">
                    <a class="dropbtn2" href="#">Admin<svg width="13" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      </svg></a>
                    <span class="dropdown-content2">
                      <!-- <a href="http://localhost:3000/auth/signup.php" class="dropdown-element">Signup User</a> -->
                      <a href="<?= url('/auth/signup.php') ?>" class="dropdown-element">Signup User</a>
                    </span>
                  </span>
                <?php } ?>
              </div>
            <?php } ?>
            </span></a></span>
        <!-- <a href="http://localhost:3000/home.php"> -->
        <a href="<?= url('/home.php') ?>">
          <h2 class="gradient-text">CSUB.</h2>
        </a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== '' && $_SESSION['role'] !== NULL) { ?>

          <nav id="nav_links">
            <ul class="nav_links">
              <li>
                <span class="dropdown">
                  <a class="dropbtn">Audit<svg width="13" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </a>
                  <span class="dropdown-content">
                    <!--
                  <a href="http://localhost:3000/audit/upload.php" class="dropdown-element">Start</a>
                    <a href="http://localhost:3000/audit/auditing.php" class="dropdown-element">Continue</a>
                    <a href="http://localhost:3000/audit/audit-history/search-history.php" class="dropdown-element">History</a>
-->
                    <a href="<?= url('/audit/upload.php') ?>" class="dropdown-element">Start</a>
                    <a href="<?= url('/audit/auditing.php') ?>" class="dropdown-element">Continue</a>
                    <a href="<?= url('/audit/audit-history/search-history.php') ?>" class="dropdown-element">History</a>
                  </span>
                </span>
              </li>
              <li>
                <span class="dropdown">
                  <a class="dropbtn">Inventory<svg width="13" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </a>
                  <span class="dropdown-content">
                    <!-- 
                  <a href="http://localhost:3000/asset-manager/manage/manage-profile.php" class="dropdown-element">Profiles</a>
                    <a href="http://localhost:3000/asset-manager/asset-ui.php" class="dropdown-element">Track Assets</a>
-->
                    <a href="<?= url('/asset-manager/manage/manage-profile.php') ?>" class="dropdown-element">Profiles</a>
                    <a href="<?= url('/asset-manager/asset-ui.php') ?>" class="dropdown-element">Track Assets</a>
                  </span>
                </span>
              </li>
              <!--
              <li><a href="http://localhost:3000/search/search.php">Search</a></li>
              <li><a href="http://localhost:3000/package-tracking/package-view.php">Package Tracking</a></li>
-->
              <li><a href="<?= url('/search/search.php') ?>">Search</a></li>
              <?php if ($_SESSION['role'] === 'admin') { ?>
                <li>
                  <span class="dropdown">
                    <a class="dropbtn">Tracking Services<svg width="13" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      </svg>
                    </a>
                    <span class="dropdown-content">
                      <a href="<?= url('/package-tracking/package-view.php') ?>">Tracking Reports</a>
                      <a href="<?= url('/package-tracking/package-view.php') ?>">Package Tracking</a>
                      <a href="<?= url('/package-tracking/package-view.php') ?>">Freight Tracking</a>
                    </span>
                </li>
                <li>
                  <span class="dropdown">
                    <a class="dropbtn">Add/Remove<svg width="13" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      </svg>
                    </a>
                    <span class="dropdown-content">
                      <!--
                    <a href="http://localhost:3000/add/add-asset.php" class="dropdown-element">Asset</a>
                      <a href="http://localhost:3000/add/bulk_add.php" class="dropdown-element">Bulk Add Assets</a>
                      <a href="http://localhost:3000/add/add-dept.php" class="dropdown-element">Department</a>
                      <a href="http://localhost:3000/add/add-bldg.php" class="dropdown-element">Building/Rooms</a>
-->
                      <a href="<?= url('/add/add-asset.php') ?>" class="dropdown-element">Asset</a>
                      <a href="<?= url('/add/bulk_add.php') ?>" class="dropdown-element">Bulk Add Assets</a>
                      <a href="<?= url('/add/add-dept.php') ?>" class="dropdown-element">Department</a>
                      <a href="<?= url('/add/add-bldg.php') ?>" class="dropdown-element">Building/Rooms</a>
                    </span>
                  </span>
                </li>

                <li>
                  <span class="dropdown">
                    <a class="dropbtn">Admin<svg width="13" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      </svg>
                    </a>
                    <span class="dropdown-content">
                      <!-- <a href="http://localhost:3000/auth/signup.php" class="dropdown-element">Signup User</a> -->
                      <a href="<?= url('/auth/signup.php') ?>" class="dropdown-element">Signup User</a>
                    </span>
                  </span>
                </li>
              <?php } ?>
            </ul>
          </nav>
          <!-- <a class="cta" href="http://localhost:3000/auth/logout.php"><button>Logout</button></a> -->
          <a class="cta" href="<?= url('/auth/logout.php') ?>"><button>Logout</button></a>
        <?php } else { ?>
          <!-- <a class="cta" href="http://localhost:3000/auth/login.php"><button>Login</button></a> -->
          <a class="cta" href="<?= url('/auth/login.php') ?>"><button>Login</button></a>
        <?php } ?>

    </header>
  </div>
  <script>
  </script>
</body>

</html>