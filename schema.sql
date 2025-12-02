-- schema.sql

-- Table for rooms (Salas)
CREATE TABLE IF NOT EXISTS res_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    capacity INT NOT NULL,
    description TEXT,
    image_url VARCHAR(255)
);

-- Table for predefined schedules (Horarios de clases)
CREATE TABLE IF NOT EXISTS res_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    day_of_week ENUM('Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    teacher_name VARCHAR(100),
    FOREIGN KEY (room_id) REFERENCES res_rooms(id) ON DELETE CASCADE
);

-- Table for bookings (Reservas)
CREATE TABLE IF NOT EXISTS res_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    user_email VARCHAR(100) NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES res_rooms(id) ON DELETE CASCADE
);

-- Table for admin users
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert some sample rooms
INSERT INTO res_rooms (name, capacity, description) VALUES 
('Sala de Conferencias', 50, 'Sala equipada con proyector y sistema de sonido.'),
('Laboratorio de Computación', 30, 'Equipos de última generación.');
