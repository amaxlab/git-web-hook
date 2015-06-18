<?php
/**
 * Created by PhpStorm.
 * User: zyuskin_en
 * Date: 03.01.15
 * Time: 3:21
 */

$url = "http://localhost/hook/examples/hook.php";
$dataJson = '{"ref":"refs/heads/master","repository":{"name":"git-web-hook","url":"git@github.com:amaxlab/git-web-hook.git"},"commits":[{"id":"06f9ce8478e0973ec17b6253000a1f1f140c322b","message":"test commit1","timestamp":"2014-12-25T15:20:16+06:00","author":{"name":"Egor Zyuskin","email":"egor@zyuskin.ru"}},{"id":"bbc699620acc8157ae9aacde3fe2f3660ac16441","message":"test commit2","timestamp":"2014-12-25T15:20:17+06:00","author":{"name":"Egor Zyuskin","email":"egor@zyuskin.ru"}}]}';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($dataJson)));
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response  = curl_exec($ch);
curl_close($ch);

print $response."\n";