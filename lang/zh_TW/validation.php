<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'required' => ':attribute 欄位為必填。',
    'string'   => ':attribute 必須是字串。',
    'integer'  => ':attribute 必須是整數。',
    'array'    => ':attribute 格式必須是陣列。',
    'min'      => [
        'numeric' => ':attribute 不能小於 :min。',
        'array'   => ':attribute 至少要有 :min 個項目。',
    ],
    'max'      => [
        'numeric' => ':attribute 不能大於 :max。',
        'array'   => ':attribute 一次最多只能處理 :max 筆。',
        'string'  => ':attribute 不能超過 :max 個字元。',
    ],
    'date_format'    => ':attribute 格式必須符合 :format。',
    'after_or_equal' => ':attribute 必須晚於或等於 :date。',
    'unique' => ':attribute 已存在，請勿重複新增。',
    'exists' => '所選擇的 :attribute 無效（該活動不存在）。',
    'size' => [
        'string' => ':attribute :value 必須是 :size 個字元。',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    | 在這裡定義欄位的中文名稱，這會自動替換訊息中的 :attribute
    */

    'attributes' => [
        'activity_name'      => '活動名稱',
        'activity_unique_id' => '活動唯一 ID',
        'start_date'         => '開始日期',
        'end_date'           => '結束日期',
        'quota'              => '產生數量',
        'note'               => '備註原因',
        'content'            => '序號內容',
        'content.*'          => '序號項目',
    ],

    // 針對特定欄位的特定規則做「客製化訊息」
    'custom' => [
        'end_date' => [
            'after' => '結束日期 不能早於當前時間，否則序號將立即過期。',
        ],
    ],
];
