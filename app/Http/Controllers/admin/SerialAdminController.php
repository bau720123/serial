<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SerialActivity;
use App\Models\SerialDetail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SerialAdminController extends Controller
{
    public function common_data($request)
    {
        // 建立查詢 Builder
        $query = SerialDetail::with('activity')->orderBy('id', 'desc');

        // 關鍵字搜尋
        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword); // 前後去空白
            $query->whereHas('activity', function($q) use ($keyword) {
                $q->where('activity_name', 'like', "%{$keyword}%")
                ->orWhere('activity_unique_id', 'like', "%{$keyword}%");
            });
        }

        // 序號搜尋
        if ($request->filled('content')) {
            $query->where('content', trim($request->content));
        }

        // 核銷狀況
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 日期區間搜尋邏輯
        if ($request->filled('date_start') || $request->filled('date_end')) {
            $query->where(function($q) use ($request) {
                // 如果有開始日期：撈取開始日期大於等於指定日期的所有資料
                if ($request->filled('date_start')) {
                    $q->where('start_date', '>=', $request->date_start . ' 00:00:00');

                }
                // 如果有結束日期：撈取結束日期小於等於指定日期的所有資料
                if ($request->filled('date_end')) {
                    $q->where('end_date', '<=', $request->date_end . ' 23:59:59');
                }
            });
        }

        return $query;
    }

    // 後台序號管理列表
    public function index(Request $request)
    {
        $query = $this->common_data($request); // 呼叫共用邏輯

        $list = $query->paginate(15)->withQueryString(); // 設定分頁

         return view('admin.serials.index', compact('list'));
    }

    // 後台序號管理匯出
    public function export(Request $request)
    {
        $query = $this->common_data($request); // 呼叫共用邏輯

        // 設定檔名與 Header
        $fileName = 'serial_export_' . date('YmdHis') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // 使用串流方式回傳，避免記憶體耗盡
        return new StreamedResponse(function() use ($query) {
            $handle = fopen('php://output', 'w');

            // 寫入 UTF-8 BOM，防止 Excel 開啟亂碼
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // 寫入標題列
            fputcsv($handle, ['活動名稱', '活動唯一ID', '序號', '核銷狀況', '更新時間', '開始時間', '結束時間', '備註說明', '新增時間']);

            // 批次處理資料 (每次處理 1000 筆，效能最優)
            $query->chunk(1000, function ($serials) use ($handle) {
                foreach ($serials as $row) {
                    $statusText = match ($row->status) {
                        0 => '未核銷',
                        1 => '已核銷',
                        2 => '已註銷',
                        default => '未設定',
                    };
                    fputcsv($handle, [
                        $row->activity->activity_name ?? 'N/A',
                        $row->activity->activity_unique_id ?? '-',
                        $row->content,
                        $statusText,
                        $row->updated_at ?? '--',
                        $row->start_date,
                        $row->end_date,
                        $row->note ?? '',
                        $row->created_at,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
