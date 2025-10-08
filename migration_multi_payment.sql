-- Migración para soportar pagos múltiples con un solo voucher
-- Ejecutar este script para actualizar la estructura

-- 1. Agregar campo para agrupar pagos del mismo voucher
ALTER TABLE payments ADD COLUMN voucher_group_id VARCHAR(50) DEFAULT NULL AFTER payment_code;

-- 2. Crear índice para mejorar consultas
CREATE INDEX idx_voucher_group ON payments(voucher_group_id);

-- 3. Agregar campo para indicar si es pago múltiple
ALTER TABLE payments ADD COLUMN is_multi_payment BOOLEAN DEFAULT FALSE AFTER voucher_group_id;

-- 4. Agregar campo para descripción del voucher (opcional)
ALTER TABLE payments ADD COLUMN voucher_description TEXT DEFAULT NULL AFTER is_multi_payment;

-- 5. Actualizar registros existentes
UPDATE payments SET voucher_group_id = payment_code WHERE voucher_group_id IS NULL;

-- 6. Crear vista para consultas de pagos agrupados
CREATE VIEW payment_groups AS
SELECT 
    voucher_group_id,
    student_id,
    semester_id,
    program_type,
    payment_date,
    payment_code,
    voucher_description,
    COUNT(*) as payment_count,
    SUM(amount) as total_amount,
    GROUP_CONCAT(payment_type ORDER BY payment_type SEPARATOR ', ') as payment_types,
    GROUP_CONCAT(amount ORDER BY payment_type SEPARATOR ', ') as amounts,
    created_at
FROM payments 
WHERE voucher_group_id IS NOT NULL
GROUP BY voucher_group_id, student_id, semester_id, program_type, payment_date, payment_code, voucher_description, created_at;
