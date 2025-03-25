<?php

namespace App\Repositories\Reconciliation;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Reconciliation;
use App\Models\User;
use App\Models\ReconciledRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ReconciliationRepositoryImplement extends Eloquent implements ReconciliationRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected Reconciliation $model;

    public function __construct(Reconciliation $model, ReconciledRecord $record)
    {
        $this->model = $model;
        $this->recordModel = $record;
    }

    public function store(array $data){
        $reconciliation = new Reconciliation();
        $reconciliation->id = Str::uuid();
        $reconciliation->user_id = $data['user_id'];
        $reconciliation->option = $data['option'];
        $reconciliation->save();

        return $reconciliation;
    }

    public function list(User $user){
        return $this->model
                    ->where('user_id', '=', $user->id)
                    ->get()
                    ->sortBy('created_at')
                    ->map(function ($rec, $index){
                        $result = $this->findResponse($rec);
                        $date = new \DateTime($rec->created_at);
                        $titleDate = $date->format('Ymd');
                        $id = str_pad(($index+1), 3, '0', STR_PAD_LEFT);

                        return [
                            'id' => $rec->id,
                            'title' => "RCL-{$titleDate}-{$id}",
                            'status' => $result ? 'Completed' : 'Pending',
                            'date' => $date->format('Y-m-d')
                        ];
                    });
    }

    public function storeResponse(array $data)
    {
        return $this->recordModel->create($data);
    }

    public function findResponse(Reconciliation $reconciliation)
    {
        return $this->recordModel->where('reconciliation_id', '=', $reconciliation->id)->first();
    }

    public function updateResponse(Reconciliation $reconciliation, array $data)
    {
        $record = $this->recordModel->where('reconciliation_id', '=', $reconciliation->id)->first();

        $record->data = $data;

        $record->save();

        return $record;
    }
}
