<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SerialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Models\SerialActivity;

class SerialController extends Controller
{
    protected $serialService;

    public function __construct(SerialService $serialService)
    {
        $this->serialService = $serialService;
    }

    // Validator 的錯誤訊息都會在：lang/zh_TW/validation.php

    // 批次新增序號
    public function serials_insert(Request $request)
    {
        // 防呆驗證
        $validator = Validator::make($request->all(), [
            'activity_name' => 'required|string',
            // 'activity_unique_id' => 'required|string|unique:sqlsrv_serial.serial_activity,activity_unique_id',
            // 'activity_unique_id' => 'required|string|unique:App\Models\SerialActivity,activity_unique_id',
            'activity_unique_id' => [
                'required',
                'string',
                "unique:" . SerialActivity::class . ",activity_unique_id",
            ],
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:start_date|after:now',
            'quota' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            // 格式正確但內容「不合法」
            return response()->json([
                'status'  => 'error',
                'message' => '驗證失敗',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 呼叫 Service 處理業務邏輯
            $result = $this->serialService->createActivityWithSerials($request->all());

            return response()->json([
                'status'  => 'success',
                'message' => '活動與序號已成功產生',
                'data'    => $result
            ], 201);

        } catch (Exception $e) {
            // 例外處理
            return response()->json([
                'status'  => 'error',
                'message' => '系統處理失敗',
                'debug'   => $e->getMessage()
            ], 500);
        }
    }

    // 批次追加序號
    public function serials_additional_insert(Request $request)
    {
        // 防呆驗證
        $validator = Validator::make($request->all(), [
            'activity_unique_id' => [
                'required',
                'string',
                "exists:" . SerialActivity::class . ",activity_unique_id",
            ],
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:start_date|after:now',
            'quota' => 'required|integer|min:1|max:100',
            'note' => 'required|string',
        ]);

        if ($validator->fails()) {
            // 格式正確但內容「不合法」
            return response()->json([
                'status'  => 'error',
                'message' => '驗證失敗',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 增加自定義參數，例如來源標記
            $request->merge([
                'insert_serial_activity' => 0, // 0 表示僅追加序號
            ]);

            // 呼叫 Service 處理業務邏輯
            $result = $this->serialService->createActivityWithSerials($request->all());

            return response()->json([
                'status'  => 'success',
                'message' => '序號已成功產生',
                'data'    => $result
            ], 201);

        } catch (Exception $e) {
            // 例外處理
            return response()->json([
                'status'  => 'error',
                'message' => '系統處理失敗',
                'debug'   => $e->getMessage()
            ], 500);
        }
    }

    // 核銷序號
    public function serials_redeem(Request $request)
    {
        // 基本格式驗證
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|size:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => '驗證失敗',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->serialService->redeemSerial($request->input('content'));

            return response()->json([
                'status'  => 'success',
                'message' => '核銷成功',
                'data'    => $result
            ], 200);

        } catch (Exception $e) {
            // 這裡會抓到 Service 拋出的業務邏輯錯誤 (如：已過期、已使用)
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 400); // 業務邏輯錯誤通常回傳 400
        }
    }

    // 註銷序號
    public function serials_cancel(Request $request)
    {
        // 使用 Validator 手動驗證，確保失敗時也能回傳 JSON 並被 middleware 紀錄
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'content' => 'required|array|min:1|max:1000',
            'content.*' => 'required|string|size:8',
            'note'      => 'required|string|max:255'
        ]);

        // 增加自定義 Replacer 來顯示出錯的內容
        $validator->addReplacer('size', function ($message, $attribute, $rule, $parameters, $validator) {
            // 抓取當前正在驗證的序號
            $value = \Illuminate\Support\Arr::get($validator->getData(), $attribute);

            // 先替換我們自訂的 :value
            $message = str_replace(':value', "[{$value}]", $message);

            // 補上有問題的 :size
            return str_replace(':size', $parameters[0], $message);
        });

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => '驗證失敗',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 如果驗證失敗，回傳 422
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => '驗證失敗',
                'errors'  => $validator->errors()
            ], 422);
        }

        $now = now()->toDateTimeString(); // 當下時間

        $results = $this->serialService->cancelSerials($request->content, $request->note, $now);

        // 判斷最終 Message
        $successCount = count($results['success']);
        $failCount = count($results['fail']);

        $message = "部分註銷成功";
        if ($failCount === 0) $message = "全部註銷成功";
        if ($successCount === 0) $message = "全部註銷失敗";

        return response()->json([
            'status'       => 'success',
            'message'      => $message,
            'cancel_at'    => $now,
            'success_data' => [
                'serial_content' => implode(',', $results['success'])
            ],
            'fail_data'    => [
                'serial_content' => implode(',', $results['fail'])
            ]
        ], 200);
    }
}
