<?php

namespace App\Http\Controllers;

use App\Exports\BeerExport;
use App\Http\Requests\BeerRequest;
use App\Jobs\ExportJob;
use App\Jobs\SendExportEmailJob;
use App\Jobs\StoreExportDataJob;
use App\Mail\ExportEmail;
use App\Models\Export;
use App\Services\PunkapiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BeerController extends Controller
{
    public function index(BeerRequest $request, PunkapiService $service)
    {
        $beers = $service->getBeers(...$request->validated());

        return Inertia::render('Beers', ['beers' => $beers]);
    }

    public function export(BeerRequest $request)
    {
        $filename = 'cervejas-encontradas-' . now()->format('Y-m-d - H_i') . '.xlsx';

        try {

            ExportJob::withChain([
                new SendExportEmailJob($filename),
                new StoreExportDataJob(auth()->user(), $filename),
            ])->dispatch($request->validated(), $filename);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao exportar dados'
            ], 500);
        }

        return 'relatorio criado';
    }


}
