<?php

// ค่าเหล่านี้ให้คุณแทนที่ด้วยข้อมูลจริงของคุณ
$clientId = '2006525783';
$clientSecret = 'cac877c5e22be00fb6b34178a93a4f5d';
$redirectUri = 'https://www.owtsoft.com/callback.php';

// ตรวจสอบว่ามี Authorization Code จากการอนุญาตหรือไม่
if (isset($_GET['code'])) {
    $authorizationCode = $_GET['code'];

    // Step 1: ขอ Access Token โดยใช้ Authorization Code
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.line.me/oauth2/v2.1/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $authorizationCode,
        'redirect_uri' => $redirectUri,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $accessToken = $data['access_token'] ?? null;

    if ($accessToken) {
        // Step 2: ใช้ Access Token เพื่อดึงข้อมูลโปรไฟล์ของผู้ใช้
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.line.me/v2/profile');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
        ]);

        $profileResponse = curl_exec($ch);
        curl_close($ch);

        $profileData = json_decode($profileResponse, true);

        // แสดงข้อมูลผู้ใช้
        if (isset($profileData['userId'])) {
            echo 'User ID: ' . htmlspecialchars($profileData['userId']) . '<br>';
            echo 'Display Name: ' . htmlspecialchars($profileData['displayName']) . '<br>';
            if (isset($profileData['pictureUrl'])) {
                echo '<img src="' . htmlspecialchars($profileData['pictureUrl']) . '" alt="Profile Picture"><br>';
            }
        } else {
            echo 'ไม่สามารถดึงข้อมูลโปรไฟล์ผู้ใช้ได้';
        }
    } else {
        echo 'ไม่สามารถดึง Access Token ได้';
    }
} else {
    // URL เพื่อให้ผู้ใช้อนุญาตการเข้าถึง
    $state = bin2hex(random_bytes(16)); // สุ่มค่า state เพื่อป้องกัน CSRF
    $authUrl = 'https://access.line.me/oauth2/v2.1/authorize?response_type=code&client_id=' . $clientId
        . '&redirect_uri=' . urlencode($redirectUri)
        . '&state=' . $state
        . '&scope=profile%20openid';

    echo '<a href="' . $authUrl . '">Login with LINE</a>';
}
