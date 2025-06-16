CREATE DATABASE IF NOT EXISTS stariks DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE stariks;


CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_id INT NOT NULL,
    type ENUM('income','expense') NOT NULL,
    label VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY u_user_card_label (user_id,card_id,type,label)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    happened_on DATE NOT NULL,
    note VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX (happened_on),
    INDEX (card_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS credit_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    last4 CHAR(4) NOT NULL,
    balance_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY u_user_last4 (user_id,last4)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS investments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_id INT NOT NULL,
    ticker VARCHAR(12) NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('stocks','bonds','crypto','funds','realestate','other') NOT NULL DEFAULT 'stocks',
    invested_amount DECIMAL(12,2) NOT NULL,
    quantity DECIMAL(12,4) NOT NULL,
    current_value DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES credit_cards(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS credit_card_loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    description VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    monthly_payment DECIMAL(12,2) NOT NULL,
    paid_off_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES credit_cards(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_id INT NOT NULL,
    category_id INT NOT NULL,
    period CHAR(7) NOT NULL, -- YYYY-MM
    limit_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY u_user_card_cat_period (user_id, card_id, category_id, period)
) ENGINE=InnoDB;

INSERT INTO categories (user_id,card_id,type,label) VALUES 
 (1,1,'expense','Ēdiens'),
 (1,1,'expense','Transports'),
 (1,1,'income','Alga'),
 (1,1,'income','Pārdošana');
