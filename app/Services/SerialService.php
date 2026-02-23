<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;
// use App\Models\SerialDetail;

class SerialService
{
    protected $connection = 'sqlsrv_serial';

    // 批次新增序號與批次追加序號
    public function createActivityWithSerials(array $data)
    {
        return DB::connection($this->connection)->transaction(function () use ($data) {

            // 當前時間
            $currentTime = now();

            if (!isset($data['insert_serial_activity'])) {
                // 批次新增序號，需建立活動
                $activityId = DB::connection($this->connection)->table('serial_activity')->insertGetId([
                    'activity_name'      => $data['activity_name'],
                    'activity_unique_id' => $data['activity_unique_id'],
                    'start_date'         => $data['start_date'],
                    'end_date'           => $data['end_date'],
                    'quota'              => $data['quota'],
                    'created_at'         => $currentTime,
                    'updated_at'         => $currentTime,
                ]);
            } else {
                // 批次追加序號，則需要先查詢活動 ID
                $activity = DB::connection($this->connection)
                    ->table('serial_activity')
                    ->where('activity_unique_id', $data['activity_unique_id'])
                    ->first();
                $activityId = $activity->id;
            }


            // 呼叫「保證唯一，不重覆」的序號產生邏輯
            $uniqueSerials = $this->generateUniqueSerials($data['quota']);

            // 若是追加序號，需寫入備註欄位
            $isAdditional = isset($data['insert_serial_activity']) && $data['insert_serial_activity'] == 0;
            $noteContent  = $isAdditional ? ($data['note'] ?? '追加序號') : null;

            $insertData = [];
            foreach ($uniqueSerials as $code) {
                $insertData[] = [
                    'serial_activity_id' => $activityId,
                    'content'            => $code,
                    'status'             => 0,
                    'note'               => $noteContent,
                    'start_date'         => $data['start_date'],
                    'end_date'           => $data['end_date'],
                    'created_at'         => $currentTime,
                    'updated_at'         => null,
                ];
            }

            // 批次寫入序號資料
            DB::connection($this->connection)->table('serial_detail')->insert($insertData);
            // SerialDetail::insert($insertData);

            return [
                'activity_id' => $activityId,
                'total_generated' => count($insertData)
            ];
        });
    }

    /**
     * 保證產生出指定數量且不與資料庫重複的序號
     */
    private function generateUniqueSerials(int $quota)
    {
        $finalSerials = [];

        while (count($finalSerials) < $quota) {
            $needed = $quota - count($finalSerials);
            $tempBatch = [];

            // 先產生一組候選序號
            for ($i = 0; $i < $needed; $i++) {
                $tempBatch[] = $this->generateRandomString();
            }

            // 移除這批裡面自己重複的部分
            $tempBatch = array_unique($tempBatch);

            //【關鍵】一次性查詢資料庫，檢查這批序號哪些已經被用過了
            $existing = DB::connection($this->connection)
                ->table('serial_detail')
                ->whereIn('content', $tempBatch)
                ->pluck('content')
                ->toArray();

            // 排除掉資料庫已存在的序號
            $available = array_diff($tempBatch, $existing);

            // 合併進最終結果
            foreach ($available as $validCode) {
                if (count($finalSerials) < $quota) {
                    $finalSerials[] = $validCode;
                }
            }

            // 如果這輪沒填滿，while 會繼續跑，直到產滿為止
        }

        return $finalSerials;
    }

    private function generateRandomString()
    {
        $letter = chr(rand(65, 90)); // A-Z
        $numbers = str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT); // 產生 1 到 9999999 之間的隨機整數，並在左側補 0 直到補滿 7 位數
        return $letter . $numbers;
    }

    // 核銷序號
    public function redeemSerial(string $content)
    {
        // 去前後空白 + 轉大寫
        $content = strtoupper(trim($content));

        return DB::connection($this->connection)->transaction(function () use ($content) {
            $now = now();

            // 查找序號並鎖定該行 (lockForUpdate)
            $serial = DB::connection($this->connection)
                ->table('serial_detail')
                ->where('content', $content)
                ->lockForUpdate()
                ->first();

            // 條件檢查

            if (!$serial) {
                throw new Exception("序號不存在");
            }

            if ($serial->status == 1) {
                throw new Exception("此序號已被核銷，請勿重複核銷");
            }

            if ($serial->status == 2) {
                throw new Exception("此序號已被註銷，無法進行核銷");
            }

            if ($now < $serial->start_date) {
                throw new Exception("此序號活動尚未開始 (開放時間：" . $serial->start_date . ")");
            }

            if ($now > $serial->end_date) {
                throw new Exception("此序號已過期失效 (到期時間：" . $serial->end_date . ")");
            }

            // 真正執行核銷
            DB::connection($this->connection)
                ->table('serial_detail')
                ->where('id', $serial->id)
                ->update([
                    'status'     => 1,
                    'updated_at' => $now, // 此時 updated_at 紀錄的是真正的核銷時點
                ]);

            return [
                'serial_content' => $serial->content,
                'redeemed_at'    => $now->toDateTimeString(),
            ];
        });
    }

    // 註銷序號
    public function cancelSerials(array $contents, string $note, string $now)
    {
        $results = [
            'success' => [],
            'fail'    => []
        ];

        // 先處理資料清洗
        $contents = array_unique(array_map(fn($item) => strtoupper(trim($item)), $contents));

        // 批次處理 (使用 Transaction 確保資料安全)
        foreach ($contents as $content) {
            try {
                DB::connection($this->connection)->transaction(function () use ($content, $note, $now, &$results) {
                    // 鎖定單行檢查
                    $serial = DB::connection($this->connection)
                        ->table('serial_detail')
                        ->where('content', $content)
                        ->lockForUpdate()
                        ->first();

                    // 條件檢查邏輯
                    if (!$serial) {
                        throw new Exception("序號不存在");
                    }
                    if ($serial->status == 1) {
                        throw new Exception("此序號已被核銷，無法進行註銷");
                    }
                    if ($serial->status == 2) {
                        throw new Exception("此序號已被註銷，請勿重複註銷");
                    }

                    // 執行註銷 (status = 2)
                    DB::connection($this->connection)
                        ->table('serial_detail')
                        ->where('id', $serial->id)
                        ->update([
                            'status'     => 2,
                            'note'       => $note, // 更新註銷原因
                            'updated_at' => $now,
                        ]);

                    $results['success'][] = $content;
                });
            } catch (Exception $e) {
                // 紀錄失敗原因
                $results['fail'][] = "{$content} ({$e->getMessage()})";
            }
        }

        return $results;
    }
}
