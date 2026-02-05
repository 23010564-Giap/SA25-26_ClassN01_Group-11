CREATE TABLE IF NOT EXISTS khoa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  maKhoa VARCHAR(50) NOT NULL,
  tenKhoa VARCHAR(255) NOT NULL,
  truong VARCHAR(255) NOT NULL,
  ngayThanhLap DATE NOT NULL,
  trangThai ENUM('Active', 'Inactive') DEFAULT 'Active',
  CONSTRAINT uc_maKhoa UNIQUE (maKhoa)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS nganh (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenNganh VARCHAR(255) NOT NULL,
  khoa_id INT NOT NULL,
  CONSTRAINT fk_nganh_khoa FOREIGN KEY (khoa_id) REFERENCES khoa(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS giangvien (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenGiangVien VARCHAR(255) NOT NULL,
  khoa_id INT NOT NULL,
  CONSTRAINT fk_gv_khoa FOREIGN KEY (khoa_id) REFERENCES khoa(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS news (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  date DATE NOT NULL,
  category ENUM('phongdambao','phongdaotao','khac') DEFAULT 'khac'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO news(title, content, date, category) VALUES
('Sample text','Sample text', CURDATE(),'phongdambao'),
('Sample text','Sample text', CURDATE(),'phongdaotao');