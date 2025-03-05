<?php

namespace App\Http\Controllers;

use App\Services\ReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Post(
 *     path="/api/v1/reconcile",
 *     summary="Reconcile two CSV files",
 *     description="Uploads two CSV files, compares them based on a key column, and returns matched/different records.",
 *     tags={"Reconciliation"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"file1", "file2", "key_column"},
 *                 @OA\Property(property="file1", type="string", format="binary", description="First CSV file"),
 *                 @OA\Property(property="file2", type="string", format="binary", description="Second CSV file"),
 *                 @OA\Property(property="key_column", type="string", description="The column used for reconciliation")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Reconciliation successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="matches", type="integer", description="Number of matched rows"),
 *             @OA\Property(property="differences", type="array", @OA\Items(type="object"), description="Differences between files"),
 *             @OA\Property(property="only_in_file1", type="array", @OA\Items(type="object"), description="Rows only in first file"),
 *             @OA\Property(property="only_in_file2", type="array", @OA\Items(type="object"), description="Rows only in second file")
 *         )
 *     ),
 *     @OA\Response(response=400, description="Validation or processing error")
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
            'key_column' => 'required|string',
        ], [
            'file1.mimes' => 'File 1 must be a CSV or Excel file.',
            'file2.mimes' => 'File 2 must be a CSV or Excel file.',
        ]);

        try {
            $file1Path = $request->file('file1')->store('uploads');
            $file2Path = $request->file('file2')->store('uploads');
            $keyColumn = $request->input('key_column');

            $file1FullPath = Storage::path($file1Path);
            $file2FullPath = Storage::path($file2Path);

            if (!$this->isValidFileFormat($file1FullPath) || !$this->isValidFileFormat($file2FullPath)) {
                Storage::delete([$file1Path, $file2Path]);
                return response()->json(['error' => 'One or both files are not in the correct format.'], 422);
            }

            $result = $this->reconciliationService->reconcileFiles($file1FullPath, $file2FullPath, $keyColumn);

            Storage::delete([$file1Path, $file2Path]);

            return response()->json(['message' => 'Reconciliation successful', 'data' => $result], 200);
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
