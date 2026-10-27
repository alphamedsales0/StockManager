-- ============================================================
-- 1. TABLE permissions
-- ============================================================
CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  key_name VARCHAR(100) NOT NULL UNIQUE,
  display_name VARCHAR(150) NOT NULL,
  category VARCHAR(50) NOT NULL,
  description TEXT NULL,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. TABLE role_permissions (pivot)
-- ============================================================
CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Insérer les permissions par défaut
-- ============================================================
INSERT INTO permissions (key_name, display_name, category, sort_order) VALUES
('dashboard',           'Dashboard ansehen',        'Dashboard',    1),
('tickets',             'Tickets ansehen',          'Tickets',      10),
('tickets-write',       'Tickets bearbeiten',       'Tickets',      11),
('tickets-delete',      'Tickets löschen',          'Tickets',      12),
('products',            'Produkte ansehen',         'Produkte',     20),
('products-write',      'Produkte bearbeiten',      'Produkte',     21),
('products-delete',     'Produkte löschen',         'Produkte',     22),
('products-categories', 'Kategorien verwalten',     'Produkte',     23),
('customers',           'Kunden ansehen',           'Kunden',       30),
('customers-write',     'Kunden bearbeiten',        'Kunden',       31),
('customers-delete',    'Kunden löschen',           'Kunden',       32),
('employees',           'Mitarbeiter ansehen',      'Mitarbeiter',  40),
('employees-write',     'Mitarbeiter bearbeiten',   'Mitarbeiter',  41),
('employees-delete',    'Mitarbeiter löschen',      'Mitarbeiter',  42),
('orders',              'Bestellungen ansehen',     'Bestellungen', 50),
('orders-write',        'Bestellungen bearbeiten',  'Bestellungen', 51),
('orders-delete',       'Bestellungen löschen',     'Bestellungen', 52),
('stats',               'Statistiken ansehen',      'Statistiken',  60),
('help',                'Hilfe ansehen',            'Allgemein',    70),
('settings',            'Einstellungen verwalten',  'System',       80),
('roles',               'Rollen verwalten',         'System',       81),
('users',               'Benutzer verwalten',       'System',       82)
ON DUPLICATE KEY UPDATE
  display_name = VALUES(display_name),
  category = VALUES(category),
  sort_order = VALUES(sort_order);

-- ============================================================
-- 4. Admin reçoit TOUTES les permissions
-- ============================================================
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name = 'admin'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- ============================================================
-- 5. Permissions par défaut par rôle système
-- ============================================================

-- Manager : tout sauf roles + users
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name = 'manager'
  AND p.key_name NOT IN ('roles', 'users')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Techniker : lecture seule
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name = 'technician'
  AND p.key_name IN ('dashboard', 'tickets', 'tickets-write', 'products', 'customers', 'help')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Office / Mitarbeiter : lecture + quelques écritures
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name IN ('employee', 'sales', 'support')
  AND p.key_name IN ('dashboard', 'tickets', 'tickets-write', 'products', 'customers', 'customers-write', 'orders', 'help')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Warehouse : produits + orders
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name = 'warehouse'
  AND p.key_name IN ('dashboard', 'products', 'products-write', 'orders', 'orders-write', 'help')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Purchasing : produits + orders + fournisseurs
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name = 'purchasing'
  AND p.key_name IN ('dashboard', 'products', 'orders', 'orders-write', 'help')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- HR : employees
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name = 'hr'
  AND p.key_name IN ('dashboard', 'employees', 'employees-write', 'help')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Finance / Accountant : stats + orders
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name IN ('finance', 'accountant')
  AND p.key_name IN ('dashboard', 'orders', 'stats', 'help')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Developer : tout sauf users + roles
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name = 'developer'
  AND p.key_name NOT IN ('roles', 'users')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Teamlead : équipe + lecture large
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name = 'teamlead'
  AND p.key_name IN ('dashboard', 'tickets', 'tickets-write', 'products', 'customers', 'employees', 'orders', 'stats', 'help')
ON DUPLICATE KEY UPDATE role_id = role_id;
