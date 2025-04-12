<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function create_or_updateEvent(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'nullable|numeric',
            'time' => 'required|date',
            'link' => 'nullable|string|max:255',
        ]);

        if ($request->id == 0) {
            Event::create([
                'title' => $request->title,
                'description' => $request->description,
                'isPaid' => $request->price == null ? false : true,
                'price' => $request->price,
                'time' => $request->time,
                'link' => $request->link,
            ]);

        } else {
            $event = Event::findOrFail($request->id);
            $event->update([
                'title' => $request->title,
                'description' => $request->description,
                'isPaid' => $request->price == null ? false : true,
                'price' => $request->price,
                'time' => $request->time,
                'link' => $request->link,
            ]);
        }
        return response()->json(['message' => 'Event saved successfully']);
    }

    public function get_events()
    {
        $events = Event::all();
        return response()->json([
            'message' => 'Events found successfully.',
            'event' => $events
        ], 200);
    }
}
