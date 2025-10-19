<?php

namespace App\Http\Controllers;

use App\Models\Typeticket;
use Illuminate\Http\Request;

class TypeticketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $typetickets = Typeticket::get();

        return response()->json($typetickets);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $typeticket = new Typeticket();
        $typeticket->nom = $request->nom;
        $typeticket->prix = $request->prix;
        $typeticket->evenement_id = $request->evenement_id;
        $typeticket->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket type Created'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Typeticket $typeticket)
    {
        return response()->json($typeticket);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Typeticket $typeticket)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Typeticket $typeticket)
    {
        $typeticket->nom = $request->nom;
        $typeticket->prix = $request->prix;
        $typeticket->evenement_id = $request->evenement_id;
        $typeticket->update();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket type Updated'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Typeticket $typeticket)
    {
        $typeticket->delete();
        return response()->json("Ticket type Deleted");
    }
}
