<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Student;
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

        $isPaid = $request->price != null;

        if ($request->id == 0) {
            $event = Event::create([
                'title' => $request->title,
                'description' => $request->description,
                'isPaid' => $isPaid,
                'price' => $request->price,
                'time' => $request->time,
                'link' => $request->link,
            ]);
            $allowedStudents = Student::where('status', 'allowed')->get();
            foreach ($allowedStudents as $student) {
                EventParticipant::create([
                    'eventID' => $event->id,
                    'studentID' => $student->id,
                    'is_member' => false,
                    'payment_status' => $isPaid ? 'pending' : 'not_required',
                ]);
            }

        } else {
            $event = Event::findOrFail($request->id);
            $event->update([
                'title' => $request->title,
                'description' => $request->description,
                'isPaid' => $isPaid,
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
    public function get_event_members($eventId)
    {
        $eventMembers = EventParticipant::with(['student', 'event'])
            ->where('eventID', $eventId)
            ->get();
        return response()->json([
            'message' => 'Event members found successfully.',
            'event' => $eventMembers
        ], 200);
    }
}
