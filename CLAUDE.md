# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 專案說明

**序號管理系統 API** — 基於 Laravel 12 (PHP 8.2+)，提供序號的批次新增、追加、核銷、註銷功能，並整合後台查詢管理介面。

## 常用指令

```bash
# 開發伺服器（同時啟動 PHP server、queue、log、Vite）
composer run dev

# 僅啟動 PHP 開發伺服器
php artisan serve

# 執行所有測試
composer run test
# 或
php artisan test

# 執行單一測試
php artisan test --filter TestName

# 程式碼格式化
./vendor/bin/pint

# 清除設定快取
php artisan config:clear
```

## 資料庫設定

所有核心資料表都使用 **`sqlsrv_serial`** 連線（SQL Server / MSSQL），在 `config/database.php` 定義，對應 `.env` 中的以下變數：

```
DB_HOST_SERIAL=
DB_PORT_SERIAL=1433
DB_DATABASE_SERIAL=
DB_USERNAME_SERIAL=
DB_PASSWORD_SERIAL=
```

預設 `DB_CONNECTION=sqlite` 僅供 Laravel 內建表（session、cache、jobs）使用，業務資料表不使用此連線。

## 資料庫結構

| 資料表 | 說明 |
|---|---|
| `serial_activity` | 活動主表，每個活動有唯一的 `activity_unique_id` |
| `serial_detail` | 序號明細表，`status`: 0=未核銷、1=已核銷、2=已註銷 |
| `serial_log` | API 請求/回應完整日誌（由 `api.logger` middleware 寫入） |

序號格式：1 碼大寫英文 + 7 碼數字（例：`B1172060`）

## 架構總覽

### 請求流程
```
HTTP Request
  → api.logger middleware（記錄請求至 serial_log）
  → SerialController（Validator 驗證，422 回傳給前端）
  → SerialService（業務邏輯，Exception 拋 400）
  → DB transaction（悲觀鎖 lockForUpdate 防止併發）
  → api.logger middleware（記錄回應至 serial_log）
```

### 路由

- **API**（`routes/api.php`）：`POST /api/serials_insert`、`/api/serials_additional_insert`、`/api/serials_redeem`、`/api/serials_cancel`，全部掛載 `api.logger` middleware
- **Web**（`routes/web.php`）：`GET /admin/serials`（後台列表）、`GET /admin/serials/export`（CSV 匯出）

### 關鍵設計

- **Middleware 註冊**：`api.logger` 在 `bootstrap/app.php` 透過 alias 綁定 `ApiLogger::class`
- **序號唯一性保證**：`SerialService::generateUniqueSerials()` 採用迴圈 + 批次查詢 DB 排除重複的方式，確保產出不與現有序號衝突
- **驗證錯誤訊息**：中文訊息定義在 `lang/zh_TW/validation.php`
- **批次追加序號**：Controller 在呼叫 Service 前會 `merge(['insert_serial_activity' => 0])` 作為區分批次新增與追加的標記
- **CSV 匯出**：使用 `StreamedResponse` + `chunk(1000)` 串流輸出，避免大量資料佔用記憶體
- **模型連線**：`SerialActivity` 與 `SerialDetail` 兩個 Model 都明確指定 `$connection = 'sqlsrv_serial'`

### 錯誤碼規範
- `201`：批次新增 / 批次追加成功
- `200`：核銷 / 註銷操作成功
- `422`：Validator 驗證失敗（參數格式錯誤）
- `400`：Service 層業務邏輯錯誤（序號已過期、已使用等）
- `500`：非預期系統例外

## 測試資源

`unit_test/` 目錄下有 Postman collection（`postman_serial_collection.json`）與 JMeter 壓力測試設定（`Summary Report.jmx`），可直接匯入使用。
