<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evenement;
use App\Models\Historiqueevent;

class HistoriqueeventController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/historiques",
     * summary="Historiques des événements",
     * @OA\Response(
     * response=200,
     * description="Liste des historiques",
     * )
     * )
     */
    public function index()
    {
        $historiques = Historiqueevent::with('user','evenement')->get();

        return response()->json($historiques);
    }

    
    // public function store($evenement)
    // {
    //     $event = Evenement::findOrFail($evenement);

    //     $historique = new Historiqueevent();
    //     $historique->evenement_id = $event->id;
    //     $historique->user_id = auth()->id();

    //     $historique->save();

    //     return response()->json([
    //         'message' => "L'événement est validé avec success",
    //     ],201);

    // }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
