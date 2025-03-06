<?php

namespace App\Http\Controllers;

use App\Services\ReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Client;

/**
 * @OA\Post(
 *     path="/api/v1/reconcile",
 *     summary="Reconcile two CSV files",
 *     description="Uploads two CSV files and returns reconciled records.",
 *     tags={"Reconciliation"},
 *     operationId="47cda2733290d3ed5e6ef36ed538bbd2",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"bank_statement", "company_ledger"},
 *                 @OA\Property(
 *                     property="bank_statement",
 *                     type="string",
 *                     format="binary",
 *                     description="First CSV file"
 *                 ),
 *                 @OA\Property(
 *                     property="company_ledger",
 *                     type="string",
 *                     format="binary",
 *                     description="Second CSV file"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Reconciliation successful",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", description="Response message"),
 *             @OA\Property(property="status", type="string", description="Response status"),
 *             @OA\Property(property="status_code", type="integer", description="Response status code"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(
 *                     property="reconciliationSummary",
 *                     type="object",
 *                     description="Summary of reconciliation results",
 *                     @OA\Property(property="totalMatchedTransactions", type="integer", description="Number of matched transactions"),
 *                     @OA\Property(property="totalUnmatchedTransactions", type="integer", description="Number of unmatched transactions"),
 *                     @OA\Property(property="accuracyRate", type="number", format="float", description="Percentage of accuracy")
 *                 ),
 *                 @OA\Property(
 *                     property="matchedTransactions",
 *                     type="array",
 *                     description="List of matched transactions",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(
 *                             property="bank_statement",
 *                             type="object",
 *                             description="Transaction data from bank statement",
 *                             example={"Date": "2025-01-01", "Description": "Payment", "Amount": 1000}
 *                         ),
 *                         @OA\Property(
 *                             property="company_ledger",
 *                             type="object",
 *                             description="Transaction data from company ledger",
 *                             example={"Date": "2025-01-01", "Description": "Payment", "Amount": 1000}
 *                         ),
 *                         @OA\Property(property="status", type="string", description="Match status", example="matched")
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation or processing error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", description="Error message"),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 description="Detailed validation errors",
 *                 example={"bank_statement": {"The bank statement file is required."}, "company_ledger": {"The company ledger file is required."}}
 *             )
 *         )
 *     )
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
        try {
            $request->validate([
                'financial_statement' => 'required|file|mimes:csv',
                'company_ledger' => 'required|file|mimes:csv',
            ], [
                'financial_statement.mimes' => 'Financial statement must be a CSV file',
                'company_ledger.mimes' => 'Company Ledger must be a CSV file.',
            ]);

            try {
                $file1Path = $request->file('financial_statement')->store('uploads');
                $file2Path = $request->file('company_ledger')->store('uploads');

                $file1FullPath = Storage::path($file1Path);
                $file2FullPath = Storage::path($file2Path);

                if (!$this->isValidFileFormat($file1FullPath) || !$this->isValidFileFormat($file2FullPath)) {
                    Storage::delete([$file1Path, $file2Path]);
                    return response()->json(['error' => 'One or both files are not in the correct format.'], 422);
                }

                $result = $this->reconciliationService->reconcileFiles($file1FullPath, $file2FullPath);

                Storage::delete([$file1Path, $file2Path]);

                return response()->json(
                    [
                        'message' => 'Reconciliation successful',
                        'status' => 'success',
                        'status_code' => 200,
                        'data' => $result
                    ], 200);

            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'status_code' => 500,
                    'message' => 'Internal Server Error',
                    'data' => $e->errors()
                ], 500);

            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'status_code' => 422,
                'message' => 'Validation errors',
                'data' => $e->errors()
            ], 422);
        }
    }

    private function isValidFileFormat(string $filePath): bool
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), ['csv', 'xls', 'xlsx']);
    }
}
