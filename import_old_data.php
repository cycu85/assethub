<?php

/**
 * Skrypt importu danych z starego systemu asekuracji do nowego
 * 
 * UWAGA: Uruchom ten skrypt w środowisku deweloperskim z połączeniem do bazy danych
 * 
 * Strategia importu:
 * 1. Import zestawów z asek_zestawy -> asekuracyjny_equipment_set
 * 2. Import sprzętu z asek_ticket -> asekuracyjny_equipment
 * 3. Utworzenie relacji many-to-many na podstawie zestaw_id
 */

ini_set('max_execution_time', 300); // 5 minut

// Sprawdź czy uruchamiamy w CLI
if (php_sapi_name() !== 'cli') {
    die("Ten skrypt musi być uruchomiony z linii komend\n");
}

// Konfiguracja połączenia do bazy danych
$host = '127.0.0.1';
$dbname = 'assethub'; // Nazwa bazy nowego systemu
$username = 'assethub';
$password = 'secure_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Połączono z bazą danych\n";
} catch (PDOException $e) {
    die("❌ Błąd połączenia z bazą: " . $e->getMessage() . "\n");
}

// Mapowanie statusów sprzętu
$equipmentStatusMap = [
    'Sprawny' => 'available',
    'Wycofany z użytku' => 'retired', 
    'Uszkodzony' => 'damaged',
    'Zutylizowany' => 'retired',
    '' => 'available'
];

// Mapowanie statusów zestawów
$setStatusMap = [
    'Sprawny' => 'available',
    'Wycofany z użytku' => 'retired',
    'Zdekompletowany' => 'incomplete',
    '' => 'available'
];

// Mapowanie typów sprzętu do nowych wartości
$equipmentTypeMap = [
    'Wyciągarka' => 'ascender',
    'Lina Statyczna 10,5' => 'rope',
    'Lina Statyczna' => 'rope',
    'Szelki' => 'harness',
    'Kask' => 'helmet',
    'Karabinek' => 'ascender',
    'Zacisk' => 'ascender'
];

// Funkcje pomocnicze
function convertDate($dateString) {
    if ($dateString === '0000-00-00' || empty($dateString)) {
        return null;
    }
    try {
        $date = new DateTime($dateString);
        return $date->format('Y-m-d');
    } catch (Exception $e) {
        return null;
    }
}

function convertDateTime($datetimeString) {
    if ($datetimeString === '0000-00-00 00:00:00' || empty($datetimeString)) {
        return date('Y-m-d H:i:s'); // Użyj obecnej daty
    }
    try {
        $date = new DateTime($datetimeString);
        return $date->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return date('Y-m-d H:i:s');
    }
}

function findUserId($pdo, $userName) {
    if (empty($userName) || $userName === 'DZIAŁ KONTROLI I JAKOŚCI') {
        // Znajdź system admin lub pierwszego dostępnego użytkownika
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'sa' OR id = 1 LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : 1;
    }
    
    // Spróbuj znaleźć użytkownika po nazwie (może być potrzebna bardziej inteligentna logika)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(CONCAT(first_name, ' ', last_name)) LIKE LOWER(?) LIMIT 1");
    $stmt->execute(["%$userName%"]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        return $result['id'];
    }
    
    // Fallback - użytkownik systemowy
    return 1;
}

echo "\n🚀 Rozpoczynanie importu danych...\n\n";

// ============================================================================
// KROK 1: WCZYTANIE DANYCH ZE STARYCH PLIKÓW SQL
// ============================================================================

echo "📂 Wczytywanie danych ze starych plików SQL...\n";

// Wczytaj dane zestawów
$zestawyFile = __DIR__ . '/old_data/grapeasekzestawy.sql';
if (!file_exists($zestawyFile)) {
    die("❌ Plik $zestawyFile nie został znaleziony\n");
}

$zestawyContent = file_get_contents($zestawyFile);
preg_match_all('/\((\d+),\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\'\)/', $zestawyContent, $zestawyMatches);

echo "   📦 Znaleziono " . count($zestawyMatches[0]) . " zestawów\n";

// Wczytaj dane sprzętu
$sprzetFile = __DIR__ . '/old_data/grapeaseksrzet.sql';
if (!file_exists($sprzetFile)) {
    die("❌ Plik $sprzetFile nie został znaleziony\n");
}

$sprzetContent = file_get_contents($sprzetFile);
// Pattern dla sprzętu (23 kolumny)
preg_match_all('/\((\d+),\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\'\)/', $sprzetContent, $sprzetMatches);

echo "   🔧 Znaleziono " . count($sprzetMatches[0]) . " elementów sprzętu\n";

// ============================================================================
// KROK 2: IMPORT ZESTAWÓW
// ============================================================================

echo "\n📦 Importowanie zestawów...\n";

$importedSets = [];
$stmt = $pdo->prepare("
    INSERT INTO asekuracyjny_equipment_set 
    (name, description, status, location, notes, created_at, updated_at, created_by_id, updated_by_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

for ($i = 0; $i < count($zestawyMatches[0]); $i++) {
    $oldId = $zestawyMatches[1][$i];
    $name = $zestawyMatches[2][$i];
    $description = $zestawyMatches[3][$i];
    $whoUse = $zestawyMatches[4][$i];
    $region = $zestawyMatches[9][$i];
    $status = $zestawyMatches[10][$i];
    $whoMod = $zestawyMatches[11][$i];
    $dateMod = $zestawyMatches[12][$i];
    
    // Mapuj status
    $newStatus = $setStatusMap[$status] ?? 'available';
    
    // Znajdź użytkownika
    $createdById = findUserId($pdo, $whoMod);
    $updatedById = $createdById;
    
    // Konwertuj datę
    $updatedAt = convertDateTime($dateMod);
    $createdAt = $updatedAt;
    
    // Przygotuj notes
    $notes = '';
    if (!empty($whoUse)) {
        $notes .= "Użytkownik: $whoUse\n";
    }
    if (!empty($region)) {
        $notes .= "Region: $region";
    }
    
    try {
        $stmt->execute([
            $name,
            $description,
            $newStatus,
            $region, // location
            trim($notes), // notes
            $createdAt,
            $updatedAt,
            $createdById,
            $updatedById
        ]);
        
        $newId = $pdo->lastInsertId();
        $importedSets[$oldId] = $newId;
        
        echo "   ✅ Zaimportowano zestaw: $name (ID: $oldId -> $newId)\n";
        
    } catch (PDOException $e) {
        echo "   ❌ Błąd importu zestawu $name: " . $e->getMessage() . "\n";
    }
}

echo "   📊 Zaimportowano " . count($importedSets) . " zestawów\n";

// ============================================================================
// KROK 3: IMPORT SPRZĘTU
// ============================================================================

echo "\n🔧 Importowanie sprzętu...\n";

$importedEquipment = [];
$stmt = $pdo->prepare("
    INSERT INTO asekuracyjny_equipment 
    (inventory_number, name, description, equipment_type, manufacturer, model, serial_number, 
     purchase_date, supplier, invoice_number, warranty_expiry, next_review_date, status, 
     notes, created_at, updated_at, created_by_id, updated_by_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$equipmentSetRelations = []; // Przechowuje relacje sprzęt-zestaw

for ($i = 0; $i < count($sprzetMatches[0]); $i++) {
    $oldId = $sprzetMatches[1][$i];
    $name = $sprzetMatches[2][$i];
    $description = $sprzetMatches[3][$i];
    $type = $sprzetMatches[4][$i];
    $owner = $sprzetMatches[5][$i];
    $producent = $sprzetMatches[6][$i];
    $model = $sprzetMatches[7][$i];
    $snNr = $sprzetMatches[8][$i];
    $whoUse = $sprzetMatches[9][$i];
    $typeCalib = $sprzetMatches[10][$i];
    $dateCalib = $sprzetMatches[11][$i];
    $dateNextCalib = $sprzetMatches[12][$i];
    $daysToCalib = $sprzetMatches[13][$i];
    $dateBuy = $sprzetMatches[14][$i];
    $sklep = $sprzetMatches[15][$i];
    $fvNr = $sprzetMatches[16][$i];
    $warrantyEndDate = $sprzetMatches[17][$i];
    $status = $sprzetMatches[18][$i];
    $other = $sprzetMatches[19][$i];
    $imagePath = $sprzetMatches[20][$i];
    $zestawId = $sprzetMatches[21][$i];
    $dateMod = $sprzetMatches[22][$i];
    $whoMod = $sprzetMatches[23][$i];
    
    // Użyj nazwy jako numeru inwentarzowego (w starym systemie name = SPRZET_xxxxx)
    $inventoryNumber = $name;
    
    // Utworz sensowną nazwę sprzętu na podstawie typu, producenta i modelu
    $equipmentName = trim("$type $producent $model");
    if (empty($equipmentName)) {
        $equipmentName = $description ?: "Sprzęt asekuracyjny";
    }
    
    // Mapuj typ sprzętu
    $equipmentType = $equipmentTypeMap[$type] ?? 'harness'; // domyślnie szelki
    
    // Mapuj status
    $newStatus = $equipmentStatusMap[$status] ?? 'available';
    
    // Znajdź użytkownika
    $createdById = findUserId($pdo, $whoMod);
    $updatedById = $createdById;
    
    // Konwertuj daty
    $purchaseDate = convertDate($dateBuy);
    $warrantyExpiry = convertDate($warrantyEndDate);
    $nextReviewDate = convertDate($dateNextCalib);
    $updatedAt = convertDateTime($dateMod);
    $createdAt = $updatedAt;
    
    // Przygotuj notes
    $notes = '';
    if (!empty($other)) {
        $notes .= "Uwagi: $other\n";
    }
    if (!empty($owner)) {
        $notes .= "Właściciel: $owner\n";
    }
    if (!empty($whoUse)) {
        $notes .= "Użytkownik: $whoUse\n";
    }
    if (!empty($typeCalib)) {
        $notes .= "Typ kalibracji: $typeCalib\n";
    }
    if (!empty($dateCalib) && $dateCalib !== '0000-00-00') {
        $notes .= "Data ostatniej kalibracji: $dateCalib";
    }
    
    try {
        $stmt->execute([
            $inventoryNumber,
            $equipmentName,
            $description,
            $equipmentType,
            $producent ?: null,
            $model ?: null,
            $snNr ?: null,
            $purchaseDate,
            $sklep ?: null,
            $fvNr ?: null,
            $warrantyExpiry,
            $nextReviewDate,
            $newStatus,
            trim($notes),
            $createdAt,
            $updatedAt,
            $createdById,
            $updatedById
        ]);
        
        $newId = $pdo->lastInsertId();
        $importedEquipment[$oldId] = $newId;
        
        // Zapisz relację do zestawu jeśli istnieje
        if (!empty($zestawId) && isset($importedSets[$zestawId])) {
            $equipmentSetRelations[] = [
                'equipment_id' => $newId,
                'set_id' => $importedSets[$zestawId]
            ];
        }
        
        echo "   ✅ Zaimportowano sprzęt: $equipmentName (ID: $oldId -> $newId)\n";
        
    } catch (PDOException $e) {
        echo "   ❌ Błąd importu sprzętu $equipmentName: " . $e->getMessage() . "\n";
    }
}

echo "   📊 Zaimportowano " . count($importedEquipment) . " elementów sprzętu\n";

// ============================================================================
// KROK 4: TWORZENIE RELACJI SPRZĘT-ZESTAW
// ============================================================================

echo "\n🔗 Tworzenie relacji sprzęt-zestaw...\n";

$stmt = $pdo->prepare("
    INSERT IGNORE INTO asekuracyjny_equipment_set_items 
    (asekuracyjny_equipment_set_id, asekuracyjny_equipment_id)
    VALUES (?, ?)
");

$relationsCount = 0;
foreach ($equipmentSetRelations as $relation) {
    try {
        $stmt->execute([$relation['set_id'], $relation['equipment_id']]);
        $relationsCount++;
        echo "   ✅ Utworzono relację sprzęt {$relation['equipment_id']} <-> zestaw {$relation['set_id']}\n";
    } catch (PDOException $e) {
        echo "   ❌ Błąd tworzenia relacji: " . $e->getMessage() . "\n";
    }
}

echo "   📊 Utworzono $relationsCount relacji\n";

// ============================================================================
// KROK 5: PODSUMOWANIE I WALIDACJA
// ============================================================================

echo "\n📊 PODSUMOWANIE IMPORTU:\n";
echo "   📦 Zestawy: " . count($importedSets) . " zaimportowane\n";
echo "   🔧 Sprzęt: " . count($importedEquipment) . " zaimportowany\n";
echo "   🔗 Relacje: $relationsCount utworzonych\n";

// Sprawdź poprawność importu
$stmt = $pdo->query("SELECT COUNT(*) FROM asekuracyjny_equipment_set");
$setsInDb = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM asekuracyjny_equipment");
$equipmentInDb = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM asekuracyjny_equipment_set_items");
$relationsInDb = $stmt->fetchColumn();

echo "\n✅ WALIDACJA:\n";
echo "   📦 Zestawy w bazie: $setsInDb\n";
echo "   🔧 Sprzęt w bazie: $equipmentInDb\n";
echo "   🔗 Relacje w bazie: $relationsInDb\n";

echo "\n🎉 Import zakończony pomyślnie!\n";

// ============================================================================
// UWAGI I ZALECENIA
// ============================================================================

echo "\n⚠️  UWAGI PO IMPORCIE:\n";
echo "   1. Sprawdź poprawność mapowania statusów sprzętu i zestawów\n";
echo "   2. Zweryfikuj przypisania użytkowników (może wymagać korekty)\n";
echo "   3. Sprawdź czy wszystkie daty zostały poprawnie zkonwertowane\n";
echo "   4. Dodaj brakujące informacje (ceny, lokalizacje, itp.)\n";
echo "   5. Usuń lub popraw duplikaty jeśli wystąpiły\n";
echo "   6. Uruchom cache:clear w Symfony po imporcie\n\n";

echo "🔗 NASTĘPNE KROKI:\n";
echo "   1. php bin/console cache:clear\n";
echo "   2. Sprawdź interfejs webowy w module asekuracja\n";
echo "   3. Zweryfikuj poprawność wyświetlania danych\n";
echo "   4. W razie potrzeby popraw mapowania i uruchom ponownie\n\n";

?>