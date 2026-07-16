# 簡易版X（Twitterクローン）
PHP・Javaの学習を目的として制作した、投稿・削除・画像アップロード機能を持つ簡易掲示板アプリです。

## デモ
NGワード投稿時の動作：https://youtu.be/WyG1vZSBWkw

## 使用技術
| 分類 | 技術 |
|---|---|
| バックエンド | PHP 8.2, SpringBoot 3.5 (Java 25) |
| データベース | MySQL 8.0 |
| インフラ | Docker, Docker Compose |
| フロントエンド | HTML, CSS, JavaScript |
| その他 | PWA（Service Worker, Web App Manifest） |

## 機能
- 投稿・削除（画像添付可）
- CSRF対策
- 不適切な投稿の自動チェック（Java製APIと連携）
- PWA対応（ホーム画面への追加、オフラインキャッシュ）

## アーキテクチャ
\`\`\`
[ブラウザ]
    ↓
[PHP (Apache)] ← → [MySQL]
    ↓ HTTP通信
[Java API (SpringBoot)] ← NGワード判定
\`\`\`

3つのコンテナをDocker Composeで連携させています。

## セットアップ
\`\`\`bash
git clone https://github.com/sara-tori-work/mini-x-.git
cd mini-x-
cp .env.example .env  # 必要に応じて値を編集
docker compose up --build
\`\`\`

起動後、以下にアクセスしてください。

- 掲示板: http://localhost:8080
- Java API: http://localhost:8081

## こだわった点
- PHPとJavaという異なる言語のサービスを、Docker Composeで1つのシステムとして連携させた
- 投稿の可否判定をJavaの独立したAPIに切り出すことで、役割分担を明確にした
- PWA化により、スマホのホーム画面からアプリのように起動できるようにした

## 開発で工夫・苦労した点
- Service Workerがキャッシュを保持する影響で、開発中の変更が反映されにくい問題に対応
- Docker環境でのコンテナ間通信（`localhost`ではなくサービス名で名前解決する必要がある点）を理解して構成
- PowerShell経由でのcurlコマンドの挙動差異など、Windows特有のハマりどころに対応
