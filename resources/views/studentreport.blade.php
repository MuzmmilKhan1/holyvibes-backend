<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Performance Report - HolyVibes</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background-color: white; padding: 0; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: center; background-color: #4CAF50; padding: 20px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
            <h1 style="color: #333; font-size: 24px; margin: 0;">HolyVibes</h1>
        </div>
        <div style="padding: 30px;">
            <h2 style="color: #333;">Hello {{ $student->name }},</h2>
            <p>Your performance report for the course <strong>{{ $course->name }}</strong> has been added by your teacher.</p>
            <ul style="line-height: 1.8;">
                <li><strong>Class:</strong> {{ $class->title }}</li>
                <li><strong>Attendance:</strong> {{ $performance->attendance ?? 'N/A' }}</li>
                <li><strong>Test Remarks:</strong> {{ $performance->test_remarks ?? 'N/A' }}</li>
                <li><strong>Participation:</strong> {{ $performance->participation ?? 'N/A' }}</li>
                <li><strong>Suggestions:</strong> {{ $performance->suggestions ?? 'N/A' }}</li>
            </ul>
            <p>Keep working hard and reach out to your teacher for any guidance!</p>
            <p>Best regards,<br>The HolyVibes Team</p>
        </div>
    </div>
</body>
</html>
