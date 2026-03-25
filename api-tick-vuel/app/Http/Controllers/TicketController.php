<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketReplyStoreRequest;
use App\Http\Requests\TicketReplyUpdateRequest;
use App\Http\Requests\TicketStoreRequest;
use App\Http\Requests\TicketUpdateRequest;
use App\Http\Resources\TicketReplyResource;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketReply;
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
        $user = auth()->user();

        try {
            $q = Ticket::query();

            if($user->role == 'user') $q->where('user_id', $user->id);

            if($request->search) {
                $q->where(function ($query) use ($request) {
                    $query->where('title', 'like', '%'.$request->search.'%')
                        ->orWhere('description', 'like', '%'.$request->search.'%')
                        ->orWhere('code', 'like', '%'.$request->search.'%');
                });
            }

            if($request->status) {
                $q->where('status', $request->status);
            }

            if($request->priority) {
                $q->where('priority', $request->priority);
            }

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
        $user = auth()->user();

        // Generate ticket with STR if doesnt exists in db
        $tiCode = 'TIC-'.Str::random(10);
        while (Ticket::where('code', $tiCode)->exists()) {
            $tiCode = 'TIC-'.Str::random(10);
        }

        $data = $request->validated();

        DB::beginTransaction();

        try {
            $ticket = new Ticket;
            $ticket->user_id = $user->id;
            $ticket->code = $tiCode;
            $ticket->title = $data['title'];
            $ticket->description = $data['description'];
            $ticket->priority = $data['priority'];

            if ($request->hasFile('attachment')) {
                $ticket->attachment = $request->file('attachment')->store('attachments', 'public');
            }

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
    public function show($code)
    {
        $user = auth()->user();

        try {
            $ticket = Ticket::where('code', $code)->first();

            if(!$ticket) {
                return response()->json([
                    'message' => 'Ticket not found',
                ], 404);
            }

            if($user->role == 'user' && $ticket->user_id != $user->id) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'message' => 'Get Ticket Detail!',
                'data' => new TicketResource($ticket),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Internal Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($code)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TicketUpdateRequest $request, string $code)
    {
        $user = auth()->user();
        $data = $request->validated();

        try {
            $ticket = Ticket::where('code', $code)->first();

            if (!$ticket) {
                return response()->json([
                    'message' => 'Ticket not found',
                ], 404);
            }

            if ($user->role == 'user' && $ticket->user_id != $user->id) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            $ticket->update($data);

            return response()->json([
                'message' => 'Ticket updated successfully',
                'data' => new TicketResource($ticket),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $code)
    {
        $user = auth()->user();

        try {
            $ticket = Ticket::where('code', $code)->first();

            if (!$ticket) {
                return response()->json([
                    'message' => 'Ticket not found',
                ], 404);
            }

            if ($user->role == 'user' && $ticket->user_id != $user->id) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            $ticket->delete();

            return response()->json([
                'message' => 'Ticket deleted successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to delete ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /*
    * Reply to the specified resource in storage.
    */
    public function storeReply(TicketReplyStoreRequest $request, $code)
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $ticket = Ticket::where('code', $code)->first();

            if(!$ticket) {
                return response()->json([
                    'message' => 'Ticket not found',
                ], 404);
            }

            if($user->role == 'user' && $ticket->user_id != $user->id) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            $ticketReply = new TicketReply();
            $ticketReply->ticket_id = $ticket->id;
            $ticketReply->user_id = $user->id;
            $ticketReply->content = $data['content'];

            if ($request->hasFile('attachment')) {
                $ticketReply->attachment = $request->file('attachment')->store('attachments', 'public');
            }

            $ticketReply->save();

            if($user->role == 'admin') {
                $ticket->status = $data['status'];
                if($data['status'] == 'resolved') {
                    $ticket->completed_at = now();
                }
                $ticket->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Reply added successfully',
                'data' => new TicketReplyResource($ticketReply),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Internal Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /*
    * Update the specified reply in storage.
    */
    public function updateReply(TicketReplyUpdateRequest $request, $id)
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $ticketReply = TicketReply::find($id);

            if (!$ticketReply) {
                return response()->json([
                    'message' => 'Reply not found',
                ], 404);
            }

            if ($ticketReply->user_id != $user->id) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            $ticketReply->content = $data['content'];
            $ticketReply->save();

            if ($user->role == 'admin' && isset($data['status'])) {
                $ticket = $ticketReply->ticket;
                $ticket->status = $data['status'];
                if ($data['status'] == 'resolved') {
                    $ticket->completed_at = now();
                } else {
                    $ticket->completed_at = null;
                }
                $ticket->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Reply updated successfully',
                'data' => new TicketReplyResource($ticketReply),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Internal Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /*
    * Remove the specified reply from storage.
    */
    public function destroyReply($id)
    {
        $user = auth()->user();

        try {
            $ticketReply = TicketReply::find($id);

            if (!$ticketReply) {
                return response()->json([
                    'message' => 'Reply not found',
                ], 404);
            }

            // User can delete their own reply, Admin can delete any reply
            if ($user->role == 'user' && $ticketReply->user_id != $user->id) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            $ticketReply->delete();

            return response()->json([
                'message' => 'Reply deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
