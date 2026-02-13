<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>序號管理系統 - 後台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: "Microsoft JhengHei", sans-serif; }
        .table-container { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .search-box { background: #ffffff; border-radius: 10px; padding: 20px; margin-bottom: 20px; border-left: 5px solid #0d6efd; }
        .status-badge { width: 70px; display: inline-block; text-align: center; }
    </style>
    <style>
    /* 針對手機版 (小於 576px) 的特殊優化 */
    @media (max-width: 575.98px) {
        /* 日期區間從並排改為上下堆疊，增加間距 */
        .search-date-group {
            flex-direction: column;
            gap: 10px !important;
        }

        /* 讓按鈕在手機上全部等寬 100% */
        .btn-mobile-full {
            width: 100%;
            margin-bottom: 10px;
        }

        /* 讓清除重置跟搜尋按鈕並排 */
        .mobile-btn-group {
            display: flex;
            flex-direction: column;
        }
    }
    </style>
</head>

<body>

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">管理後台 | 序號監控中心</span>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="search-box shadow-sm">
        <form action="/admin/serials" method="GET" id="searchForm" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">關鍵字搜尋</label>
                <input type="text" name="keyword" class="form-control" placeholder="請輸入 活動名稱 或是 活動唯一 ID" value="{{ request('keyword') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">序號</label>
                <input type="text" name="content" class="form-control" placeholder="請輸入序號" value="{{ request('content') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">狀態</label>
                <select name="status" class="form-select">
                    <option value="">請選取</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>未核銷</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>已核銷</option>
                    <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>已註銷</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">搜尋區間</label>
                <div class="d-flex gap-2 search-date-group">
                    <input type="date" name="date_start" id="date_start" class="form-control" value="{{ request('date_start') }}">
                    <input type="date" name="date_end" id="date_end" class="form-control" value="{{ request('date_end') }}">
                </div>
            </div>
            <div class="col-12 text-end">
                <hr>
                <div class="mobile-btn-group">
                    <a href="/admin/serials" class="btn btn-light border btn-mobile-full">清除重置</a>
                    <button type="submit" class="btn btn-primary px-5 btn-mobile-full">立即搜尋</button>
                    <button type="button" id="exportBtn" class="btn btn-success px-4 btn-mobile-full">匯出 CSV</button>
                </div>
            </div>
        </form>
    </div>

    <div class="table-container shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-bordered text-center">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%;">活動名稱 / ID</th>
                        <th style="width: 10%;">序號</th>
                        <th style="width: 10%;">狀態</th>
                        <th style="width: 15%;">更新時間</th>
                        <th style="width: 15%;">有效期限 (起~迄)</th>
                        <th style="width: 20%;">備註說明</th>
                        <th style="width: 15%;">新增時間</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($list as $item)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $item->activity->activity_name ?? 'N/A' }}</div>
                            <small class="text-muted">{{ $item->activity->activity_unique_id ?? '-' }}</small>
                        </td>
                        <td><code class="fs-5">{{ $item->content }}</code></td>
                        <td class="text-center">
                            @if($item->status == 0)
                                <span class="badge bg-success status-badge">未核銷</span>
                            @elseif($item->status == 1)
                                <span class="badge bg-success status-badge">已核銷</span>
                            @elseif($item->status == 2)
                                <span class="badge bg-dark status-badge">已註銷</span>
                            @else
                                <span class="badge bg-warning text-dark status-badge">未設定</span>
                            @endif
                        </td>
                        <td>{{ $item->updated_at ?? '--' }}</td>
                        <td>
                            <small>{{ $item->start_date }}</small><br>
                            <small>{{ $item->end_date }}</small>
                        </td>
                        <td><small class="text-muted">{{ $item->note ?? '-' }}</small></td>
                        <td><small>{{ $item->created_at }}</small></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">目前沒有符合條件的資料</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $list->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    /**
     * 過濾掉沒有值的欄位參數，回傳乾淨的參數物件
     */
    function getCleanParamsArray($form) {
        let params = {};
        $form.find('input[name], select[name]').each(function() {
            const val = $(this).val().trim();
            if (val !== "") {
                params[$(this).attr('name')] = val;
            }
        });
        return params;
    }

    // 搜尋按鈕
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const start = $('#date_start').val(); // 開始日期
        const end = $('#date_end').val(); // 結束日期

        // 日期防呆
        if (start && end && start > end) {
            Swal.fire({
                icon: 'error',
                title: '日期設定錯誤',
                text: '結束日期不能小於開始日期',
                confirmButtonText: '確定',
                confirmButtonColor: '#0d6efd'
            });
            return false;
        }

        // 手動組裝乾淨 URL
        const paramsObj = getCleanParamsArray($(this));
        const queryString = $.param(paramsObj); // jQuery 內建將物件轉為 URL 參數字串
        const baseUrl = $(this).attr('action'); // searchForm 的 action 屬性值

        // before：serials?keyword=&content=C1689170&status=&date_start=&date_end=
        // after：serials?content=C1689170

        window.location.href = baseUrl + (queryString ? '?' + queryString : '');
    });

    // 匯出按鈕
    $('#exportBtn').on('click', function() {
        const $form = $('#searchForm');

        // 匯出前同樣檢查日期防呆
        const start = $('#date_start').val();
        const end = $('#date_end').val();
        if (start && end && start > end) {
            // 直接觸發 form 的 submit 來顯示 Swal 錯誤，就不用再寫一次邏輯了
            $form.submit();
            return false;
        }

        // 手動組裝乾淨 URL
        const paramsObj = getCleanParamsArray($form);
        const queryString = $.param(paramsObj); // jQuery 內建將物件轉為 URL 參數字串
        const baseUrl = $form.attr('action'); // searchForm 的 action 屬性值

        // before：export?keyword=&content=C1689170&status=&date_start=&date_end=
        // after：export?content=C1689170

        window.location.href = baseUrl + "/export?" + queryString;
    });
});
</script>

</body>
</html>
