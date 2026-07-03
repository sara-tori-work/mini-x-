<?php
// .envファイルを読み込んで環境変数として設定する簡易ローダー
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
    'mysql:dbname=%s;host=%s;port=%s;charset=utf8mb4',
    $_ENV['DB_NAME'],
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT']
);

$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
