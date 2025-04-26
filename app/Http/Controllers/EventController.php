<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventBilling;
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

    public function get_std_events(Request $request)
    {
        $user = $request->get('user');
        $events = EventParticipant::with('student', 'event')->where('studentID', $user->student_id)->get();
        return response()->json([
            'message' => 'Events found successfully.',
            'events' => $events
        ], 200);
    }

    public function event_payment(Request $request)
    {
        $validated = $request->validate([
            'eventID' => 'required|exists:events,id',
            'method' => 'required|in:easypaisa,jazzcash,bank',
            'receipt' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->get('user');
        $receiptFile = $request->file('receipt');

        $imageContent = file_get_contents($receiptFile->getRealPath());
        $base64Image = base64_encode($imageContent);
        $mimeType = $receiptFile->getMimeType();
        $dataUri = 'data:' . $mimeType . ';base64,' . $base64Image;

        $addPayment = EventBilling::create([
            'studentID' => $user->student_id,
            'eventID' => $validated['eventID'],
            'receipt' => $dataUri,
            'paymentMethod' => $validated['method'],
        ]);

        return response()->json([
            'message' => 'Payment submitted successfully. Awaiting verification.',
            'payment' => $addPayment
        ]);
    }



    public function get_std_event_billing($studentID, $eventId)
    {
        $billingDetails = EventBilling::where('studentID', $studentID)
            ->where('eventID', $eventId)
            ->get();
        if (!$billingDetails) {
            return response()->json([
                'message' => 'No billing details found for this student and event.',
            ], 404);
        }
        return response()->json([
            'message' => 'Event billing details found successfully.',
            'billingDetails' => $billingDetails
        ]);
    }

    public function update_payemnt_status(Request $request)
    {
        $validated = $request->validate([
            'studentID' => 'required|exists:students,id',
            'eventID' => 'required|exists:events,id',
            'paymentStatus' => 'required|in:pending,paid,rejected',
        ]);
        $participant = EventParticipant::where('studentID', $validated['studentID'])
            ->where('eventID', $validated['eventID'])
            ->first();
        if (!$participant) {
            return response()->json([
                'message' => 'Participant not found for the specified student and event.'
            ], 404);
        }
        $participant->payment_status = $validated['paymentStatus'];
        $participant->save();
        return response()->json([
            'message' => 'Payment status updated successfully.',
            'participant' => $participant,
        ]);
    }

    public function join_cancel_membership($eventID, $studentID)
    {
        $participant = EventParticipant::where('eventID', $eventID)
            ->where('studentID', $studentID)
            ->first();
        if (!$participant) {
            return response()->json([
                'message' => 'Participant not found.'
            ], 404);
        }
        $participant->is_member = !$participant->is_member;
        $participant->save();
        return response()->json([
            'message' => $participant->is_member
                ? 'Joined event as a member.'
                : 'Cancelled membership from event.',
            'participant' => $participant
        ]);
    }

    public function add_students(Request $request, $eventID)
    {
        try {
            $request->validate([
                'students' => 'required|array',
                'students.*' => 'required|exists:students,id',
            ]);
            $eventExists = Event::where('id', $eventID)->first();
            if (!$eventExists) {
                return response()->json([
                    'message' => 'Event not found.'
                ], 404);
            }
            foreach ($request->students as $studentID) {
                $existingParticipation = EventParticipant::where('eventID', $eventID)
                    ->where('studentID', $studentID)
                    ->first();
                if ($existingParticipation) {
                    $existingParticipation->update([
                        'studentID' => $studentID,
                    ]);
                } else {
                    EventParticipant::create([
                        'eventID' => $eventID,
                        'studentID' => $studentID,
                        'is_member' => false,
                        'payment_status' => $eventExists->isPaid ? 'pending' : "not_required",
                    ]);
                }
            }
            return response()->json([
                'message' => 'Students processed successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing students.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete_event($eventID)
    {
        if (!Event::destroy($eventID)) {
            return response()->json([
                'message' => 'Event not found.',
            ], 404);
        }
        return response()->json([
            'message' => 'Event deleted successfully.',
        ], 200);
    }
    

}
