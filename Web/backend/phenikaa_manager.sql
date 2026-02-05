
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


;
;
;
;




CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `record_id` int(11) NOT NULL,
  `action` varchar(32) NOT NULL,
  `message` varchar(512) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `audit_log` (`id`, `table_name`, `record_id`, `action`, `message`, `created_at`) VALUES
(1, 'monhoc', 1, 'CREATE', 'Create mon GT1 - Calculus', '2025-10-31 10:28:29'),
(2, 'monhoc', 1, 'UPDATE', 'Update mon GT1 - Calculus', '2025-10-31 10:29:28'),
(3, 'monhoc', 1, 'UPDATE', 'Update mon GT2 - Calculus2', '2025-10-31 11:24:14'),
(4, 'monhoc', 1, 'UPDATE', 'Update mon GT2 - Calculus2', '2025-10-31 11:24:18'),
(5, 'monhoc', 1, 'UPDATE', 'Update mon GT1 - Calculus 1', '2025-10-31 11:24:29'),
(6, 'monhoc', 2, 'CREATE', 'Create mon GT2 - Calculus 2', '2025-10-31 11:25:02'),
(7, 'lophoc', 0, 'CREATE', 'Sample text', '2025-11-18 16:15:44'),
(8, 'cautruc_khung', 1, 'INIT', 'Khoi tao cau truc nen', '2025-11-18 17:27:42'),
(9, 'cautruc_khung', 2, 'INIT', 'Khoi tao cau truc nen', '2025-11-18 17:28:07'),
(10, 'cautruc_khung', 1, 'DEACTIVATE', 'Sample text', '2025-11-18 17:37:32'),
(11, 'cautruc_khung', 2, 'UPDATE', 'Update thong tin cau truc', '2025-11-18 17:37:47'),
(12, 'cautruc_khung', 1, 'REACTIVATE', 'Sample text', '2025-11-18 17:41:09'),
(13, 'cautruc_khung', 2, 'UPDATE', 'Update thong tin cau truc', '2025-11-18 17:57:59'),
(14, 'cautruc_khung', 2, 'UPDATE', 'Update thong tin cau truc', '2025-11-18 18:01:37'),
(15, 'cautruc_khung', 1, 'UPDATE', 'Update thong tin cau truc', '2025-11-18 18:01:54'),
(16, 'cautruc_khung', 2, 'UPDATE', 'Update thong tin cau truc', '2025-11-18 18:02:53'),
(17, 'nganh', 5, 'CREATE', 'Sample text', '2025-11-18 18:50:20'),
(18, 'nganh', 5, 'UPDATE', 'Update thong tin Majors', '2025-11-18 18:50:30'),
(19, 'lophoc', 0, 'CREATE', 'Sample text', '2025-11-19 16:42:24'),
(20, 'monhoc', 3, 'CREATE', 'Create mon YCPM - Yeu phan mem', '2025-11-20 15:34:12'),
(21, 'monhoc', 3, 'UPDATE', 'Update mon YCPm1 - Yeu phan mem', '2025-11-20 15:34:31'),
(22, 'monhoc', 3, 'DELETE', 'Delete mon', '2025-11-20 15:34:38'),
(23, 'lophoc', 0, 'CREATE', 'Sample text', '2025-11-20 15:38:17'),
(24, 'cautruc_khung', 3, 'INIT', 'Khoi tao cau truc nen', '2025-11-20 15:40:10'),
(25, 'cautruc_khung', 4, 'INIT', 'Khoi tao cau truc nen', '2025-11-20 15:40:44'),
(26, 'cautruc_khung', 3, 'UPDATE', 'Update thong tin cau truc', '2025-11-20 15:41:18');



CREATE TABLE `cautruc_khung` (
  `id` int(11) NOT NULL,
  `tenCauTruc` varchar(255) NOT NULL,
  `nganh_id` int(11) NOT NULL,
  `bacDaoTao` varchar(50) NOT NULL,
  `khoaTuyen` varchar(20) NOT NULL,
  `moTa` text DEFAULT NULL,
  `quyTacTinChi` int(11) DEFAULT 0,
  `khoiKienThuc` text DEFAULT NULL,
  `trangThai` varchar(20) DEFAULT 'Sample text',
  `nguoiTao` varchar(50) DEFAULT NULL,
  `ngayTao` datetime DEFAULT current_timestamp(),
  `ngayCapNhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `cautruc_khung` (`id`, `tenCauTruc`, `nganh_id`, `bacDaoTao`, `khoaTuyen`, `moTa`, `quyTacTinChi`, `khoiKienThuc`, `trangThai`, `nguoiTao`, `ngayTao`, `ngayCapNhat`) VALUES
(3, 'CNTT1', 3, 'ai hoc', 'k18', '', 120, 'Sample text', 'Sample text', 'admin', '2025-11-20 22:40:10', '2025-11-20 22:41:18'),
(4, 'CNTT', 3, 'Sample text', 'K18', '', 120, 'Sample text', 'Sample text', 'admin', '2025-11-20 22:40:44', '2025-11-20 22:40:44');



CREATE TABLE `ctdt` (
  `id` int(11) NOT NULL,
  `maCTDT` varchar(50) NOT NULL,
  `tenCTDT` varchar(255) NOT NULL,
  `moTa` text DEFAULT NULL,
  `nganh_id` int(11) DEFAULT 0,
  `namBatDau` int(11) DEFAULT 2025,
  `tongTinChi` int(11) DEFAULT 0,
  `giaTinChi` decimal(15,0) DEFAULT 0,
  `cautruc_id` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `ctdt` (`id`, `maCTDT`, `tenCTDT`, `moTa`, `nganh_id`, `namBatDau`, `tongTinChi`, `giaTinChi`, `cautruc_id`) VALUES
(7, 'CNTT', 'Dh', NULL, 3, 2025, 120, 900000, 0);



CREATE TABLE `giangvien` (
  `id` int(11) NOT NULL,
  `maGV` varchar(50) NOT NULL,
  `hoTen` varchar(255) NOT NULL,
  `ngaySinh` date DEFAULT NULL,
  `gioiTinh` enum('Nam','Female','Other') DEFAULT 'Other',
  `hocHam` varchar(100) DEFAULT NULL,
  `hocVi` varchar(100) DEFAULT NULL,
  `khoa_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `dienThoai` varchar(20) DEFAULT NULL,
  `diaChi` varchar(255) DEFAULT NULL,
  `trangThai` enum('Active','Sample text','Sample text') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `giangvien` (`id`, `maGV`, `hoTen`, `ngaySinh`, `gioiTinh`, `hocHam`, `hocVi`, `khoa_id`, `email`, `dienThoai`, `diaChi`, `trangThai`) VALUES
(3, '2390301', 'Sample text', '2005-02-09', 'Nam', 'GS', 'TS', 18, 'phucvuhong0902@gmail.com', '+84367699347', 'Sample text', 'Active'),
(6, '118', 'Sample text', '0000-00-00', 'Nam', 'Sample text', 'THS', 18, '5@gmai.com', '+84367699347', '', 'Sample text'),
(7, '113', 'Sample text', '2005-02-09', 'Nam', 'GS', 'TS', 18, '3@gmai.com', '+84367699347', 'HN', 'Active');



CREATE TABLE `giaodich_sinhvien` (
  `id` int(11) NOT NULL,
  `maSV` varchar(50) NOT NULL,
  `loai` varchar(20) NOT NULL,
  `soPhieu` varchar(50) DEFAULT NULL,
  `noiDung` text DEFAULT NULL,
  `soTien` decimal(15,0) DEFAULT 0,
  `ngayTao` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `giaodich_sinhvien` (`id`, `maSV`, `loai`, `soPhieu`, `noiDung`, `soTien`, `ngayTao`) VALUES
(1, 'sv001', 'PHIEU_THU', 'PT1763653545', 'Sample text', 2700000, '2025-11-20');



CREATE TABLE `khoa` (
  `id` int(11) NOT NULL,
  `maKhoa` varchar(50) NOT NULL,
  `tenKhoa` varchar(255) NOT NULL,
  `truong` varchar(255) NOT NULL,
  `ngayThanhLap` date NOT NULL,
  `trangThai` enum('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `khoa` (`id`, `maKhoa`, `tenKhoa`, `truong`, `ngayThanhLap`, `trangThai`) VALUES
(18, 'ITC2', 'CNTT', '', '2222-02-22', 'Active'),
(19, 'QTKD1', 'Sample text', '', '1111-11-11', 'Active'),
(20, 'DL25', 'Sample text', '', '2005-02-09', 'Inactive'),
(23, 'DLT', 'Sample text', '', '2005-02-09', 'Active');



CREATE TABLE `khoan_phai_thu` (
  `id` int(11) NOT NULL,
  `maSV` varchar(50) DEFAULT NULL,
  `noiDung` varchar(255) DEFAULT NULL,
  `soTien` decimal(15,0) DEFAULT 0,
  `hanNop` date DEFAULT NULL,
  `trangThai` varchar(20) DEFAULT 'CHUA_NOP'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `khoan_phai_thu` (`id`, `maSV`, `noiDung`, `soTien`, `hanNop`, `trangThai`) VALUES
(3, 'sv001', 'Hoc phi: Calculus 1 (3 tin chi)', 2700000, '2025-12-20', 'DA_NOP');



CREATE TABLE `khoan_thu` (
  `id` int(11) NOT NULL,
  `maSV` varchar(30) NOT NULL,
  `monhoc_id` int(11) DEFAULT NULL,
  `tenKhoan` varchar(255) NOT NULL,
  `soTien` bigint(20) NOT NULL,
  `daNop` bigint(20) NOT NULL DEFAULT 0,
  `hanNop` date DEFAULT NULL,
  `trangThai` enum('CHUA_NOP','DA_NOP','NOP_MOT_PHAN') NOT NULL DEFAULT 'CHUA_NOP',
  `ghiChu` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `khoan_thu` (`id`, `maSV`, `monhoc_id`, `tenKhoan`, `soTien`, `daNop`, `hanNop`, `trangThai`, `ghiChu`) VALUES
(3, 'SV001', 1, 'Hoc phi mon Lap trinh C', 3500000, 0, '2025-09-01', 'CHUA_NOP', ''),
(4, 'SV001', 2, 'Hoc phi mon Lap trinh C', 3500000, 0, '2025-09-01', 'CHUA_NOP', '');



CREATE TABLE `khoikienthuc` (
  `id` int(11) NOT NULL,
  `maKKT` varchar(50) NOT NULL,
  `tenKKT` varchar(255) NOT NULL,
  `moTa` text DEFAULT NULL,
  `khoa_id` int(11) DEFAULT NULL,
  `tinChiMin` int(11) DEFAULT 0,
  `tinChiMax` int(11) DEFAULT 0,
  `loai` varchar(100) DEFAULT 'Sample text',
  `trangThai` varchar(50) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `khoikienthuc` (`id`, `maKKT`, `tenKKT`, `moTa`, `khoa_id`, `tinChiMin`, `tinChiMax`, `loai`, `trangThai`) VALUES
(9, 'KKT0011', 'Sample text', NULL, 18, 3, 6, 'Sample text', 'Active');



CREATE TABLE `lophoc` (
  `id` int(11) NOT NULL,
  `maLop` varchar(50) NOT NULL,
  `monhoc_id` int(11) NOT NULL,
  `giangvien_id` int(11) NOT NULL,
  `phongHoc` varchar(50) NOT NULL,
  `thu` int(11) NOT NULL,
  `tietBatDau` int(11) NOT NULL,
  `soTiet` int(11) NOT NULL,
  `siSoToiDa` int(11) DEFAULT 60,
  `siSoThucTe` int(11) DEFAULT 0,
  `phuongThuc` varchar(20) DEFAULT 'Offline',
  `hocKy` varchar(20) DEFAULT '2025-1',
  `trangThai` varchar(20) DEFAULT 'DangMo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `lophoc` (`id`, `maLop`, `monhoc_id`, `giangvien_id`, `phongHoc`, `thu`, `tietBatDau`, `soTiet`, `siSoToiDa`, `siSoThucTe`, `phuongThuc`, `hocKy`, `trangThai`) VALUES
(2, 'KTPM2', 2, 6, 'A2-301', 3, 1, 3, 60, 0, 'Offline', '2025-1', 'DangMo'),
(3, 'GT1', 1, 3, 'A3-107', 3, 1, 5, 60, 0, 'Offline', '2025-1', 'DangMo');



CREATE TABLE `monhoc` (
  `id` int(11) NOT NULL,
  `maMon` varchar(50) NOT NULL,
  `tenMon` varchar(255) NOT NULL,
  `soTinChi` tinyint(4) NOT NULL,
  `donvi_id` int(11) NOT NULL,
  `moTa` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hocPhi` int(11) NOT NULL DEFAULT 0
) ;


INSERT INTO `monhoc` (`id`, `maMon`, `tenMon`, `soTinChi`, `donvi_id`, `moTa`, `created_at`, `hocPhi`) VALUES
(1, 'GT1', 'Sample text', 3, 18, '', '2025-10-31 10:28:29', 0),
(2, 'GT2', 'Sample text', 3, 18, '', '2025-10-31 11:25:02', 0);



CREATE TABLE `monhoc_quanhe` (
  `id` int(11) NOT NULL,
  `mon_id` int(11) NOT NULL,
  `loai` enum('TIEN_QUYET','PHU_THUOC') NOT NULL,
  `lien_quan_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `monhoc_quanhe` (`id`, `mon_id`, `loai`, `lien_quan_id`) VALUES
(1, 2, 'TIEN_QUYET', 1),
(2, 2, 'PHU_THUOC', 1);



CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `date` date NOT NULL,
  `category` enum('phongdambao','phongdaotao','khac') DEFAULT 'khac',
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `news` (`id`, `title`, `content`, `date`, `category`, `image`) VALUES
(5, 'Sample text', 'Phenikaa University announces enrollment for the 2025 regular university program with several new admission methods....', '2025-02-15', 'phongdaotao', 'https://phenikaa-uni.edu.vn/img/share_facebook_2.jpg'),
(6, 'Sample text', 'Congratulations to the new graduates and engineers for successfully completing their studies at the university....', '2025-01-20', 'phongdaotao', 'https://images.unsplash.com/photo-1627556704290-2b1f5853ff78?w=600&q=80'),
(7, 'Sample text', 'Sample text', '2024-12-10', '', 'https://images.unsplash.com/photo-1544531696-44a427069e39?w=600&q=80');



CREATE TABLE `nganh` (
  `id` int(11) NOT NULL,
  `maNganh` varchar(50) NOT NULL,
  `tenNganh` varchar(255) NOT NULL,
  `khoa_id` int(11) NOT NULL,
  `bacDaoTao` enum('Sample text','ai hoc','Sample text','Sample text','Other') NOT NULL DEFAULT 'ai hoc',
  `trangThai` varchar(50) NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `nganh` (`id`, `maNganh`, `tenNganh`, `khoa_id`, `bacDaoTao`, `trangThai`) VALUES
(3, 'HTTP', 'CNTT', 18, 'ai hoc', 'Active'),
(4, 'QTDL25', 'Sample text', 20, 'ai hoc', 'Active'),
(5, 'QTKD', 'Sample text', 19, 'ai hoc', 'Active');



CREATE TABLE `taichinh_sinhvien` (
  `id` int(11) NOT NULL,
  `maSV` varchar(50) NOT NULL,
  `phaiNop` decimal(15,0) DEFAULT 0,
  `mienGiam` decimal(15,0) DEFAULT 0,
  `daNop` decimal(15,0) DEFAULT 0,
  `kyHoc` varchar(20) DEFAULT '2024-1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `taichinh_sinhvien` (`id`, `maSV`, `phaiNop`, `mienGiam`, `daNop`, `kyHoc`) VALUES
(1, 'sv001', 0, 0, 0, '2024-1');



CREATE TABLE `thanh_toan` (
  `id` int(11) NOT NULL,
  `khoanThu_id` int(11) NOT NULL,
  `maSV` varchar(30) NOT NULL,
  `soTien` bigint(20) NOT NULL,
  `phuongThuc` enum('TienMat','ChuyenKhoan','The') NOT NULL DEFAULT 'ChuyenKhoan',
  `trangThaiGD` enum('THANH_CONG','THAT_BAI') NOT NULL DEFAULT 'THANH_CONG',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ghiChu` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role` enum('admin','editor','viewer') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `maSV` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `is_active`, `created_at`, `maSV`) VALUES
(1, 'admin', '$2y$10$fdw.DQvP4LUyAt1NJOVHHe8yY7aGedXUWgxPMqpsKTPjn/L4KHjMq', 'Sample text', 'admin', 1, '2025-11-02 14:06:53', NULL),
(2, 'sv001', '$2y$10$or10FEgTDhj1xrU4nyxHG.qqw9gVxmTkwBMXT53jJF7NvTuZMEgrK', 'Nguyen Van A', 'viewer', 1, '2025-11-02 14:30:27', 'SV001');


ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `cautruc_khung`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nganh_id` (`nganh_id`);

ALTER TABLE `ctdt`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `giangvien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_gv_ma` (`maGV`),
  ADD UNIQUE KEY `uk_gv_email` (`email`),
  ADD KEY `fk_gv_khoa` (`khoa_id`);

ALTER TABLE `giaodich_sinhvien`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `khoa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_khoa_maKhoa` (`maKhoa`);

ALTER TABLE `khoan_phai_thu`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `khoan_thu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maSV` (`maSV`),
  ADD KEY `trangThai` (`trangThai`),
  ADD KEY `hanNop` (`hanNop`),
  ADD KEY `fk_khoan_thu_monhoc` (`monhoc_id`);

ALTER TABLE `khoikienthuc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `khoa_id` (`khoa_id`);

ALTER TABLE `lophoc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `maLop` (`maLop`),
  ADD KEY `monhoc_id` (`monhoc_id`),
  ADD KEY `giangvien_id` (`giangvien_id`);

ALTER TABLE `monhoc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `maMon` (`maMon`),
  ADD KEY `fk_monhoc_donvi` (`donvi_id`);

ALTER TABLE `monhoc_quanhe`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_qh` (`mon_id`,`loai`,`lien_quan_id`),
  ADD KEY `fk_qh_lienmon` (`lien_quan_id`);

ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `nganh`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_nganh_ma_khoa` (`khoa_id`,`maNganh`),
  ADD UNIQUE KEY `uk_nganh_ten_khoa` (`khoa_id`,`tenNganh`);

ALTER TABLE `taichinh_sinhvien`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `thanh_toan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tt_khoanthu` (`khoanThu_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);


ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

ALTER TABLE `cautruc_khung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `ctdt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `giangvien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `giaodich_sinhvien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `khoa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

ALTER TABLE `khoan_phai_thu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `khoan_thu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `khoikienthuc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

ALTER TABLE `lophoc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `monhoc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `monhoc_quanhe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `nganh`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `taichinh_sinhvien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `thanh_toan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;


ALTER TABLE `cautruc_khung`
  ADD CONSTRAINT `cautruc_khung_ibfk_1` FOREIGN KEY (`nganh_id`) REFERENCES `nganh` (`id`);

ALTER TABLE `giangvien`
  ADD CONSTRAINT `fk_gv_khoa` FOREIGN KEY (`khoa_id`) REFERENCES `khoa` (`id`);

ALTER TABLE `khoan_thu`
  ADD CONSTRAINT `fk_khoan_thu_monhoc` FOREIGN KEY (`monhoc_id`) REFERENCES `monhoc` (`id`);

ALTER TABLE `khoikienthuc`
  ADD CONSTRAINT `khoikienthuc_ibfk_1` FOREIGN KEY (`khoa_id`) REFERENCES `khoa` (`id`);

ALTER TABLE `lophoc`
  ADD CONSTRAINT `lophoc_ibfk_1` FOREIGN KEY (`monhoc_id`) REFERENCES `monhoc` (`id`),
  ADD CONSTRAINT `lophoc_ibfk_2` FOREIGN KEY (`giangvien_id`) REFERENCES `giangvien` (`id`);

ALTER TABLE `monhoc`
  ADD CONSTRAINT `fk_monhoc_donvi` FOREIGN KEY (`donvi_id`) REFERENCES `khoa` (`id`);

ALTER TABLE `monhoc_quanhe`
  ADD CONSTRAINT `fk_qh_lienmon` FOREIGN KEY (`lien_quan_id`) REFERENCES `monhoc` (`id`),
  ADD CONSTRAINT `fk_qh_mon` FOREIGN KEY (`mon_id`) REFERENCES `monhoc` (`id`) ON DELETE CASCADE;

ALTER TABLE `nganh`
  ADD CONSTRAINT `fk_nganh_khoa` FOREIGN KEY (`khoa_id`) REFERENCES `khoa` (`id`);

ALTER TABLE `thanh_toan`
  ADD CONSTRAINT `fk_tt_khoanthu` FOREIGN KEY (`khoanThu_id`) REFERENCES `khoan_thu` (`id`) ON DELETE CASCADE;
COMMIT;

;
;
;