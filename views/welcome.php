<?php
session_start();
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

$auth = new Auth();

// If already logged in, redirect to dashboard
if ($auth->check()) {
    header('Location: /ScrapingToolsAutoSync/dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Scraping Management System</title>

    <!-- Google Fonts - Merriweather -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *:not(.fa):not(.fas):not(.far):not(.fab):not(.fal):not(.fad) {
            font-family: 'Merriweather' !important;
        }
        body {
            font-family: 'Merriweather';
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .welcome-card {
            max-width: 600px;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0,0,0,0.3);
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .container {
            display: flex;
            justify-content: center;
        }
        a.btn {
            background-color: #CE9335;
            color: white;
        }
        a.btn:hover {
            background-color: #0b5ed7;
            color: white;
        }
        
    </style>
</head>
<body>
    <div class="container">
        <div class="card welcome-card">
            <div class="card-body text-center p-5">
                <img src="https://internationalpropertyalerts.com/wp-content/uploads/2025/07/IPA-Gold-Blue-Logo-small.png" style="width: 150px; margin-bottom: 30px;">
                <h1 class="mb-4">Web Scraping Management System</h1>
                <p class="lead mb-4">
                    A modern system by International Property Alerts (IPA) for managing and monitoring automated property data scrapers.
                </p>

                <div class="d-grid gap-2 mb-4">
                    <a href="/ScrapingToolsAutoSync/login" class="btn btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Login to Continue
                    </a>
                </div>

                <hr class="my-4">

                <div class="row text-start">
                    <div class="col-md-6 mb-3">
                        <h6><i class="fas fa-chart-line text-primary me-2"></i>Dashboard</h6>
                        <small class="text-muted">Statistics and charts</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6><i class="fas fa-play-circle text-success me-2"></i>Running Tools</h6>
                        <small class="text-muted">Real-time monitoring</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6><i class="fas fa-cog text-info me-2"></i>Configurations</h6>
                        <small class="text-muted">Manage scrapers</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6><i class="fas fa-terminal text-warning me-2"></i>Live Console</h6>
                        <small class="text-muted">Real-time output</small>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
