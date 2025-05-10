<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px;">
    <div
        style="max-width: 600px; margin: auto; background-color: white; padding: 0; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <div
            style="display: flex; flex-direction: row; align-items: center; justify-content: center; background-color: #4CAF50; padding: 20px; text-align: center; border-top-left-radius: 8px; border-top-right-radius: 8px;">
            <h1 style="color: #333; margin: 10px 0 0; font-size: 24px;">HolyVibes</h1>
        </div>
        <div style="padding: 30px;">
            <h2 style="color: #333;">{{ $subtitle }}</h2>
            {!! $body !!}
        </div>
    </div>
</body>

</html>