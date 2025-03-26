<?php

namespace App\Http\Controllers;

use App\Services\ReconciliationService;
use App\Services\NewReconciliation\NewReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
<<<<<<< HEAD
use GuzzleHttp\Client;
use Illuminate\Validation\ValidationException;

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
=======
use App\Models\ReconciledRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\Reconciliation;
use App\Http\Requests\ManualReconciliationRequest;
use App\Jobs\ProcessReconciliation;
use App\Jobs\ProcessRecoxReconciliation;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Reconciliation",
 *     description="API Endpoints for file reconciliation"
>>>>>>> dev
 * )
 */

class ReconciliationController extends Controller
{
    protected $reconciliationService;
    protected $testService;

    public function __construct(ReconciliationService $reconciliationService, NewReconciliationService $testService)
    {
        $this->reconciliationService = $reconciliationService;
        $this->testService = $testService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reconcile",
     *     summary="Reconcile two CSV or Excel files",
     *     description="Upload and compare two files using various reconciliation methods",
     *     tags={"Reconciliation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file1", "file2"},
     *                 @OA\Property(
     *                     property="file1",
     *                     type="string",
     *                     format="binary",
     *                     description="First CSV or Excel file"
     *                 ),
     *                 @OA\Property(
     *                     property="file2",
     *                     type="string",
     *                     format="binary",
     *                     description="Second CSV or Excel file"
     *                 ),
     *                 @OA\Property(
     *                     property="reconcile_option",
     *                     type="string",
     *                     enum={
     *                         "reconcile_with_Gemini",
     *                         "reconcile_with_recox_ai",
     *                         "reconcile_with_openAI",
     *                         "reconcile_with_deepSeek"
     *                     },
     *                     description="Reconciliation method to use"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful reconciliation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciliation successful"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="matches", type="integer", example=5),
     *                 @OA\Property(property="only_in_file1", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="only_in_file2", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function reconcile(Request $request): JsonResponse
    {
<<<<<<< HEAD
        try {
            $request->validate([
                'financial_statement' => 'required|file|mimes:csv',
                'company_ledger' => 'required|file|mimes:csv',
            ], [
                'financial_statement.mimes' => 'Financial statement must be a CSV file',
                'company_ledger.mimes' => 'Company Ledger must be a CSV file.',
            ]);
=======
        $request->validate([
            'file1' => 'required|file|mimes:csv|max:2048',
            'file2' => 'required|file|mimes:csv|max:2048',
            'reconcile_option' => 'nullable|in:reconcile_with_recox_ai,reconcile_with_openAI,reconcile_with_deepSeek,reconcile_with_Gemini',
        ], [
            'file1.mimes' => 'File 1 must be a CSV.',
            'file2.mimes' => 'File 2 must be a CSV.',
            'file1.max' => 'File 1 must not be larger than 2MB.',
            'file2.max' => 'File 2 must not be larger than 2MB.',
        ]);

        try {
            $file1Path = $request->file('file1')->store('uploads');
            $file2Path = $request->file('file2')->store('uploads');
>>>>>>> dev

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

<<<<<<< HEAD
                return response()->json(
                    [
                        'message' => 'Reconciliation successful',
                        'status' => 'success',
                        'status_code' => 200,
                        'data' => $result
                    ], 200);

            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'status_code' => 500,
                    'message' => 'Internal Server Error',
                    'data' => $e->getMessage()
                ], 500);
        }
        }catch(ValidationException $e){
            return response()->json([
                'status' => 'error',
                'status_code' => 422,
                'message' => 'Validation errors',
                'data' => $e->errors()
            ], 422);
=======
            $reconcileOption = $request->input('reconcile_option', 'reconcile_with_Gemini');

            $result = match ($reconcileOption) {
                'reconcile_with_recox_ai' => $this->reconciliationService->reconcileWithRecox($file1FullPath, $file2FullPath),
                'reconcile_with_openAI' => $this->reconciliationService->reconcileWithOpenAI($file1FullPath, $file2FullPath),
                'reconcile_with_deepSeek' => $this->reconciliationService->reconcileWithDeepSeek($file1FullPath, $file2FullPath),
                default => $this->reconciliationService->reconcileWithGemini($file1FullPath, $file2FullPath),
            };

            $reconciliation = $this->reconciliationService->store([
                'user' => Auth::id(),
                'statement' => $file1FullPath,
                'ledger' => $file2FullPath,
                'ai' => $reconcileOption,
                'response' => $result
            ]);

            return response()->json([
                "message" => "Reconciliation successful",
                "status" => "success",
                "status_code" => 200,
                'data' => [
                    'reconciliation_id' => $reconciliation->id,
                    ...$result
                ]
            ], 200);
        } catch (\Exception $e) {
            if (isset($file1Path, $file2Path)) {
                Storage::delete([$file1Path, $file2Path]);
            }
            return response()->json(['error' => $e->getMessage()], 400);
>>>>>>> dev
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reconciliations/{reconciliation}/export",
     *     summary="Export reconciled data as a CSV file",
     *     description="Generates a CSV file containing matched and unmatched transactions from reconciliation.",
     *     tags={"Reconciliation"},
     *     @OA\Parameter(
     *         name="reconciliation",
     *         in="path",
     *         required=true,
     *         description="Reconciliation ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CSV file generated successfully",
     *         @OA\Header(header="Content-Disposition", description="attachment; filename=reconciled-data.csv", @OA\Schema(type="string"))
     *     ),
     *     @OA\Response(response=500, description="Server error while generating CSV file")
     * )
     */
    public function export(Reconciliation $reconciliation){
        try {
            return $this->testService->export($reconciliation);
        } catch(\Exception $e) {
            return response()->json([
                "message" => "Failed to generate report",
                "status" => "error",
                "status_code" => 500,
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reconciliations/{reconciliation}",
     *     summary="Get reconciled records",
     *     description="Fetch reconciled records for the logged-in user",
     *     tags={"Reconciliation"},
     *     @OA\Parameter(
     *         name="reconciliation",
     *         in="path",
     *         required=true,
     *         description="Reconciliation ID",
     *         @OA\Schema(type="string")
     *     ),
     *     security={{ "bearerAuth":{} }},
     *     @OA\Response(
     *         response=200,
     *         description="Reconciled records fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciled records fetched successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function getReconciledRecords(Request $request, Reconciliation $reconciliation)
    {
        $user = $request->user();

        if($reconciliation->user->id != $user->id){
            return response()->json([
                'message' => 'Failed to authenticate',
                'status' => 'error',
                'status_code' => 401,
                'data' => [
                    'error' => 'Please contact the owner to view this'
                ]
            ], 401);
        }

        $records = $this->testService->fetchResults($reconciliation, $user);

        return response()->json([
            'message' => 'Reconciled records fetched successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $records
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reconcile/{reconciliation}",
     *     summary="Reconcile a ledger and a statement",
     *     description="Perform reconciliation between a ledger and a statement based on specified action",
     *     tags={"Reconciliation"},
     *     @OA\Parameter(
     *         name="reconciliation",
     *         in="path",
     *         required=true,
     *         description="Reconciliation ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"ledger", "statement", "action"},
     *                 @OA\Property(
     *                     property="ledger",
     *                     type="object",
     *                     @OA\Property(property="Date", type="string", description="Date of the ledger entry"),
     *                     @OA\Property(property="Person", type="string", description="Description of the ledger entry"),
     *                     @OA\Property(property="Amount", type="integer", description="Amount in the ledger entry")
     *                 ),
     *                 @OA\Property(
     *                     property="statement",
     *                     type="object",
     *                     @OA\Property(property="Date", type="string", description="Date of the statement entry"),
     *                     @OA\Property(property="Person", type="string", description="Description of the statement entry"),
     *                     @OA\Property(property="Amount", type="integer", description="Amount in the statement entry")
     *                 ),
     *                 @OA\Property(
     *                     property="action",
     *                     type="string",
     *                     enum={"match", "unmatch"},
     *                     description="Reconciliation action to perform"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful reconciliation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciliation successful"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="matches", type="integer", example=5),
     *                 @OA\Property(property="only_in_ledger", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="only_in_statement", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function matchUnmatch(ManualReconciliationRequest $request, Reconciliation $reconciliation){
        $validated = $request->validated();

        $res = $this->testService->matchUnmatch($reconciliation, $validated['statements'], $validated['ledgers'], $validated['action']);

        return response()->json([
            'message' => 'Reconciliation updated successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $res
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reconcile-embeddings",
     *     summary="New Reconciliation Approach - Embeddings",
     *     description="Upload and compare two sets of files using various reconciliation methods",
     *     tags={"Reconciliation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"bank_statements", "ledgers"},
     *                 @OA\Property(
     *                     property="bank_statements",
     *                     type="array",
     *                     @OA\Items(
     *                         type="string",
     *                         format="binary"
     *                     ),
     *                     description="Array of Bank Statement CSV files"
     *                 ),
     *                 @OA\Property(
     *                     property="ledgers",
     *                     type="array",
     *                     @OA\Items(
     *                         type="string",
     *                         format="binary"
     *                     ),
     *                     description="Array of Ledger CSV files"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful reconciliation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciliation successful"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="matches", type="integer", example=5),
     *                 @OA\Property(
     *                     property="unmatched_statements",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example="Payment for Student: STU1029")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="unmatched_ledgers",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example="Exam Fee")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid file format")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="The bank_statements field is required")
     *         )
     *     )
     * )
     */
    public function testEmbeddings(Request $request): JsonResponse
    {
        $request->validate([
            'bank_statements' => 'required|array',
            'ledgers' => 'required|array',
            'bank_statements.*' => 'required|file|mimes:csv|max:2048',
            'ledgers.*' => 'required|file|mimes:csv|max:2048',
        ], [
            'bank_statements.*.mimes' => 'Bank Statement must be a CSV.',
            'ledgers.*.mimes' => 'Ledger must be a CSV.',
            'bank_statements.*.max' => 'Bank statement must not be larger than 2MB.',
            'ledgers.*.max' => 'Ledger must not be larger than 2MB.',
        ]);

        try {
            Log::info('Uploaded bank statements:', ['files' => $request->file('bank_statements')]);
            Log::info('Uploaded ledgers:', ['files' => $request->file('ledgers')]);

            $statements = [];
            $ledgers = [];

            foreach ($request->file('bank_statements') as $file) {
                Log::info('File', ['file' => $file]);
                $statementPath = $file->store('uploads');
                $statementFullPath = Storage::path($statementPath);
                Log::info('Stored bank statement:', ['path' => $statementFullPath]);

                if (!$this->isValidFileFormat($statementFullPath)) {
                    Storage::delete([$statementPath]);
                    return response()->json(['error' => 'One of the files is not in the correct format.'], 422);
                }

                $statements[] = $statementFullPath;
            }

            foreach ($request->file('ledgers') as $file) {
                $ledgerPath = $file->store('uploads');
                $ledgerFullPath = Storage::path($ledgerPath);
                Log::info('Stored ledger:', ['path' => $ledgerFullPath]);

                if (!$this->isValidFileFormat($ledgerFullPath)) {
                    Storage::delete([$ledgerPath]);
                    return response()->json(['error' => 'One of the files is not in the correct format.'], 422);
                }

                $ledgers[] = $ledgerFullPath;
            }

            Log::info('Dispatching reconciliation job:', [
                'statements' => $statements,
                'ledgers' => $ledgers,
                'user' => $request->user(),
            ]);

            $reconciliation = $this->testService->storeReconciliation($statements, $ledgers,  $request->user()->id);
            ProcessReconciliation::dispatch($statements, $ledgers, $request->user(), $reconciliation);

            return response()->json([
                "message" => "Reconciliation initiated successfully",
                "status" => "success",
                "status_code" => 200,
                'data' => [
                    'reconciliation_id' => $reconciliation->id
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to reconcile: ' . $e->getMessage(), ['trace' => $e->getTrace()]);
            return response()->json([
                "message" => "Failed to reconcile",
                "status" => "error",
                "status_code" => 500,
                'data' => [
                    'error' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reconciliations",
     *     summary="Get reconciliations",
     *     description="Fetch reconciliations for the logged-in user",
     *     tags={"Reconciliation"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Response(
     *         response=200,
     *         description="Reconciliations fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciled records fetched successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function listUserReconciliations(Request $request){
        return $this->testService->fetchUserReconciliations($request->user());
    }


    private function isValidFileFormat(string $filePath): bool
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), ['csv', 'xls', 'xlsx']);
    }

}
