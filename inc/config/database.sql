-- Create database if not exists
CREATE DATABASE IF NOT EXISTS allied_steel_dms;
USE allied_steel_dms;

-- User roles table
CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

-- Insert default roles
INSERT INTO roles (role_name) VALUES 
('Admin'), 
('Manager'), 
('Salesperson'), 
('Inventory Manager');

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    role_id INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    profile_picture VARCHAR(255) DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    total_logins INT DEFAULT 0,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Insert default admin user (password: admin3535)
INSERT INTO users (username, password, email, full_name, role_id) VALUES 
('Admin35', '$2y$10$bKs0cfd5mbV/tMjbvJP6cObn4p6Nz/3QrhnSX0QfcGKtxEWF8H0Ey', 'admin35@gmail.com', 'System Administrator', 1);

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    zip_code VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Vendors table
CREATE TABLE IF NOT EXISTS vendors (
    vendor_id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    zip_code VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory categories
CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory items table
CREATE TABLE IF NOT EXISTS inventory (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    item_number VARCHAR(50) NOT NULL UNIQUE,
    item_name VARCHAR(150) NOT NULL,
    category_id INT NOT NULL,
    description TEXT,
    unit_of_measure VARCHAR(20) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    customer_price DECIMAL(10,2) DEFAULT NULL,
    current_stock DECIMAL(10,2) DEFAULT 0,
    minimum_stock DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    is_featured TINYINT(1) DEFAULT 0,
    show_on_website TINYINT(1) DEFAULT 1,
    seo_title VARCHAR(200) DEFAULT NULL,
    seo_description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- Media Table for inventory items 
CREATE TABLE IF NOT EXISTS media (
    media_id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory(item_id)
);

-- Stock Logs Table
CREATE TABLE IF NOT EXISTS stock_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    type ENUM('addition', 'reduction') NOT NULL,
    reason VARCHAR(255) NOT NULL,
    reference_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory(item_id)
);

-- Raw Materials Table (for products purchased from vendors)
CREATE TABLE IF NOT EXISTS raw_materials (
    material_id INT PRIMARY KEY AUTO_INCREMENT,
    material_code VARCHAR(50) NOT NULL UNIQUE,
    material_name VARCHAR(150) NOT NULL,
    description TEXT,
    unit_of_measure VARCHAR(20) NOT NULL,
    current_stock DECIMAL(10,2) DEFAULT 0,
    minimum_stock DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Raw Material Stock Logs Table
CREATE TABLE IF NOT EXISTS raw_material_stock_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    material_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    type ENUM('addition', 'reduction') NOT NULL,
    reason VARCHAR(255) NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Purchases Table
CREATE TABLE IF NOT EXISTS purchases (
    purchase_id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_number VARCHAR(50) NOT NULL UNIQUE,
    vendor_id INT NOT NULL,
    purchase_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    delivery_status ENUM('pending', 'in_transit', 'delivered', 'delayed') DEFAULT 'pending',
    expected_delivery DATE DEFAULT NULL,
    invoice_file VARCHAR(255) DEFAULT NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Add index for reporting & filtering
CREATE INDEX idx_vendor_date ON purchases(vendor_id, purchase_date);

-- Purchase Details Table
CREATE TABLE IF NOT EXISTS purchase_details (
    purchase_detail_id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_id INT NOT NULL,
    material_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) DEFAULT 0,
    total_price DECIMAL(12,2) NOT NULL,
    
    FOREIGN KEY (purchase_id) REFERENCES purchases(purchase_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE
);

-- Password reset table
CREATE TABLE IF NOT EXISTS password_resets (
    reset_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Export History Table
CREATE TABLE IF NOT EXISTS export_history (
    export_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    export_type VARCHAR(50) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT DEFAULT 0,
    filters_applied JSON DEFAULT NULL,
    export_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Customer Users Table (separate from admin users)
CREATE TABLE IF NOT EXISTS customer_users (
    customer_user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    zip_code VARCHAR(20) DEFAULT NULL,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    email_verified TINYINT(1) DEFAULT 0,
    profile_picture VARCHAR(255) DEFAULT NULL,
    admin_customer_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL,
    FOREIGN KEY (admin_customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL
);

-- Shopping Cart Table
CREATE TABLE IF NOT EXISTS cart (
    cart_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_user_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_user_id) REFERENCES customer_users(customer_user_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory(item_id) ON DELETE CASCADE,
    UNIQUE KEY unique_customer_item (customer_user_id, item_id)
);

-- Customer Orders Table (moved before sales table)
CREATE TABLE IF NOT EXISTS customer_orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    customer_user_id INT NOT NULL,
    order_date DATETIME NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    final_amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cod') NOT NULL DEFAULT 'cod',
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    tracking_number VARCHAR(50) DEFAULT NULL,
    completion_date DATETIME DEFAULT NULL,
    cancellation_date DATETIME DEFAULT NULL,
    cancellation_reason TEXT DEFAULT NULL,
    shipping_address TEXT NOT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted_admin TINYINT(1) DEFAULT 0,
    is_deleted_customer TINYINT(1) DEFAULT 0,
    FOREIGN KEY (customer_user_id) REFERENCES customer_users(customer_user_id)
);

-- Sales table (now customer_orders is already created)
CREATE TABLE IF NOT EXISTS sales (
    sale_id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    customer_user_id INT DEFAULT NULL,
    sale_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    paid_amount DECIMAL(12,2) DEFAULT 0,
    order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    sale_type ENUM('direct', 'customer_order') NOT NULL DEFAULT 'direct',
    customer_order_id INT DEFAULT NULL,
    tracking_number VARCHAR(50) DEFAULT NULL,
    completion_date DATETIME DEFAULT NULL,
    cancellation_date DATETIME DEFAULT NULL,
    cancellation_reason TEXT DEFAULT NULL,
    invoice_file VARCHAR(255) DEFAULT NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (customer_user_id) REFERENCES customer_users(customer_user_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (customer_order_id) REFERENCES customer_orders(order_id) ON DELETE SET NULL
);

-- Sale details table
CREATE TABLE IF NOT EXISTS sale_details (
    sale_detail_id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory(item_id)
);

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    method VARCHAR(50) NOT NULL, -- e.g., cash, bank_transfer, check, credit_card
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE
);

-- Customer Order Details Table
CREATE TABLE IF NOT EXISTS customer_order_details (
    order_detail_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES customer_orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory(item_id)
);

-- Customer Payments Table
CREATE TABLE IF NOT EXISTS customer_payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    transaction_id VARCHAR(100) DEFAULT NULL,
    payment_method ENUM('cod') NOT NULL DEFAULT 'cod',
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    amount DECIMAL(12,2) NOT NULL,
    payment_date DATETIME DEFAULT NULL,
    gateway_response TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES customer_orders(order_id) ON DELETE CASCADE
);

-- Customer Password Reset Table
CREATE TABLE IF NOT EXISTS customer_password_resets (
    reset_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_user_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_user_id) REFERENCES customer_users(customer_user_id) ON DELETE CASCADE
);

-- Order Status Logs Table
CREATE TABLE IF NOT EXISTS order_status_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    old_status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL,
    new_status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL,
    changed_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES customer_orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id)
);

-- Company Information Table (for landing page content)
CREATE TABLE IF NOT EXISTS company_info (
    info_id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(200) NOT NULL,
    tagline VARCHAR(300) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    mission TEXT DEFAULT NULL,
    vision TEXT DEFAULT NULL,
    about_us TEXT DEFAULT NULL,
    contact_email VARCHAR(100) DEFAULT NULL,
    contact_phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    social_media JSON DEFAULT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    favicon VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default company info
INSERT INTO company_info (company_name, tagline, description, contact_email, contact_phone) VALUES 
('Allied Steel Works', 'Your Trusted Steel Solutions Partner', 'Leading provider of quality steel products and solutions for all your construction and industrial needs.', 'info@alliedsteelworks.com', '+92-XXX-XXXXXXX');

-- Add indexes for better performance
CREATE INDEX idx_customer_orders_date ON customer_orders(order_date);
CREATE INDEX idx_customer_orders_status ON customer_orders(order_status);
CREATE INDEX idx_cart_customer ON cart(customer_user_id);
CREATE INDEX idx_payments_order ON customer_payments(order_id);
CREATE INDEX idx_order_status_logs_order ON order_status_logs(order_id);
CREATE INDEX idx_order_status_logs_changed_by ON order_status_logs(changed_by);
CREATE INDEX idx_order_status_logs_created_at ON order_status_logs(created_at);

-- Customer Email Verifications Table
CREATE TABLE IF NOT EXISTS customer_email_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_user_id) REFERENCES customer_users(customer_user_id) ON DELETE CASCADE
);

-- Triggers
DELIMITER //

-- Update raw materials stock after purchase
DROP TRIGGER IF EXISTS after_purchase_detail_insert//
CREATE TRIGGER after_purchase_detail_insert
AFTER INSERT ON purchase_details
FOR EACH ROW
BEGIN
    UPDATE raw_materials
    SET current_stock = current_stock + NEW.quantity
    WHERE material_id = NEW.material_id;
END //

-- Update inventory stock after sale
DROP TRIGGER IF EXISTS after_sale_detail_insert//
CREATE TRIGGER after_sale_detail_insert
AFTER INSERT ON sale_details
FOR EACH ROW
BEGIN
    UPDATE inventory
    SET current_stock = current_stock - NEW.quantity
    WHERE item_id = NEW.item_id;
END //

-- Trigger to update cart total when quantity changes
DROP TRIGGER IF EXISTS update_cart_total//
CREATE TRIGGER update_cart_total
BEFORE UPDATE ON cart
FOR EACH ROW
BEGIN
    SET NEW.total_price = NEW.quantity * NEW.unit_price;
END //

DROP TRIGGER IF EXISTS insert_cart_total//
CREATE TRIGGER insert_cart_total
BEFORE INSERT ON cart
FOR EACH ROW
BEGIN
    SET NEW.total_price = NEW.quantity * NEW.unit_price;
END //

DELIMITER ;

-- Add reference_id column to existing stock_logs table (migration)
ALTER TABLE stock_logs ADD COLUMN IF NOT EXISTS reference_id INT NULL AFTER reason;

