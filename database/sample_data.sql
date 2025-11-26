-- Sample data for attendance_system database

-- Insert sample departments
INSERT INTO departments (name, description) VALUES
('Human Resources', 'Manages employee relations and company policies'),
('Information Technology', 'Handles all technology and software development'),
('Marketing', 'Responsible for marketing and promotional activities'),
('Finance', 'Manages financial operations and accounting'),
('Operations', 'Oversees daily business operations');

-- Insert sample admin
INSERT INTO admins (name, email, password) VALUES
('Admin User', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

-- Insert sample employees
INSERT INTO employees (department_id, name, email, phone, password, address) VALUES
(1, 'John Doe', 'john.doe@company.com', '123-456-7890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123 Main St, City, State'),
(2, 'Jane Smith', 'jane.smith@company.com', '123-456-7891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '456 Oak Ave, City, State'),
(3, 'Mike Johnson', 'mike.johnson@company.com', '123-456-7892', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '789 Pine Rd, City, State'),
(4, 'Sarah Wilson', 'sarah.wilson@company.com', '123-456-7893', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '321 Elm St, City, State'),
(5, 'David Brown', 'david.brown@company.com', '123-456-7894', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '654 Maple Dr, City, State');

-- Insert sample attendance records
INSERT INTO attendances (employee_id, check_in, check_out, status, notes) VALUES
(1, '2025-08-15 09:00:00', '2025-08-15 17:30:00', 'Present', 'Regular working day'),
(2, '2025-08-15 08:45:00', '2025-08-15 17:15:00', 'Present', 'Early arrival'),
(3, '2025-08-15 09:15:00', '2025-08-15 17:45:00', 'Present', 'Slightly late arrival'),
(4, '2025-08-15 09:00:00', '2025-08-15 17:00:00', 'Present', 'Regular working day'),
(5, '2025-08-15 09:30:00', '2025-08-15 18:00:00', 'Present', 'Late arrival, worked extra'),

(1, '2025-08-16 09:00:00', '2025-08-16 17:30:00', 'Present', 'Regular working day'),
(2, '2025-08-16 08:50:00', '2025-08-16 17:20:00', 'Present', 'Early arrival'),
(3, '2025-08-16 09:10:00', '2025-08-16 17:40:00', 'Present', 'On time'),
(4, '2025-08-16 09:05:00', '2025-08-16 17:05:00', 'Present', 'Regular working day'),
(5, '2025-08-16 09:00:00', '2025-08-16 17:30:00', 'Present', 'Regular working day'),

(1, '2025-08-17 09:00:00', '2025-08-17 17:30:00', 'Present', 'Regular working day'),
(2, '2025-08-17 08:55:00', '2025-08-17 17:25:00', 'Present', 'Early arrival'),
(3, '2025-08-17 09:20:00', '2025-08-17 17:50:00', 'Present', 'Late arrival'),
(4, '2025-08-17 09:00:00', '2025-08-17 17:00:00', 'Present', 'Regular working day'),
(5, '2025-08-17 09:15:00', '2025-08-17 17:45:00', 'Present', 'Slightly late');

