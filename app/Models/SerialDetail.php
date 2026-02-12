<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\SerialActivity;

class SerialDetail extends Model
{
    // 指定連線名稱
    protected $connection = 'sqlsrv_serial';

    // 指定資料表名稱
    protected $table = 'serial_detail';

    /**
     * 定義關聯：序號屬於哪一個活動
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(SerialActivity::class, 'serial_activity_id', 'id');
    }
}
