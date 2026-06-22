# Hướng dẫn Migration - Tách Sheet "Bảng lương"

## Tổng quan

Từ phiên bản này, hệ thống sẽ tự động tách sheet "Bảng lương" từ file Excel và lưu riêng để tối ưu hiệu suất xử lý.

## Thay đổi

### 1. Database Schema
Đã thêm 3 trường mới vào bảng `salary_files`:
- `salary_sheet_file_name` - Tên file sheet "Bảng lương" đã tách
- `salary_sheet_path` - Đường dẫn đến file sheet đã tách
- `salary_sheet_size` - Kích thước file sheet

### 2. Chức năng Upload/Update
- **File gốc**: Vẫn được lưu đầy đủ trong `file_path`
- **File sheet**: Sheet "Bảng lương" được tách riêng và lưu trong `salary_sheet_path`
- **Xử lý**: Các thao tác đọc dữ liệu, xuất PDF sẽ ưu tiên sử dụng file sheet (nhanh hơn)

### 3. Lợi ích
✅ Giữ nguyên file gốc để tra cứu/backup  
✅ Tách sheet "Bảng lương" để xử lý nhanh hơn  
✅ Giảm thời gian đọc file khi làm việc với file Excel lớn  
✅ Tự động fallback về file gốc nếu file sheet không tồn tại  

## Cách chạy Migration

Khi database đã kết nối, chạy lệnh sau:

```bash
cd /home/hungtv/vinhphu/mail-app
php artisan migrate
```

Migration sẽ thêm các trường mới vào bảng `salary_files`.

## Kiểm tra sau khi Migration

1. **Upload file mới**: Truy cập `/salary-files/create` và upload file Excel
   - Kiểm tra log để xác nhận sheet "Bảng lương" đã được tách thành công
   - File gốc và file sheet đều được lưu trong `storage/app/salary-files/`

2. **Kiểm tra file cũ**: Các file đã upload trước đây vẫn hoạt động bình thường
   - Hệ thống tự động dùng `file_path` nếu `salary_sheet_path` là null

3. **Xuất PDF**: Thử xuất PDF từ một file lương để đảm bảo mọi thứ hoạt động

## Xử lý Lỗi

### Lỗi: "Không tìm thấy sheet có tên 'Bảng lương'"
**Nguyên nhân**: File Excel không có sheet tên "Bảng lương" hoặc "Bảng Lương"  
**Giải pháp**: Đảm bảo file Excel có sheet với tên chính xác này

### Lỗi khi upload: "Có lỗi khi xử lý file Excel"
**Nguyên nhân**: File Excel bị lỗi hoặc không đọc được  
**Giải pháp**: 
1. Kiểm tra log trong `storage/logs/laravel.log`
2. Thử mở file bằng Excel để đảm bảo file không bị lỗi
3. Lưu lại file Excel (Save As) và thử upload lại

## Rollback (Nếu cần)

Để rollback migration:

```bash
php artisan migrate:rollback --step=1
```

Lưu ý: Điều này chỉ xóa các trường mới, không ảnh hưởng đến dữ liệu đã lưu.

## Cấu trúc File

```
storage/app/salary-files/
├── 1705820000_file_goc.xlsx          # File gốc đầy đủ
├── 1705820000_file_goc_bangluong.xlsx # Sheet "Bảng lương" đã tách
├── 1705820100_khac.xlsx
└── 1705820100_khac_bangluong.xlsx
```

## Kiểm tra Log

Sau khi upload, kiểm tra log để xác nhận:

```bash
tail -f storage/logs/laravel.log | grep "Bảng lương"
```

Log thành công sẽ hiển thị:
```
[INFO] Đã tách sheet "Bảng lương" thành công
[INFO] ✅ Đọc sheet 'Bảng Lương' thành công
```

## Hỗ trợ

Nếu có vấn đề, kiểm tra:
1. Log file: `storage/logs/laravel.log`
2. Quyền thư mục: `storage/app/salary-files/` phải có quyền ghi
3. Extension PHP: Đảm bảo có `php-zip` và `php-xml`
