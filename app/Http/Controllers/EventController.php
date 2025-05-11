<?php

namespace App\Http\Controllers;

use App\Mail\ClassMail;
use App\Models\Event;
use App\Models\EventBilling;
use App\Models\EventParticipant;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
            $title = 'New Event Created';
            $subtitle = 'An event has been created.';
            $body = 'A new event titled "<strong>' . $event->title . '</strong>" has been created.<br><br>' .
                'Description: ' . $event->description . '<br>' .
                'Time: ' . $event->time . '<br>' .
                'Price: ' . ($isPaid ? '$' . $event->price : 'Free') . '<br>' .
                'Event Link: <a href="' . $event->link . '">' . $event->link . '</a><br><br>' .
                'Thank you,<br>The HolyVibes Team';

            $students = Student::all();
            foreach ($students as $student) {
                Mail::to($student->email)->send(new ClassMail($title, $subtitle, $body));
            }

            $teachers = Teacher::all();
            foreach ($teachers as $teacher) {
                Mail::to($teacher->email)->send(new ClassMail($title, $subtitle, $body));
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
            $title = 'Event Updated';
            $subtitle = 'An event has been updated.';
            $body = 'The event titled "<strong>' . $event->title . '</strong>" has been updated.<br><br>' .
                'Description: ' . $event->description . '<br>' .
                'Time: ' . $event->time . '<br>' .
                'Price: ' . ($isPaid ? '$' . $event->price : 'Free') . '<br>' .
                'Event Link: <a href="' . $event->link . '">' . $event->link . '</a><br><br>' .
                'Thank you,<br>The HolyVibes Team';
            $students = Student::all();
            foreach ($students as $student) {
                Mail::to($student->email)->send(new ClassMail($title, $subtitle, $body));
            }
            $teachers = Teacher::all();
            foreach ($teachers as $teacher) {
                Mail::to($teacher->email)->send(new ClassMail($title, $subtitle, $body));
            }
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

        $admin = User::where('role', 'admin')->first();
        $user = $request->get('user');
        $student = Student::find($user->student_id);
        $event = Event::find($validated['eventID']);
        $receiptFile = $request->file('receipt');

        $imageContent = file_get_contents($receiptFile->getRealPath());
        $base64Image = base64_encode($imageContent);
        $mimeType = $receiptFile->getMimeType();
        $dataUri = 'data:' . $mimeType . ';base64,' . $base64Image;

        $addPayment = EventBilling::create([
            'studentID' => $student->id,
            'eventID' => $event->id,
            'receipt' => $dataUri,
            'paymentMethod' => $validated['method'],
        ]);
        $studentTitle = 'Event Payment Submitted';
        $studentSubtitle = 'Your payment has been received';
        $studentBody = '<p>Dear ' . $student->name . ',</p>
        <p>We have received your payment for the event <strong>"' . $event->title . '"</strong>.</p>
        <p>Payment Method: <strong>' . ucfirst($validated['method']) . '</strong></p>
        <p>The admin will verify your payment shortly. Thank you for participating!</p>
        <p>Best regards,<br>The HolyVibes Team</p>';
        Mail::to($student->email)->send(new ClassMail($studentTitle, $studentSubtitle, $studentBody));
        if ($admin) {
            $adminTitle = 'New Event Payment Received';
            $adminSubtitle = 'A student has submitted an event payment';
            $adminBody = '<p>Dear Admin,</p>
            <p>The student <strong>' . $student->name . '</strong> has submitted a payment for the event <strong>"' . $event->name . '"</strong>.</p>
            <p>Payment Method: <strong>' . ucfirst($validated['method']) . '</strong></p>
            <p>Please verify the payment and approve the participation accordingly.</p>
            <p>Regards,<br>The HolyVibes System</p>';
            Mail::to($admin->email)->send(new ClassMail($adminTitle, $adminSubtitle, $adminBody));
        }
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

        $student = Student::find($validated['studentID']);
        $event = Event::find($validated['eventID']);

        $title = 'Event Payment Status Updated';
        $subtitle = 'Status: ' . ucfirst($validated['paymentStatus']);
        $body = 'Dear ' . $student->name . ',<br><br>' .
            'Your payment status for the event "<strong>' . $event->title . '</strong>" has been updated to: <strong>' . ucfirst($validated['paymentStatus']) . '</strong>.<br>' .
            'Please check your dashboard for more details.<br><br>' .
            'Thank you,<br>The HolyVibes Team';
        Mail::to($student->email)->send(new ClassMail($title, $subtitle, $body));

        return response()->json([
            'message' => 'Payment status updated successfully.',
            'participant' => $participant,
        ]);
    }

    public function join_cancel_membership($eventID, $studentID)
    {
        $admin = User::where('role', 'admin')->first();
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
            $event = Event::find($eventID);
            if (!$event) {
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
                        'payment_status' => $event->isPaid ? 'pending' : 'not_required',
                    ]);
                }
                $student = Student::find($studentID);
                if ($student) {
                    $title = 'You Have Been Added to an Event';
                    $subtitle = 'Event Participation Notification';
                    $body = '<p>Dear ' . $student->name . ',</p>
                    <p>You have been added to the event <strong>"' . $event->name . '"</strong>.</p>' .
                        ($event->isPaid
                            ? '<p>Please proceed with the payment to confirm your participation.</p>'
                            : '<p>No payment is required for this event.</p>') .
                        '<p>Best regards,<br>The HolyVibes Team</p>';

                    Mail::to($student->email)->send(new ClassMail($title, $subtitle, $body));
                }
            }
            return response()->json([
                'message' => 'Students processed and notified successfully.',
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
        $event = Event::find($eventID);
        if (!$event) {
            return response()->json([
                'message' => 'Event not found.',
            ], 404);
        }
        $students = Student::all();
        $teachers = Teacher::all();
        foreach ($students as $student) {
            $title = 'Event Deleted';
            $subtitle = 'An event has been removed';
            $body = '<p>Dear ' . $student->name . ',</p>
            <p>The event <strong>"' . $event->title . '"</strong> has been deleted.</p>
            <p>We apologize for any inconvenience caused.</p>
            <p>Best regards,<br>The HolyVibes Team</p>';
            Mail::to($student->email)->send(new ClassMail($title, $subtitle, $body));
        }
        foreach ($teachers as $teacher) {
            $title = 'Event Deleted';
            $subtitle = 'An event has been removed';
            $body = '<p>Dear ' . $teacher->name . ',</p>
            <p>The event <strong>"' . $event->name . '"</strong> has been deleted.</p>
            <p>Best regards,<br>The HolyVibes Team</p>';

            Mail::to($teacher->email)->send(new ClassMail($title, $subtitle, $body));
        }
        $event->delete();
        return response()->json([
            'message' => 'Event deleted and all users notified.',
        ], 200);
    }
}
