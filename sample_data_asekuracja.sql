-- Przykładowe dane dla modułu Asekuracja
-- Wykonaj te polecenia w bazie danych aby załadować przykładowe dane

-- Sprzęt asekuracyjny (14 elementów)
INSERT INTO asekuracyjny_equipment 
(inventory_number, name, description, equipment_type, manufacturer, model, serial_number, 
manufacturing_date, purchase_date, purchase_price, supplier, invoice_number, warranty_expiry, 
next_review_date, review_interval_months, status, location, notes, created_by_id, created_at, updated_at) 
VALUES 
('ASK-001-2024', 'Szelki robocze Petzl AVAO', 'Szelki całego ciała do prac na wysokości, z regulacją w 4 punktach', 'harness', 'Petzl', 'AVAO BOD', 'C071BA001234', 
'2023-08-15', '2024-01-15', 450.00, 'TechClimb Sp. z o.o.', 'FV/2024/001', '2026-01-15', 
'2025-01-15', 12, 'available', 'Magazyn A, Szafa nr 1', 'Nowy sprzęt, pierwszy przegląd po roku', 1, NOW(), NOW()),

('ASK-002-2024', 'Lina dynamiczna Edelrid Boa', 'Lina wspinaczkowa 9.8mm, 70m, certyfikat CE', 'rope', 'Edelrid', 'Boa 9.8mm', 'ED2023B9870', 
'2023-05-20', '2024-02-10', 280.00, 'Alpine Sport', 'AS/2024/045', '2026-02-10', 
'2025-02-10', 12, 'available', 'Magazyn A, Szafa nr 2', 'Regularnie sprawdzać na uszkodzenia', 1, NOW(), NOW()),

('ASK-003-2024', 'Kask Black Diamond Vector', 'Kask wspinaczkowy z regulacją, wentylowany', 'helmet', 'Black Diamond', 'Vector', 'BD2023V5678', 
'2023-09-10', '2024-01-20', 180.00, 'Góralski Sklep', 'GS/2024/12', '2026-01-20', 
'2025-01-20', 12, 'available', 'Magazyn B, Półka górna', 'Sprawdzać mocowania przed użyciem', 1, NOW(), NOW()),

('ASK-004-2024', 'Zacisk Petzl Ascension', 'Zacisk prawy do technik linowych', 'ascender', 'Petzl', 'Ascension', 'C310AA567890', 
'2023-11-01', '2024-03-05', 95.00, 'TechClimb Sp. z o.o.', 'FV/2024/089', '2026-03-05', 
'2025-03-05', 12, 'available', 'Magazyn A, Szuflada 3', 'Regularnie czyścić mechanizm', 1, NOW(), NOW()),

('ASK-005-2024', 'Blokada Petzl Stop', 'Blokada/zjazdownik do kontrolowanego zjazdu', 'stopper', 'Petzl', 'Stop', 'D020BB123456', 
'2023-07-12', '2024-02-28', 120.00, 'Alpine Sport', 'AS/2024/078', '2026-02-28', 
'2025-02-28', 12, 'available', 'Magazyn A, Szafa nr 1', 'Sprawdzać płynność działania', 1, NOW(), NOW()),

('ASK-006-2024', 'Szelki Mammut Ophir 3', 'Szelki wspinaczkowe z 4 pętlami sprzętowymi', 'harness', 'Mammut', 'Ophir 3 Slide', 'MM2023O3987', 
'2023-10-15', '2024-04-12', 320.00, 'Góralski Sklep', 'GS/2024/67', '2026-04-12', 
'2025-04-12', 12, 'available', 'Magazyn B, Szafa nr 2', 'Nowy model z ulepszonym systemem zapięć', 1, NOW(), NOW()),

('ASK-007-2024', 'Lina statyczna Beal Industrie', 'Lina statyczna 10.5mm, 50m do prac przemysłowych', 'rope', 'Beal', 'Industrie 10.5', 'BE2023I1050', 
'2023-06-08', '2024-03-18', 195.00, 'ProSafety', 'PS/2024/134', '2026-03-18', 
'2025-03-18', 12, 'available', 'Magazyn C, Bęben nr 1', 'Do użytku przemysłowego, nie wspinaczkowego', 1, NOW(), NOW()),

('ASK-008-2024', 'Kask Grivel Stealth', 'Lekki kask do wspinaczki i prac na wysokości', 'helmet', 'Grivel', 'Stealth HS', 'GV2023S4321', 
'2023-09-25', '2024-05-08', 165.00, 'TechClimb Sp. z o.o.', 'FV/2024/156', '2026-05-08', 
'2025-05-08', 12, 'available', 'Magazyn B, Półka środkowa', 'Bardzo lekki, odpowiedni do długich prac', 1, NOW(), NOW()),

('ASK-009-2024', 'Lina Edelrid Performance Static', 'Lina statyczna 11mm, 200m, do prac przemysłowych', 'rope', 'Edelrid', 'Performance Static 11mm', 'ED2023PS200', 
'2023-04-10', '2024-06-15', 890.00, 'ProSafety', 'PS/2024/201', '2026-06-15', 
'2025-06-15', 12, 'available', 'Magazyn C, Bęben nr 2', 'Długa lina do dużych obiektów', 1, NOW(), NOW()),

('ASK-010-2024', 'Szelki Singing Rock Expert 3D', 'Profesjonalne szelki z systemem 3D', 'harness', 'Singing Rock', 'Expert 3D Speed', 'SR2023E3D890', 
'2023-12-05', '2024-07-22', 380.00, 'Alpine Sport', 'AS/2024/298', '2026-07-22', 
'2025-07-22', 12, 'available', 'Magazyn A, Szafa nr 3', 'Ergonomiczne, do długotrwałych prac', 1, NOW(), NOW()),

('ASK-011-2024', 'Kask Camp Armour', 'Kask przemysłowy z mocowaniem latarki', 'helmet', 'Camp', 'Armour Pro', 'CP2023AP1122', 
'2023-08-30', '2024-04-18', 125.00, 'Góralski Sklep', 'GS/2024/145', '2026-04-18', 
'2025-04-18', 12, 'available', 'Magazyn B, Półka dolna', 'Z mocowaniem na latarkę czołową', 1, NOW(), NOW()),

('ASK-012-2024', 'Zacisk Petzl Basic', 'Zacisk lewy, podstawowy model', 'ascender', 'Petzl', 'Basic', 'C310BB789012', 
'2023-09-18', '2024-08-05', 75.00, 'TechClimb Sp. z o.o.', 'FV/2024/267', '2026-08-05', 
'2025-08-05', 12, 'available', 'Magazyn A, Szuflada 4', 'Model podstawowy, do szkoleń', 1, NOW(), NOW()),

('ASK-013-2024', 'Blokada Kong Duck', 'Uniwersalna blokada/zjazdownik', 'stopper', 'Kong', 'Duck', 'KG2023DK3344', 
'2023-11-20', '2024-09-12', 98.00, 'Alpine Sport', 'AS/2024/367', '2026-09-12', 
'2025-09-12', 12, 'available', 'Magazyn B, Szuflada 1', 'Lekka, uniwersalna w użyciu', 1, NOW(), NOW()),

('ASK-014-2024', 'Szelki Petzl Falcon Mountain', 'Szelki z siodełkiem do długich prac', 'harness', 'Petzl', 'Falcon Mountain', 'C038BA445566', 
'2023-06-25', '2024-05-30', 420.00, 'ProSafety', 'PS/2024/178', '2026-05-30', 
'2025-05-30', 12, 'available', 'Magazyn A, Szafa nr 4', 'Z siodełkiem, bardzo komfortowe', 1, NOW(), NOW());

-- Zestawy sprzętu (4 zestawy)
INSERT INTO asekuracyjny_equipment_set 
(name, description, set_type, status, next_review_date, review_interval_months, location, notes, created_by_id, created_at, updated_at) 
VALUES 
('Zestaw podstawowy do prac na wysokości', 'Kompletny zestaw dla pracowników rozpoczynających pracę na wysokości', 'basic', 'available', '2025-06-15', 12, 'Magazyn A', 'Zestaw dla początkujących, zawiera podstawowy sprzęt', 1, NOW(), NOW()),

('Zestaw zaawansowany wspinaczkowy', 'Profesjonalny zestaw do prac wysokościowych i ratownictwa', 'advanced', 'available', '2025-07-01', 12, 'Magazyn B', 'Dla doświadczonych pracowników', 1, NOW(), NOW()),

('Zestaw ratowniczy specjalistyczny', 'Zestaw do działań ratowniczych i ewakuacji', 'rescue', 'available', '2025-05-20', 6, 'Magazyn C - Strefa pilna', 'Zestaw gotowości ratowniczej, przeglądy co 6 miesięcy', 1, NOW(), NOW()),

('Zestaw szkoleniowy nr 1', 'Zestaw do szkoleń BHP i kursów pracy na wysokości', 'basic', 'available', '2025-08-10', 12, 'Sala szkoleniowa', 'Sprzęt dedykowany do celów szkoleniowych', 1, NOW(), NOW());

-- Przypisania sprzętu do zestawów (18 przypisań)
INSERT INTO asekuracyjny_equipment_set_items (asekuracyjny_equipment_set_id, asekuracyjny_equipment_id) 
VALUES 
-- Zestaw podstawowy (ID 1): szelki, lina, kask
(1, 1), -- Szelki Petzl AVAO
(1, 2), -- Lina Edelrid Boa
(1, 3), -- Kask Black Diamond

-- Zestaw zaawansowany (ID 2): szelki, lina, kask, zacisk, blokada
(2, 6), -- Szelki Mammut Ophir
(2, 7), -- Lina statyczna Beal
(2, 8), -- Kask Grivel
(2, 4), -- Zacisk Petzl
(2, 5), -- Blokada Petzl

-- Zestaw ratowniczy (ID 3): najlepszy sprzęt
(3, 1), -- Szelki Petzl AVAO
(3, 7), -- Lina statyczna
(3, 3), -- Kask Black Diamond
(3, 4), -- Zacisk
(3, 5), -- Blokada

-- Zestaw szkoleniowy nr 1 (ID 4)
(4, 10), -- Szelki Singing Rock
(4, 9),  -- Lina Performance Static
(4, 11), -- Kask Camp
(4, 12), -- Zacisk Basic
(4, 13); -- Blokada Kong

-- Podsumowanie:
-- 14 elementów sprzętu różnych typów (4 szelki, 3 liny, 3 kaski, 2 zaciski, 2 blokady)
-- 4 zestawy różnych typów (basic x2, advanced x1, rescue x1)
-- 18 przypisań sprzętu do zestawów
-- Wszystkie elementy w statusie "available"
-- Różni producenci: Petzl, Edelrid, Black Diamond, Mammut, Beal, Grivel, Camp, Singing Rock, Kong
-- Różnorodne lokalizacje: Magazyn A/B/C, różne szafy i półki
-- Realistyczne ceny i daty zakupu/przeglądu