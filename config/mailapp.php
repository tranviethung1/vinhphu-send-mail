<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Cấu hình riêng cho hệ thống gửi mail
     |--------------------------------------------------------------------------
     |
     | ID cơ sở được coi là "cơ sở cha" (Tổng công ty).
     | Dùng để hiển thị phân biệt cơ sở cha / cơ sở con trên giao diện.
     |
     | Thiết lập trong file .env:
     | HEAD_OFFICE_FACILITY_ID=1
     |
     */
    'head_office_facility_id' => env('HEAD_OFFICE_FACILITY_ID'),
];

