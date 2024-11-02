<?php

// ตั้งค่า Supabase URL และ API Key ของคุณ
$supabaseUrl = 'https://jlhrexozxghiqobhcmaf.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImpsaHJleG96eGdoaXFvYmhjbWFmIiwicm9sZSI6ImFub24iLCJpYXQiOjE3MzA1NzM0MDQsImV4cCI6MjA0NjE0OTQwNH0.jWGCii4TGTw8QGMAYkf2IvDkjqGxwjis5XgGOMPc-AI';

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

        if (isset($profileData['userId'])) {
            // เตรียมข้อมูลที่จะส่งไป Supabase
            $userData = [
                'user_id' => $profileData['userId'],
                'display_name' => $profileData['displayName'],
                'picture_url' => $profileData['pictureUrl'] ?? null
            ];

            // Step 3: ส่งข้อมูลไปยัง Supabase
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $supabaseUrl . '/rest/v1/users'); // ใช้ endpoint สำหรับตาราง users
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $supabaseKey,
                'Authorization: Bearer ' . $supabaseKey,
                'Prefer: return=representation'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));

            $supabaseResponse = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($supabaseResponse, true);
            if (isset($result[0])) {
                echo 'ข้อมูลถูกเก็บใน Supabase เรียบร้อยแล้ว';

                // หลังจากที่บันทึกข้อมูลลงใน Supabase เรียบร้อยแล้ว
                header("Location: changrutchayothln://HomePage");
                exit();
            } else {
                echo 'ไม่สามารถเก็บข้อมูลใน Supabase ได้: ' . htmlspecialchars(print_r($result, true));
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
