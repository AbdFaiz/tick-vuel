<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketStoreRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $q = Ticket::query();

            if($request->search) {
                $q->where('title', 'like', '%'.$request->search.'%')
                ->orWhere('description', 'like', '%'.$request->search.'%');
            }

            if($request->status) {
                $q->where('status', $request->status);
            }

            if($request->priority) {
                $q->where('priority', $request->priority);
            }

            if(auth()->user()->role == 'user') $q->where('user_id', auth()->user()->id);

            return response()->json([
                'message' => 'Get All Tickets!',
                'data' => TicketResource::collection($q->get())
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Internal Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
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
    public function store(TicketStoreRequest $request)
    {
        // Generate ticket with STR if doesnt exists in db
        $tiCode = 'TIC-'.Str::random(10);
        while (Ticket::where('code', $tiCode)->exists()) {
            $tiCode = 'TIC-'.Str::random(10);
        }

        $data = $request->validated();

        DB::beginTransaction();

        try {
            $ticket = new Ticket;
            $ticket->user_id = auth()->user()->id;
            $ticket->code = $tiCode;
            $ticket->title = $data['title'];
            $ticket->description = $data['description'];
            $ticket->priority = $data['priority'];
            $ticket->save();

            DB::commit();

            return response()->json([
                'message' => 'Ticket created successfully',
                'data' => new TicketResource($ticket),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
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
