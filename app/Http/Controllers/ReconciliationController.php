<?php

namespace App\Http\Controllers;

use App\Services\ReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Post(
 *     path="/api/v1/reconcile",
 *     summary="Reconcile two CSV or Excel files",
 *     description="Uploads two files, compares them based on detected name and amount columns, and returns matched/different records. You can choose to reconcile using AI or manually. Defaults to AI reconciliation if no option is provided.",
 *     tags={"Reconciliation"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"file1", "file2"},
 *                 @OA\Property(property="file1", type="string", format="binary", description="First CSV or Excel file"),
 *                 @OA\Property(property="file2", type="string", format="binary", description="Second CSV or Excel file"),
 *                 @OA\Property(
 *                     property="reconcile_option", 
 *                     type="string", 
 *                     enum={"reconcile_with_recox_ai", "reconcile_with_openAI","reconcile_with_deepSeek","reconcile_with_Gemini"},
 *                     description="Reconciliation method. Defaults to AI if not provided."
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Reconciliation successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Reconciliation successful"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="matches", type="integer", example=5, description="Number of matched rows"),
 *                 @OA\Property(property="only_in_file1", type="array", @OA\Items(type="object"), description="Rows only in first file"),
 *                 @OA\Property(property="only_in_file2", type="array", @OA\Items(type="object"), description="Rows only in second file")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=400, description="Validation or processing error"),
 *     @OA\Response(response=422, description="Invalid file format")
 * )
 */

class ReconciliationController extends Controller
{
    protected $reconciliationService;

    public function __construct(ReconciliationService $reconciliationService)
    {
        $this->reconciliationService = $reconciliationService;
    }

    public function reconcile(Request $request): JsonResponse
    {
        $request->validate([
            'file1' => 'required|file|mimes:csv,xlsx,xls',
            'file2' => 'required|file|mimes:csv,xlsx,xls',
            'reconcile_option' => 'nullable|in:reconcile_with_recox_ai,reconcile_with_openAI,reconcile_with_deepSeek,reconcile_with_Gemini',
        ], [
            'file1.mimes' => 'File 1 must be a CSV or Excel file.',
            'file2.mimes' => 'File 2 must be a CSV or Excel file.',
            'reconcile_option' => 'nullable|in:reconcile_with_recox_ai,reconcile_with_openAI,reconcile_with_deepSeek,reconcile_with_Gemini',
        ]);

        try {
            $file1Path = $request->file('file1')->store('uploads');
            $file2Path = $request->file('file2')->store('uploads');

            $file1FullPath = Storage::path($file1Path);
            $file2FullPath = Storage::path($file2Path);

            if (!$this->isValidFileFormat($file1FullPath) || !$this->isValidFileFormat($file2FullPath)) {
                Storage::delete([$file1Path, $file2Path]);
                return response()->json(['error' => 'One or both files are not in the correct format.'], 422);
            }

            $reconcileOption = $request->input('reconcile_option', 'reconcile_with_recox_ai');

            switch ($reconcileOption) {
                case 'reconcile_with_recox_ai':
                    $result = $this->reconciliationService->reconcileWithRecox($file1FullPath, $file2FullPath);
                    break;
                case 'reconcile_with_openAI':
                    $result = $this->reconciliationService->reconcileWithOpenAI($file1FullPath, $file2FullPath);
                    break;
                case 'reconcile_with_deepSeek':
                    $result = $this->reconciliationService->reconcileWithDeepSeek($file1FullPath, $file2FullPath);
                    break;
                case 'reconcile_with_Gemini':
                    $result = $this->reconciliationService->reconcileWithGemini($file1FullPath, $file2FullPath);
                    break;
                case 'reconcile_manually':
                default:
                    $result = $this->reconciliationService->reconcileWithRecox($file1FullPath, $file2FullPath);
                    break;
            }

            Storage::delete([$file1Path, $file2Path]);

            return response()->json([
                "message" => "Reconciliation successful",
                "status" => "success",
                "status_code" => 200,
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    private function isValidFileFormat(string $filePath): bool
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), ['csv', 'xls', 'xlsx']);
    }
}
