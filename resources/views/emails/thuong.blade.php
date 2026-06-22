<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu thưởng</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.8;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            font-size: 15px;
        }
        p {
            margin: 0 0 12px 0;
        }
        .signature {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <p>Dear {{ $employeeName }},</p>

    @if(!empty($description))
        <p>{!! nl2br(e($description)) !!}</p>
    @else
        <p>{{ env('MAIL_DEPARTMENT', 'Phòng HCNS') }} xin gửi lại anh/chị phiếu thưởng.<br>
        Anh/chị nếu có thắc mắc, xin vui lòng liên hệ lại phòng nhân sự để được giải đáp.</p>
    @endif

    <div class="signature">
        <p>Trân trọng!</p>
    </div>
</body>
</html>
