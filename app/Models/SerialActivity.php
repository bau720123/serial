<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerialActivity extends Model
{
    // 指定連線名稱
    protected $connection = 'sqlsrv_serial';

    // 指定資料表名稱
    protected $table = 'serial_activity';
}
