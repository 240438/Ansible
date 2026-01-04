<?php
// Get hostname
$hostname = gethostname();

// Deployment date
$deployed_date = date("Y-m-d H:i:s");

// Metadata base URL
$metadata_base = "http://169.254.169.254/latest/";

// Function to get IMDSv2 token
function getImdsV2Token() {
    $ch = curl_init("http://169.254.169.254/latest/api/token");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => "PUT",
        CURLOPT_HTTPHEADER     => [
            "X-aws-ec2-metadata-token-ttl-seconds: 21600"
        ],
        CURLOPT_TIMEOUT        => 2
    ]);

    $token = curl_exec($ch);
    curl_close($ch);

    return $token ?: null;
}

// Function to fetch metadata using token
function getMetadata($path, $token) {
    $url = "http://169.254.169.254/latest/meta-data/" . $path;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            "X-aws-ec2-metadata-token: $token"
        ],
        CURLOPT_TIMEOUT        => 2
    ]);

    $value = curl_exec($ch);
    curl_close($ch);

    return $value ?: "N/A";
}

// Fetch token
$token = getImdsV2Token();

// Fetch metadata only if token is available
$instance_id = $token ? getMetadata("instance-id", $token) : "N/A";
$private_ip  = $token ? getMetadata("local-ipv4", $token) : "N/A";
$public_ip   = $token ? getMetadata("public-ipv4", $token) : "N/A";
$public_dns  = $token ? getMetadata("public-hostname", $token) : "N/A";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Frontend Web Server</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }
        h1 {
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .info {
            margin: 15px 0;
            padding: 10px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
        }
        .label {
            font-weight: bold;
            color: #ffd700;
        }
        .info a {
            color: white;           /* same as other values */
            text-decoration: none;  /* remove underline */
            font-weight: normal;
        }

        .info a:hover {
            text-decoration: underline; /* optional: underline on hover */
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Nginx Front End Web Server </h1>

        <div class="info"><span class="label">Hostname:</span> <?= htmlspecialchars($hostname) ?></div>
        <div class="info"><span class="label">Instance ID:</span> <?= htmlspecialchars($instance_id) ?></div>
        <div class="info"><span class="label">Private IP:</span> <?= htmlspecialchars($private_ip) ?></div>
        <div class="info"><span class="label">Public IP:</span> <?= htmlspecialchars($public_ip) ?></div>
        <div class="info"><span class="label">Public DNS:</span>
            <a href="https://<?= htmlspecialchars($public_dns) ?>" target="_blank">
            https://<?= htmlspecialchars($public_dns) ?></a>
        </div>
        <div class="info"><span class="label">Deployed:</span> <?= $deployed_date ?></div>
        <div class="info"><span class="label">Status:</span> ✅ Active and Running</div>
        <div class="info"><span class="label">Managed By:</span> Terraform + Ansible</div>
    </div>
</body>
</html>
