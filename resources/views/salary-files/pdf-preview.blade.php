<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; margin: 12px; }
        table { border-collapse: collapse; width: 100%; font-size: 10px; }
        td { border: 1px solid #ccc; padding: 4px 6px; }
    </style>
</head>
<body>
    @php
        $maxColumns = !empty($data) ? max(array_map('count', $data)) : 0;
    @endphp

    @if(empty($data))
        <p>Không có dữ liệu.</p>
    @else
        <table>
            <tbody>
                @foreach($data as $row)
                    <tr>
                        @for($i = 0; $i < $maxColumns; $i++)
                            <td>{{ $row[$i] ?? '' }}</td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
