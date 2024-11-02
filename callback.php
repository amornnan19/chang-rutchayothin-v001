<?php

// ตั้งค่า Channel ID, Channel Secret, และ Redirect URI
$clientId = '2006525783';
$clientSecret = 'cac877c5e22be00fb6b34178a93a4f5d';
$redirectUri = 'https://chang-ruchayothin.test/callback.php';

// ตรวจสอบว่าได้รับ Authorization Code จากการอนุญาตหรือไม่
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

        session_start();
        $_SESSION['user_data'] = $profileData;

        // แสดงข้อมูลที่ได้รับกลับมาทั้งหมด
        echo '<h2>Response from LINE Login API</h2>';
        echo '<pre>' . htmlspecialchars(print_r($data, true)) . '</pre>';

        echo '<h2>Profile Information from LINE</h2>';
        echo '<pre>' . htmlspecialchars(print_r($profileData, true)) . '</pre>';

        // แสดงรายละเอียดข้อมูลโปรไฟล์ในรูปแบบอ่านง่าย
        if (isset($profileData['userId'])) {
            echo '<h3>User Information</h3>';
            echo 'User ID: ' . htmlspecialchars($profileData['userId']) . '<br>';
            echo 'Display Name: ' . htmlspecialchars($profileData['displayName']) . '<br>';
            if (isset($profileData['pictureUrl'])) {
                echo 'Profile Picture:<br><img src="' . htmlspecialchars($profileData['pictureUrl']) . '" alt="Profile Picture"><br>';
            }
        } else {
            echo '<p>ไม่สามารถดึงข้อมูลโปรไฟล์ผู้ใช้ได้</p>';
        }
    } else {
        echo '<p>ไม่สามารถดึง Access Token ได้</p>';
    }
} else {
    echo '<p>ไม่พบ Authorization Code ในการตอบกลับ</p>';
}
