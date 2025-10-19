<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->adresse = $request->adresse;
        $user->update();

        return response()->json([
            'status' => 'success',
            'message' => 'Profile est modifié.'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
