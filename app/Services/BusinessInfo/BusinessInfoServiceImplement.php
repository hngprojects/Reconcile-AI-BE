<?php

namespace App\Services\BusinessInfo;

use LaravelEasyRepository\ServiceApi;
use App\Repositories\BusinessInfo\BusinessInfoRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;

class BusinessInfoServiceImplement extends ServiceApi implements BusinessInfoService{

    /**
     * set title message api for CRUD
     * @param string $title
     */
     protected string $title = "Business Info Service";
     /**
     * uncomment this to override the default message
     * protected string $create_message = "";
     * protected string $update_message = "";
     * protected string $delete_message = "";
     */

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    public function __construct(protected BusinessInfoRepository $mainRepository)
    {
    }

    public function setupBusinessInfo(Request $request): array
    {
        try {
            $validated = $request->validate([
                'business_name' => 'required|string|max:255',
                'business_type' => 'required|string|max:255',
                'currency' => 'required|string|max:3',
                'reporting_year' => 'sometimes|string'
            ]);

            $year = $this->parseReportingYear($validated['reporting_year'] ?? 'January - December');

            $businessInfo = $this->mainRepository->createBusinessInfo([
                'user_id' => $request->user()->id,
                'name' => $validated['business_name'],
                'type' => $validated['business_type'],
                'currency' => $validated['currency'],
                'reporting_year_start' => $year['start'],
                'reporting_year_end' => $year['end'],
            ]);

            return $this->formatResponse(
                201,
                'Business info created successfully',
                $businessInfo
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->formatResponse(
                422,
                'Validation error',
                null,
                $e->errors()
            );
                
        } catch (Exception $e) {
            return $this->formatResponse(
                400,
                'Failed to create business info',
                null,
                $e->getMessage()
            );
        }
    }

    private function parseReportingYear(string $yearRange): array
    {
        $currentYear = now()->year;
        $months = explode(' - ', $yearRange);
        
        return [
            'start' => Carbon::parse("first day of {$months[0]} $currentYear")->toDateString(),
            'end' => Carbon::parse("last day of {$months[1]} $currentYear")->toDateString()
        ];
    }

    private function formatResponse(int $code, string $message, $data = null, $error = null): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'error' => $error
        ];
    }
}
