<?php

namespace App\Http\Controllers;

use App\Services\ReconciliationService;
use App\Services\NewReconciliation\NewReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
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

            $file1FullPath = Storage::path($file1Path);
            $file2FullPath = Storage::path($file2Path);

            if (!$this->isValidFileFormat($file1FullPath) || !$this->isValidFileFormat($file2FullPath)) {
                Storage::delete([$file1Path, $file2Path]);
                return response()->json(['error' => 'One or both files are not in the correct format.'], 422);
            }

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
     *                 type="object",
     *                 @OA\Property(
     *                     property="bank_statements[0][file]",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *                 @OA\Property(
     *                     property="bank_statements[0][bank_account]",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="bank_statements[0][period]",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="ledgers[]",
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 )
     *              ),
     *          )
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
            'title' => 'required|string',
            'bank_statements' => 'required|array',
            'ledgers' => 'required|array',
            'bank_statements.*.file' => 'required|file|mimes:csv|max:2048',
            'bank_statements.*.bank_account' => 'required|uuid|exists:bank_accounts,id',
            'bank_statements.*.period' => 'required|string',
            'ledgers.*' => 'required|string|exists:bookkeeping_ledgers,id',
        ], [
            'bank_statements.*.file.mimes' => 'Bank Statement must be a CSV.',
            'bank_statements.*.file.max' => 'Bank statement must not be larger than 2MB.',
        ]);

        try {
            $statements = [];
            $ledgers = $request->input('ledgers');

            foreach ($request->input('bank_statements') as $index => $statementData) {
                $file = $request->file("bank_statements.$index.file");
                $bankAccount = $statementData['bank_account'];
                $period = $statementData['period'];

                if (!$file) {
                    return response()->json(['error' => "Missing file for bank_statements[$index]."], 422);
                }

                $statementPath = $file->store('uploads');
                $statementFullPath = Storage::path($statementPath);

                if (!$this->isValidFileFormat($statementFullPath)) {
                    Storage::delete([$statementPath]);
                    return response()->json(['error' => 'One of the files is not in the correct format.'], 422);
                }

                $statements[] = [
                    'path' => $statementFullPath,
                    'bank_account_id' => $bankAccount,
                    'period' => $period,
                ];
            }

            $reconciliation = $this->testService->storeReconciliation($statements, $ledgers,  $request->input('title'), $request->user()->id);
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
