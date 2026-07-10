<?php
/* .envファイル（同一階層）を読み込んで環境変数として設定する簡易ローダー
function loadEnv(string $path): void{
    if(!file_exists($path)){
        exit('.envファイルが見つかりません');
    }

    foreach(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line){
        // #から始まる行はコメントとして無視
        if(str_starts_with((trim($line)), '#')){
            continue;
        }

        // =がない行（最後の空改行）は無視
        if(!str_contains($line, '=')){
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

loadEnv(__DIR__ . '/.env');

$dsn = sprintf(
    //'mysql:dbname=%s;host=%s;port=%s;charset=utf8mb4', Local用
    'mysql:dbname=%s;host=%s;charset=utf8mb4',
    $_ENV['DB_NAME'],
    $_ENV['DB_HOST'],
    //$_ENV['DB_PORT'] Local用
);
*/

// .envが別階層にある場合
$dsn = sprintf(
    'mysql:dbname=%s;host=%s;port=%s;charset=utf8mb4',
    getenv('DB_NAME') ?: 'local',
    getenv('DB_HOST') ?: '127.0.0.1',
    getenv('DB_PORT') ?: '3306'
);

$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: 'root';

// v1.7.1変更：Javaの不適切投稿チェックAPIを呼び出す
function isMessageAllowed(string $message): array{
    $ch = curl_init('http://java-api:8081/api/check-message'); //DockerCompose内でコンテナ同士が通信するときサービス名がそのままホスト名。
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $message]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); //Javaが応答しなかったら3秒で締める

    $response = curl_exec($ch);
    curl_close($ch);

    // Java側が応答しない場合は投稿を許可する（障害があったときに投稿全体を止めないため）
    if($response === false){
        return [
            'isValid' => true,
            'reason' => null
        ];
    }
    return json_decode($response, true);
}
