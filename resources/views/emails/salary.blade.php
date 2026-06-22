<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu lương</title>
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
@php
    $deadlineDate = '';
    if ($month) {
        try {
            $date = \Carbon\Carbon::createFromFormat('m/Y', $month)->addMonth()->setDay(9);
            $deadlineDate = $date->format('d/m/Y');
        } catch (\Exception $e) {
            $deadlineDate = '';
        }
    }
@endphp

    <p>Dear {{ $employeeName }},</p>

    <p>{{ env('MAIL_DEPARTMENT', 'Phòng HCNS') }} xin gửi lại anh/chị phiếu lương{{ $month ? ' tháng ' . $month : '' }}.<br>
    Anh/chị nếu có thắc mắc, xin vui lòng liên hệ lại phòng nhân sự để được giải đáp.</p>

    @if($deadlineDate)
    <p>Sau 09:00 ngày {{ $deadlineDate }}, nếu không có phản hồi, anh/chị được coi là đã chấp nhận phiếu lương.<br>
    Mọi điều chỉnh sẽ tiến hành vào tháng lương sau.</p>
    @endif

    <div class="signature">
        <p>Trân trọng!</p>
    </div>
</body>
</html>
