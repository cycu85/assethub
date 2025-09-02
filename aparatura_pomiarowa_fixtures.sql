-- Przykładowe dane dla modułu Aparatura Pomiarowa

-- Mierniki i Akcesoria (min 20 sztuk)
INSERT INTO aparatura_pomiarowa_equipment 
(inventory_number, name, description, equipment_type, manufacturer, model, serial_number, manufacturing_date, purchase_date, purchase_price, supplier, invoice_number, warranty_expiry, next_review_date, review_interval_months, status, location, notes, projekt, assigned_to_id, created_by_id, updated_by_id, created_at, updated_at) 
VALUES 
-- Mierniki ciśnienia
('APM-001', 'Manometr ciśnienia 0-10 bar', 'Manometr do pomiaru ciśnienia w instalacjach', 'manometr', 'Wika', 'A-10', 'W001234567', '2023-01-15', '2023-02-20', 450.00, 'TechnoMiar Sp. z o.o.', 'TM/2023/045', '2025-02-20', '2024-08-20', 12, 'available', 'Magazyn A1-15', 'Kalibracja ważna do sierpnia 2024', 'Projekt Alpha', NULL, 1, NULL, NOW(), NOW()),

('APM-002', 'Manometr próżniowy -1/0 bar', 'Manometr do pomiaru podciśnienia', 'manometr', 'Wika', 'A-10V', 'W001234568', '2023-01-20', '2023-02-25', 475.00, 'TechnoMiar Sp. z o.o.', 'TM/2023/046', '2025-02-25', '2024-08-25', 12, 'available', 'Magazyn A1-16', 'Kalibracja ważna do sierpnia 2024', 'Projekt Beta', NULL, 1, NULL, NOW(), NOW()),

('APM-003', 'Manometr różnicowy 0-50 mbar', 'Manometr do pomiaru różnicy ciśnień', 'manometr', 'Dwyer', 'Mark II 25', 'DW2025789', '2023-03-10', '2023-04-15', 620.00, 'Instrumenty Pomiarowe SA', 'IP/2023/123', '2025-04-15', '2024-10-15', 12, 'available', 'Laboratorium L2', 'Bardzo precyzyjny, do pomiarów różnicowych', 'Projekt Gamma', NULL, 1, NULL, NOW(), NOW()),

('APM-004', 'Manometr glicerynowy 0-16 bar', 'Manometr wypełniony gliceryną, odporny na wibracje', 'manometr', 'Afriso', 'RF80G', 'AF789456123', '2023-02-05', '2023-03-10', 380.00, 'Pomiary Przemysłowe Sp. z o.o.', 'PP/2023/089', '2025-03-10', '2024-09-10', 12, 'available', 'Magazyn B2-08', 'Odporny na wibracje i pulsacje', 'Projekt Delta', NULL, 1, NULL, NOW(), NOW()),

-- Mierniki temperatury
('APM-005', 'Termometr przemysłowy PT100', 'Czujnik temperatury PT100 z przetwornikiem', 'termometr', 'Jumo', 'dTRANS T06', 'JU456789012', '2023-01-25', '2023-03-01', 890.00, 'AutomatykaPro Sp. z o.o.', 'AP/2023/234', '2025-03-01', '2024-09-01', 12, 'available', 'Magazyn C1-22', 'Zakres -50°C do +200°C', 'Projekt Epsilon', NULL, 1, NULL, NOW(), NOW()),

('APM-006', 'Termometr bimetaliczny 0-120°C', 'Termometr mechaniczny bimetaliczny', 'termometr', 'Wika', 'A48', 'W987654321', '2023-02-10', '2023-03-15', 245.00, 'TechnoMiar Sp. z o.o.', 'TM/2023/078', '2025-03-15', '2024-09-15', 12, 'available', 'Magazyn A1-18', 'Prosty w obsłudze, nie wymaga zasilania', 'Projekt Zeta', NULL, 1, NULL, NOW(), NOW()),

('APM-007', 'Pirometr cyfrowy', 'Termometr bесконтактowy na podczerwień', 'termometr', 'Fluke', '568', 'FL568123456', '2023-03-20', '2023-04-25', 1250.00, 'Narzędzia Pomiarowe SA', 'NP/2023/345', '2025-04-25', '2024-10-25', 12, 'assigned', 'Pracownik: Jan Kowalski', 'Zakres -40°C do +800°C, laser wskaźnik', 'Projekt Eta', NULL, 1, NULL, NOW(), NOW()),

('APM-008', 'Termometr kontaktowy cyfrowy', 'Termometr z sondą termoparową K', 'termometr', 'Testo', '925', 'TE925789123', '2023-02-28', '2023-04-05', 680.00, 'Laboratoria Sp. z o.o.', 'LAB/2023/456', '2025-04-05', '2024-10-05', 12, 'available', 'Laboratorium L1', 'Z sondą penetracyjną i powierzchniową', 'Projekt Theta', NULL, 1, NULL, NOW(), NOW()),

-- Mierniki przepływu
('APM-009', 'Przepływomierz elektromagnetyczny DN50', 'Przepływomierz do cieczy przewodzących', 'przepływomierz', 'Endress+Hauser', 'Promag 53P', 'EH53P456789', '2023-01-30', '2023-03-20', 3450.00, 'E+H Polska Sp. z o.o.', 'EH/2023/567', '2025-03-20', '2024-09-20', 12, 'available', 'Magazyn D1-05', 'Dokładność ±0,5%, IP67', 'Projekt Iota', NULL, 1, NULL, NOW(), NOW()),

('APM-010', 'Przepływomierz turbinowy G25', 'Przepływomierz do gazów', 'przepływomierz', 'Elster', 'TRZ G25', 'EL456789123', '2023-02-15', '2023-03-25', 1890.00, 'GazTech Sp. z o.o.', 'GT/2023/678', '2025-03-25', '2024-09-25', 12, 'available', 'Magazyn E2-12', 'Do pomiaru przepływu gazu ziemnego', 'Projekt Kappa', NULL, 1, NULL, NOW(), NOW()),

('APM-011', 'Rotametr szklany DN25', 'Przepływomierz pływakowy do cieczy', 'przepływomierz', 'Krohne', 'DK32', 'KR789123456', '2023-03-05', '2023-04-10', 1200.00, 'FlowControl SA', 'FC/2023/789', '2025-04-10', '2024-10-10', 12, 'available', 'Laboratorium L3', 'Zakres 0,5-5 m3/h woda', 'Projekt Lambda', NULL, 1, NULL, NOW(), NOW()),

-- Mierniki poziomu
('APM-012', 'Wskaźnik poziomu magnetyczny', 'Wskaźnik poziomu cieczy w zbiorniku', 'poziomomierz', 'Magnetrol', '705-510A', 'MG123456789', '2023-02-20', '2023-04-01', 2100.00, 'Level Measurement Sp. z o.o.', 'LM/2023/890', '2025-04-01', '2024-10-01', 12, 'available', 'Magazyn F1-18', 'Zakres pomiaru 0-2000mm', 'Projekt Mu', NULL, 1, NULL, NOW(), NOW()),

('APM-013', 'Przetwornik poziomu hydrostatyczny', 'Przetwornik ciśnienia do pomiaru poziomu', 'poziomomierz', 'Vega', 'VEGABAR 28', 'VG789456123', '2023-01-18', '2023-03-08', 1750.00, 'ProcessControl SA', 'PC/2023/901', '2025-03-08', '2024-09-08', 12, 'available', 'Magazyn F2-22', 'Wyjście 4-20mA, Hart', 'Projekt Nu', NULL, 1, NULL, NOW(), NOW()),

('APM-014', 'Czujnik poziomu ultradźwiękowy', 'Bесконтактowy czujnik poziomu', 'poziomomierz', 'Pepperl+Fuchs', 'UB4000-30GM-E5-V15', 'PF456123789', '2023-03-08', '2023-04-18', 980.00, 'Sensory Przemysłowe Sp. z o.o.', 'SP/2023/012', '2025-04-18', '2024-10-18', 12, 'assigned', 'Pracownik: Anna Nowak', 'Zakres 0,3-4m, wyjście analogowe', 'Projekt Xi', NULL, 1, NULL, NOW(), NOW()),

-- Multimery i mierniki uniwersalne
('APM-015', 'Multimetr przemysłowy', 'Multimetr cyfrowy do pomiarów elektrycznych', 'multimetr', 'Fluke', '87V', 'FL87V123456', '2023-02-12', '2023-03-28', 1450.00, 'ElektroTest SA', 'ET/2023/123', '2025-03-28', '2024-09-28', 12, 'available', 'Laboratorium L4', 'TRMS, CAT IV 600V', 'Projekt Omikron', NULL, 1, NULL, NOW(), NOW()),

('APM-016', 'Miernik rezystancji izolacji', 'Megaomomierz do testów izolacji', 'megaomomierz', 'Fluke', '1550C', 'FL1550C789', '2023-01-22', '2023-03-12', 2890.00, 'ElektroTest SA', 'ET/2023/234', '2025-03-12', '2024-09-12', 12, 'available', 'Magazyn G1-08', 'Napięcia testowe 250V-5kV', 'Projekt Pi', NULL, 1, NULL, NOW(), NOW()),

('APM-017', 'Miernik mocy elektrycznej', 'Analizator jakości zasilania', 'analizator_mocy', 'Fluke', '435-II', 'FL435II456', '2023-03-15', '2023-04-30', 8900.00, 'PowerQuality Sp. z o.o.', 'PQ/2023/345', '2025-04-30', '2024-10-30', 12, 'available', 'Laboratorium L5', 'Analiza harmonicznych, flicker', 'Projekt Rho', NULL, 1, NULL, NOW(), NOW()),

-- Wagi i akcesoria
('APM-018', 'Waga laboratoryjna precyzyjna', 'Waga analityczna 0,1mg', 'waga', 'Mettler Toledo', 'XS205', 'MT205789123', '2023-02-08', '2023-03-22', 4200.00, 'Scales Professional SA', 'SP/2023/456', '2025-03-22', '2024-09-22', 12, 'available', 'Laboratorium L6', 'Dokładność 0,1mg, kalibracja wewnętrzna', 'Projekt Sigma', NULL, 1, NULL, NOW(), NOW()),

('APM-019', 'Waga przemysłowa', 'Waga platformowa do 300kg', 'waga', 'Radwag', 'PUE C32', 'RW456789123', '2023-01-28', '2023-03-18', 1850.00, 'Industrial Scales Sp. z o.o.', 'IS/2023/567', '2025-03-18', '2024-09-18', 12, 'available', 'Hala produkcyjna H1', 'Platforma 60x80cm, IP65', 'Projekt Tau', NULL, 1, NULL, NOW(), NOW()),

-- Mierniki specjalistyczne
('APM-020', 'Miernik pH cyfrowy', 'pH-metr laboratoryjny z elektrodą', 'ph_metr', 'Hanna', 'HI-2020', 'HI2020456789', '2023-02-25', '2023-04-08', 1650.00, 'ChemLab Sp. z o.o.', 'CL/2023/678', '2025-04-08', '2024-10-08', 12, 'available', 'Laboratorium L7', 'Zakres pH 0-14, rozdzielczość 0,01pH', 'Projekt Upsilon', NULL, 1, NULL, NOW(), NOW()),

('APM-021', 'Miernik przewodności elektrycznej', 'Konduktometr do pomiaru przewodności cieczy', 'konduktometr', 'WTW', 'Cond 3110', 'WTW789456123', '2023-03-12', '2023-04-22', 2100.00, 'WaterTest SA', 'WT/2023/789', '2025-04-22', '2024-10-22', 12, 'assigned', 'Pracownik: Piotr Wiśniewski', 'Zakres 0,001-2000 mS/cm', 'Projekt Phi', NULL, 1, NULL, NOW(), NOW()),

('APM-022', 'Anemometr cyfrowy', 'Miernik prędkości przepływu powietrza', 'anemometr', 'Testo', '416', 'TE416123456', '2023-02-18', '2023-04-02', 890.00, 'AirFlow Instruments Sp. z o.o.', 'AI/2023/890', '2025-04-02', '2024-10-02', 12, 'available', 'Magazyn H2-15', 'Zakres 0,6-40 m/s, Bluetooth', 'Projekt Chi', NULL, 1, NULL, NOW(), NOW()),

('APM-023', 'Miernik wilgotności', 'Higrometr do pomiaru wilgotności względnej', 'higrometr', 'Testo', '625', 'TE625789123', '2023-01-15', '2023-02-28', 1120.00, 'Climate Control SA', 'CC/2023/901', '2025-02-28', '2024-08-28', 12, 'available', 'Laboratorium L8', 'Zakres 0-100%RH, ±2%RH', 'Projekt Psi', NULL, 1, NULL, NOW(), NOW()),

('APM-024', 'Spektrofotometr UV-VIS', 'Spektrofotometr do analizy spektralnej', 'spektrofotometr', 'Hach', 'DR3900', 'HA3900456789', '2023-03-01', '2023-04-15', 12500.00, 'AnalyticalLab Sp. z o.o.', 'AL/2023/012', '2025-04-15', '2024-10-15', 12, 'available', 'Laboratorium L9', 'Zakres 190-1100nm, dotykowy wyświetlacz', 'Projekt Omega', NULL, 1, NULL, NOW(), NOW()),

('APM-025', 'Kalibrator ciśnienia', 'Kalibrator pneumatyczny 0-10 bar', 'kalibrator', 'Druck', 'DPI611', 'DR611789456', '2023-02-22', '2023-04-05', 3200.00, 'Calibration Services SA', 'CS/2023/123', '2025-04-05', '2024-10-05', 12, 'available', 'Pracownia kalibracji PK1', 'Dokładność ±0,025%, wbudowana pompa', 'Projekt Kalibracja', NULL, 1, NULL, NOW(), NOW());

-- Zestawy pomiarowe (min 3 sztuki)
INSERT INTO aparatura_pomiarowa_equipment_set 
(name, description, set_type, status, location, notes, next_review_date, review_interval_months, assigned_to_id, created_by_id, updated_by_id, created_at, updated_at) 
VALUES 
('Zestaw pomiarowy ciśnienia ZP-001', 'Kompletny zestaw do pomiarów ciśnienia w instalacjach przemysłowych', 'pressure_measurement', 'available', 'Laboratorium L2', 'Zestaw zawiera manometry różnych zakresów, kalibrator i akcesoria', '2024-12-01', 12, NULL, 1, NULL, NOW(), NOW()),

('Zestaw termometryczny ZT-002', 'Profesjonalny zestaw do pomiarów temperatury', 'temperature_measurement', 'available', 'Laboratorium L1', 'Zestaw z termometrami kontaktowymi i beskontaktowymi', '2025-01-15', 12, NULL, 1, NULL, NOW(), NOW()),

('Zestaw kontroli przepływu ZF-003', 'Kompletny zestaw do pomiarów i kontroli przepływu cieczy i gazów', 'flow_measurement', 'assigned', 'Hala produkcyjna H1', 'Zestaw mobilny, idealny do kontroli instalacji', '2024-11-20', 12, NULL, 1, NULL, NOW(), NOW()),

('Zestaw analityczny laboratoryjny ZA-004', 'Zestaw przyrządów do analiz laboratoryjnych', 'analytical_measurement', 'available', 'Laboratorium L9', 'Zawiera spektrofotometr, pH-metr, konduktometr i wagę precyzyjną', '2025-02-10', 12, NULL, 1, NULL, NOW(), NOW()),

('Zestaw pomiarów elektrycznych ZE-005', 'Kompletny zestaw do pomiarów i testów elektrycznych', 'electrical_measurement', 'available', 'Laboratorium L4', 'Multimetry, megaomomierz, analizator mocy', '2024-12-30', 12, NULL, 1, NULL, NOW(), NOW());

-- Powiązania sprzętu z zestawami  
INSERT INTO aparatura_pomiarowa_equipment_set_items (aparatura_pomiarowa_equipment_set_id, aparatura_pomiarowa_equipment_id) 
SELECT 
    (SELECT id FROM aparatura_pomiarowa_equipment_set WHERE name = 'Zestaw pomiarowy ciśnienia ZP-001'),
    e.id
FROM aparatura_pomiarowa_equipment e 
WHERE e.inventory_number IN ('APM-001', 'APM-002', 'APM-003', 'APM-004', 'APM-025');

INSERT INTO aparatura_pomiarowa_equipment_set_items (aparatura_pomiarowa_equipment_set_id, aparatura_pomiarowa_equipment_id) 
SELECT 
    (SELECT id FROM aparatura_pomiarowa_equipment_set WHERE name = 'Zestaw termometryczny ZT-002'),
    e.id
FROM aparatura_pomiarowa_equipment e 
WHERE e.inventory_number IN ('APM-005', 'APM-006', 'APM-007', 'APM-008');

INSERT INTO aparatura_pomiarowa_equipment_set_items (aparatura_pomiarowa_equipment_set_id, aparatura_pomiarowa_equipment_id) 
SELECT 
    (SELECT id FROM aparatura_pomiarowa_equipment_set WHERE name = 'Zestaw kontroli przepływu ZF-003'),
    e.id
FROM aparatura_pomiarowa_equipment e 
WHERE e.inventory_number IN ('APM-009', 'APM-010', 'APM-011', 'APM-022');

INSERT INTO aparatura_pomiarowa_equipment_set_items (aparatura_pomiarowa_equipment_set_id, aparatura_pomiarowa_equipment_id) 
SELECT 
    (SELECT id FROM aparatura_pomiarowa_equipment_set WHERE name = 'Zestaw analityczny laboratoryjny ZA-004'),
    e.id
FROM aparatura_pomiarowa_equipment e 
WHERE e.inventory_number IN ('APM-018', 'APM-020', 'APM-021', 'APM-024');

INSERT INTO aparatura_pomiarowa_equipment_set_items (aparatura_pomiarowa_equipment_set_id, aparatura_pomiarowa_equipment_id) 
SELECT 
    (SELECT id FROM aparatura_pomiarowa_equipment_set WHERE name = 'Zestaw pomiarów elektrycznych ZE-005'),
    e.id
FROM aparatura_pomiarowa_equipment e 
WHERE e.inventory_number IN ('APM-015', 'APM-016', 'APM-017');