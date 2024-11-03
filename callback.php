<?php

// ตั้งค่า Supabase URL และ API Key ของคุณ
$supabaseUrl = 'https://jlhrexozxghiqobhcmaf.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImpsaHJleG96eGdoaXFvYmhjbWFmIiwicm9sZSI6ImFub24iLCJpYXQiOjE3MzA1NzM0MDQsImV4cCI6MjA0NjE0OTQwNH0.jWGCii4TGTw8QGMAYkf2IvDkjqGxwjis5XgGOMPc-AI';

// ตั้งค่า Channel ID, Channel Secret, และ Redirect URI
$clientId = '2006525783';
$clientSecret = 'cac877c5e22be00fb6b34178a93a4f5d';
$redirectUri = 'https://www.owtsoft.com/callback.php';

$sessionToken = bin2hex(openssl_random_pseudo_bytes(16)); // สร้าง token แบบสุ่ม
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
                'picture_url' => $profileData['pictureUrl'] ?? null,
                'session_token' => $sessionToken // เพิ่ม session token
            ];

            $redirectUri = "changrutchayothin://changrutchayothin.com/createLineAccount?sessionToken=" . urlencode($sessionToken);

            // ตรวจสอบว่าผู้ใช้มีอยู่แล้วใน Supabase หรือไม่
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $supabaseUrl . '/rest/v1/users?user_id=eq.' . $profileData['userId']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'apikey: ' . $supabaseKey,
                'Authorization: Bearer ' . $supabaseKey
            ]);

            $existingUserResponse = curl_exec($ch);
            curl_close($ch);

            $existingUser = json_decode($existingUserResponse, true);

            if (!empty($existingUser)) {
                // ถ้ามีผู้ใช้อยู่แล้ว ให้ทำการอัปเดตข้อมูลแทน
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $supabaseUrl . '/rest/v1/users?user_id=eq.' . $profileData['userId']);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'apikey: ' . $supabaseKey,
                    'Authorization: Bearer ' . $supabaseKey
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));

                $supabaseResponse = curl_exec($ch);
                curl_close($ch);

                echo 'ข้อมูลผู้ใช้ถูกอัปเดตใน Supabase เรียบร้อยแล้ว';
                echo '<p>การเข้าสู่ระบบเสร็จสมบูรณ์แล้ว</p>';
                echo '<a href="changrutchayothin://changrutchayothin.com/createLineAccount">คลิกที่นี่เพื่อกลับไปยังแอป</a>';

                header("Location: $redirectUri");
                exit();
            } else {
                // ถ้าไม่มีผู้ใช้ ให้ทำการ Insert ข้อมูลใหม่
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $supabaseUrl . '/rest/v1/users');
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
                    echo 'ข้อมูลผู้ใช้ถูกบันทึกใน Supabase เรียบร้อยแล้ว';
                    echo '<p>การเข้าสู่ระบบเสร็จสมบูรณ์แล้ว</p>';
                    echo '<a href="changrutchayothin://changrutchayothin.com/createLineAccount">คลิกที่นี่เพื่อกลับไปยังแอป</a>';
                    header("Location: $redirectUri");
                    exit();
                } else {
                    echo 'ไม่สามารถบันทึกข้อมูลใน Supabase ได้: ' . htmlspecialchars(print_r($result, true));
                }
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
