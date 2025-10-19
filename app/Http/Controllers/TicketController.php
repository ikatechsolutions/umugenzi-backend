<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tickets = Ticket::get();

        return response()->json($tickets);
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
        $ticket = new Ticket();
        $ticket->typeticket_id = $request->typeticket_id;
        $ticket->quantite = $request->quantite;
        $ticket->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket Created'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        return response()->json($ticket);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ticket $ticket)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $ticket->typeticket_id = $request->typeticket_id;
        $ticket->quantite = $request->quantite;
        $ticket->update();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket Updated'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket Deleted'
        ]);
    }
}
