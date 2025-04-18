<?php

namespace App\Services\BusinessInfo;

use LaravelEasyRepository\ServiceApi;
use App\Repositories\BusinessInfo\BusinessInfoRepository;
use Illuminate\Support\Facades\Validator;
use Exception;

class BusinessInfoServiceImplement extends ServiceApi implements BusinessInfoService{

    protected string $title = "Business Info Service";

    public function __construct(protected BusinessInfoRepository $mainRepository)
    {
    }

    public function setupBusinessInfo($request): array
    {
        try {
            $validated = $this->validateRequest($request);

            $businessInfo = $this->mainRepository->createBusinessInfo([
                'user_id' => $request->user()->id,
                'name' => $validated['business_name'],
                'type' => $validated['business_type'],
                'currency' => $validated['currency'],
                'reporting_year' => $validated['reporting_year'],
            ]);

            return $this->response(201, 'Business info created successfully', $businessInfo);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->response(422, 'Validation error', null, $e->errors());
        } catch (Exception $e) {
            return $this->response(400, 'Failed to create business info', null, $e->getMessage());
        }
    }

    public function updateBusinessInfo(string $id, $request): array
    {
        try {
            $validated = $this->validateRequest($request);

            $businessInfo = $this->mainRepository->findOne($id);
            if (!$businessInfo) {
                return $this->response(404, 'Business info not found');
            }

            $updatedBusinessInfo = $this->mainRepository->updateBusinessInfo($id, [
                'name' => $validated['business_name'],
                'type' => $validated['business_type'],
                'currency' => $validated['currency'],
                'reporting_year' => $validated['reporting_year'],
            ]);
            
            return $this->response(200, 'Business info updated successfully', $updatedBusinessInfo);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->response(422, 'Validation error', null, $e->errors());
        } catch (Exception $e) {
            return $this->response(400, 'Failed to update business info', null, $e->getMessage());
        }
    }

    private function validateRequest($request): array
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
            'currency' => 'required|string|max:3',
            'reporting_year' => 'required|string|in:January - December,April - March,July - June'
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    private function response(int $code, string $message, $data = null, $error = null): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'error' => $error,
        ];
    }
}
