# AssetHub - System Zarządzania Zasobami Firmy

<div align="center">
  <h3>📦 Kompleksowy system do zarządzania zasobami przedsiębiorstwa</h3>
  <p>
    <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.2+">
    <img src="https://img.shields.io/badge/Symfony-7.0-000000?style=flat-square&logo=symfony&logoColor=white" alt="Symfony 7.0">
    <img src="https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
    <img src="https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white" alt="Bootstrap 5.3">
    <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License MIT">
  </p>
</div>

## 📋 Spis Treści

- [O Projekcie](#-o-projekcie)
- [Funkcjonalności](#-funkcjonalności)
- [Wymagania Systemowe](#-wymagania-systemowe)
- [Instalacja](#-instalacja)
- [Konfiguracja](#-konfiguracja)
- [Użytkowanie](#-użytkowanie)
- [API i Integracje](#-api-i-integracje)
- [Rozwój](#-rozwój)
- [Wsparcie](#-wsparcie)
- [Licencja](#-licencja)

## 🎯 O Projekcie

AssetHub to enterprise-grade system zarządzania zasobami firmy, zbudowany w oparciu o nowoczesne wzorce architektoniczne. Wykorzystuje Service Layer Pattern, CQRS i Event-Driven Architecture dla maksymalnej skalowalności i bezpieczeństwa. System oferuje modularną architekturę z granularną kontrolą dostępu, kompleksowym audytem i zaawansowanymi funkcjami bezpieczeństwa.

### Główne Cechy

- **🏗️ Architektura Enterprise** - Service Layer Pattern + CQRS + Event-Driven Architecture
- **🔐 Zaawansowane Bezpieczeństwo** - AuthorizationService, rate limiting, kompleksowy audit trail
- **📊 Kompleksowy System Audytu** - AuditService z wielopoziomowym logowaniem wszystkich operacji
- **👥 Granularne Zarządzanie Użytkownikami** - Role i uprawnienia per moduł z LDAP/AD
- **⚡ Wysokowydajne Wyszukiwanie** - Globalne wyszukiwanie z rate limiting i audytem
- **📈 Inteligentny Dashboard** - Dynamiczne metryki dostosowane do uprawnień użytkownika
- **🔧 Zaawansowane Zarządzanie Modułami** - Aktywacja tylko potrzebnych funkcjonalności
- **📱 Responsywny Interfejs** - Optymalizacja dla urządzeń mobilnych
- **🚀 Łatwa Instalacja** - Graficzny kreator instalacji

## ✨ Funkcjonalności

### 🔧 Moduł Sprzętu i Narzędzi
- Inwentaryzacja sprzętu z numerami inwentarzowymi
- Śledzenie lokalizacji i przypisań do użytkowników
- Harmonogram przeglądów i konserwacji
- Historia użytkowania i napraw
- Zarządzanie dokumentacją i certyfikatami

### 🛡️ Moduł Asekuracja (Sprzęt Wysokościowy)
- **Zarządzanie sprzętem asekuracyjnym** - szelki, liny, kaski, zaciski, blokady z pełnymi metadanymi
- **System przeglądów i certyfikacji** - okresowe, po uszkodzeniu, po naprawie, początkowe
- **Zestawy sprzętu** - tworzenie kompletnych zestawów z wielokrotnym wyborem elementów
- **Modal zakończenia przeglądu** - upload załączników (PDF, DOC, JPG, XLS), wyniki, certyfikaty
- **Automatyczne statusy** - sprzęt/zestaw automatycznie zmienia status podczas procesu przeglądu
- **Historia przeglądów z snapshot** - sztywne powiązania elementów z przeglądami, niezależne od zmian w zestawach
- **System uprawnień** - granularne role (ADMIN, EDITOR, VIEWER, LIST) z pełną kontrolą dostępu
- **Załączniki przeglądów** - bezpieczne przechowywanie w katalogach `public/uploads/asekuracja/`
- **Dashboard z metrykami** - karty sprzętu, statystyki przeglądów, alerty terminów
- **Słowniki konfiguracyjne** - typy sprzętu, statusy, typy zestawów i przeglądów
- **Indywidualne wyniki w zestawach** - różne wyniki dla poszczególnych elementów w przeglądach zestawów

### 🛡️ Moduł Środków Ochrony Osobistej (ŚOP)
- Kontrola wydawania ŚOP zgodnie z normami
- Śledzenie dat ważności certyfikatów
- Przypomnienia o wymianie sprzętu
- Ewidencja szkoleń BHP

### 💻 Moduł Sprzętu IT
- Inwentaryzacja komputerów, laptopów, serwerów
- Śledzenie licencji oprogramowania
- Zarządzanie konfiguracjami sprzętowymi
- Historia serwisowania i modernizacji

### 🚗 Moduł Floty Pojazdów
- Rejestr pojazdów służbowych
- Książki jazd i ewidencja przebiegu
- Harmonogram przeglądów i ubezpieczeń
- Kontrola kosztów eksploatacji

### 👨‍💼 Panel Administracyjny Enterprise
- **🔐 AuthorizationService** - Centralizowana autoryzacja z granularnymi uprawnieniami per moduł
- **📊 AuditService** - Kompleksowy system audytu z wielopoziomowym logowaniem:
  - Akcje użytkowników (INFO) - wszystkie operacje użytkowników
  - Zdarzenia bezpieczeństwa (ERROR) - rate limiting, niepowodzenia logowania
  - Akcje administracyjne (NOTICE) - backup, maintenance, konfiguracja
  - Operacje bazodanowe (NOTICE/ERROR) - backup, optymalizacja, błędy
- **👥 Zaawansowane Zarządzanie Użytkownikami** - Role z uprawnieniami VIEW/CREATE/EDIT/DELETE per moduł
- **🔧 System Modułowy** - Aktywacja i zarządzanie modułami z pełną kontrolą dostępu
- **📈 Dashboard Enterprise** - Inteligentne metryki dostosowane do uprawnień użytkownika
- **⚡ Globalne Wyszukiwanie** - Multi-module search z rate limiting i audytem
- **💾 Zarządzanie Bazą Danych** - Backup, optymalizacja, maintenance z pełnym audytem
- **🔍 Podgląd Logów** - Zaawansowane filtrowanie logów systemowych w panelu
- **📚 System Słowników** - Centralne zarządzanie słownikami dla wszystkich modułów

#### 🎨 Ustawienia Systemu
- **Ogólne** - Dynamiczne ustawienia nazwy aplikacji, logo firmy i kolorystyki z zaawansowanym systemem kollorów:
  - **Niezależna konfiguracja kolorów**: główny kolor aplikacji, tło menu, tekst menu, aktywny element menu
  - **Dual color picker + HEX**: wizualny selektor i pole tekstowe z synchronizacją dwukierunkową
  - **Podgląd na żywo**: wszystkie zmiany widoczne natychmiast w prawym panelu
  - **Inteligentna walidacja**: automatyczne poprawki formatu HEX (dodawanie #, rozszerzanie z 3 do 6 znaków)
  - **Reset do domyślnych**: przycisk przywracający wszystkie ustawienia do wartości fabrycznych z modalem potwierdzenia
- **📧 Email** - Kompletna konfiguracja SMTP z testowaniem połączenia i wysyłaniem wiadomości testowych
- **🔗 LDAP/Active Directory** - Pełna integracja z AD: synchronizacja użytkowników, mapowanie pól, hierarchia przełożonych
- **💾 Baza Danych** - Zarządzanie bazą danych: kopie zapasowe (mysqldump), optymalizacja tabel, analiza, czyszczenie logów

### 👤 System Profili Użytkowników
- **Profil użytkownika** - Przeglądanie i edycja danych osobowych
- **Zmiana hasła** - Bezpieczna zmiana hasła dla użytkowników lokalnych
- **Avatary użytkowników** - Upload i zarządzanie zdjęciami profilowymi (JPG, PNG, GIF, WebP)
- **Integracja LDAP** - Automatyczna synchronizacja danych z Active Directory

## 🏗️ Architektura Enterprise

### Service Layer Pattern
Wszystkie operacje biznesowe realizowane przez dedykowane serwisy:
- **EquipmentService** - Zarządzanie sprzętem z pełną logiką biznesową
- **AsekuracyjnyService** - Kompletne zarządzanie sprzętem asekuracyjnym, zestawami i przeglądami
- **AuthorizationService** - Centralizowana autoryzacja zastępująca stary PermissionService
- **AuditService** - Kompleksowy system audytu z wielopoziomowym logowaniem
- **AdminService** - Operacje administracyjne, backup, maintenance

### CQRS (Command Query Responsibility Segregation)
Separacja komend i zapytań dla lepszej architektury:
- **Commands** - CreateEquipmentCommand, UpdateUserCommand, etc.
- **Queries** - GetEquipmentQuery, SearchUsersQuery, etc.
- **Handlers** - Dedykowane handlery dla każdej operacji

### Event-Driven Architecture
System zdarzeń domenowych z subskrybentami:
- **Events** - EquipmentCreatedEvent, UserUpdatedEvent, SecurityEvent, etc.
- **Event Subscribers** - Automatyczne akcje, powiadomienia, audit trail
- **Loose Coupling** - Moduły komunikują się przez zdarzenia

### Bezpieczeństwo Enterprise Grade
- **Rate Limiting** - Ochrona przed spam/DoS attacks w wyszukiwaniu
- **Comprehensive Audit Trail** - Każda akcja logowana z kontekstem
- **Permission-based Authorization** - Granularne uprawnienia per moduł
- **Security Event Monitoring** - Automatyczne wykrywanie zagrożeń
- **Input Sanitization** - Automatyczna sanityzacja danych wrażliwych

## 💻 Wymagania Systemowe

### Minimalne Wymagania

| Komponent | Wymaganie |
|-----------|-----------|
| **System Operacyjny** | Ubuntu 20.04+ / CentOS 8+ / Debian 11+ |
| **PHP** | 8.2 lub nowszy |
| **Serwer Web** | Apache 2.4+ / Nginx 1.18+ |
| **Baza Danych** | MySQL 8.0+ (domyślnie) / PostgreSQL 13+ / SQLite 3.35+ |
| **Pamięć RAM** | Minimum 512MB, zalecane 2GB+ |
| **Przestrzeń Dyskowa** | Minimum 1GB, zalecane 10GB+ (w tym miejsce na avatary, załączniki asekuracji, backupy) |
| **PHP Extensions** | mysql, pdo, intl, mbstring, xml, curl, gd, ldap |
| **Narzędzia systemowe** | mysqldump (dla AdminService database backups) |

### Zalecane Wymagania Produkcyjne

| Komponent | Zalecane |
|-----------|----------|
| **CPU** | 2+ rdzenie |
| **RAM** | 4GB+ |
| **Storage** | SSD 10GB+ |
| **PHP OPcache** | Włączony |
| **HTTPS** | Certyfikat SSL/TLS |
| **Backup** | Automatyczne kopie zapasowe |

## 🚀 Instalacja

### Metoda 1: Instalacja z Kreatoriem (Zalecana)

#### Wariant A: Ubuntu 22.04 LTS

1. **Przygotowanie Serwera Ubuntu 22.04**
   ```bash
   # Aktualizacja systemu
   sudo apt update && sudo apt upgrade -y
   
   # Instalacja PHP 8.2 i rozszerzeń
   sudo apt install -y software-properties-common
   sudo add-apt-repository ppa:ondrej/php
   sudo apt update
   sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-common \
     php8.2-mysql php8.2-pdo php8.2-intl php8.2-mbstring \
     php8.2-xml php8.2-curl php8.2-gd php8.2-zip php8.2-opcache \
     php8.2-ldap
   ```

2. **Instalacja MySQL i Serwera Web (Apache)**
   ```bash
   # Instalacja MySQL
   sudo apt install -y mysql-server
   sudo systemctl enable mysql
   sudo systemctl start mysql
   
   # Zabezpieczenie instalacji MySQL
   sudo mysql_secure_installation
   
   # Utworzenie bazy danych i użytkownika
   sudo mysql -e "CREATE DATABASE assethub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   sudo mysql -e "CREATE USER 'assethub'@'localhost' IDENTIFIED BY 'secure_password';"
   sudo mysql -e "GRANT ALL PRIVILEGES ON assethub.* TO 'assethub'@'localhost';"
   sudo mysql -e "FLUSH PRIVILEGES;"
   
   # Instalacja Apache
   sudo apt install -y apache2
   
   # Włączenie modułów
   sudo a2enmod rewrite
   sudo a2enmod php8.2
   
   # Uruchomienie usług
   sudo systemctl enable apache2
   sudo systemctl start apache2
   ```

3. **Instalacja Composera**
   ```bash
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   sudo chmod +x /usr/local/bin/composer
   ```

4. **Pobranie i Instalacja AssetHub**
   ```bash
   # Przejście do katalogu web
   cd /var/www
   
   # Klonowanie repozytorium
   sudo git clone https://github.com/cycu85/assethub.git
   sudo chown -R www-data:www-data assethub
   cd assethub
   
   # Konfiguracja środowiska
   # Skopiuj szablon i dostosuj do swoich potrzeb:
   sudo -u www-data cp .env.example .env
   sudo -u www-data nano .env
   # Zmień DATABASE_URL na: mysql://assethub:secure_password@localhost:3306/assethub
   # WAŻNE: Plik .env zawiera hasła i NIE jest w git!
   
   # Instalacja zależności
   sudo -u www-data composer install --no-dev --optimize-autoloader
   sudo -u www-data composer require symfony/asset
   
   # Utworzenie struktury bazy danych
   sudo -u www-data php bin/console doctrine:database:create
   sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction
   
   # Załadowanie przykładowych danych z nowymi przeglądami
   sudo -u www-data php bin/console doctrine:fixtures:load --no-interaction
   
   # Ustawienie uprawnień
   sudo chmod -R 755 var/
   sudo chmod -R 777 var/cache var/log
   
   # Tworzenie katalogów logów (system automatycznie utworzy pliki logów)
   sudo -u www-data mkdir -p var/log
   
   # Tworzenie katalogów dla uploads i backupów
   sudo -u www-data mkdir -p public/uploads/avatars
   sudo -u www-data mkdir -p public/uploads/reviews
   sudo -u www-data mkdir -p public/uploads/asekuracja/equipment
   sudo -u www-data mkdir -p public/uploads/asekuracja/sets
   sudo -u www-data mkdir -p var/backups
   sudo chmod 755 public/uploads/avatars public/uploads/reviews public/uploads/asekuracja var/backups
   sudo chmod 755 public/uploads/asekuracja/equipment public/uploads/asekuracja/sets
   sudo chown -R www-data:www-data public/uploads var/backups
   ```

5. **Konfiguracja Apache**
   ```bash
   # Utworzenie pliku konfiguracyjnego
   sudo tee /etc/apache2/sites-available/assethub.conf > /dev/null <<EOF
   <VirtualHost *:80>
       ServerName your-domain.com
       DocumentRoot /var/www/assethub/public
       
       <Directory /var/www/assethub/public>
           AllowOverride All
           Require all granted
           DirectoryIndex index.php
       </Directory>
       
       ErrorLog \${APACHE_LOG_DIR}/assethub_error.log
       CustomLog \${APACHE_LOG_DIR}/assethub_access.log combined
   </VirtualHost>
   EOF
   
   # Aktywacja strony
   sudo a2ensite assethub.conf
   sudo a2dissite 000-default.conf
   sudo systemctl reload apache2
   ```

6. **Uruchomienie Kreatora Instalacji**
   - Otwórz przeglądarkę i przejdź do: `http://your-domain.com/install`
   - Postępuj zgodnie z instrukcjami kreatora:
     - **Krok 1**: Ekran powitalny
     - **Krok 2**: Sprawdzenie wymagań systemowych
     - **Krok 3**: Konfiguracja bazy danych (opcjonalnie z danymi przykładowymi)
     - **Krok 4**: Utworzenie konta administratora
     - **Krok 5**: Zakończenie instalacji

#### Wariant B: Ubuntu 24.04 LTS (Noble Numbat)

Ubuntu 24.04 zawiera nowsze wersje pakietów i niektóre zmiany w konfiguracji:

1. **Przygotowanie Serwera Ubuntu 24.04**
   ```bash
   # Aktualizacja systemu
   sudo apt update && sudo apt upgrade -y
   
   # Instalacja PHP 8.3 i rozszerzeń (Ubuntu 24.04 domyślnie)
   sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-common \
     php8.3-mysql php8.3-pdo php8.3-intl php8.3-mbstring \
     php8.3-xml php8.3-curl php8.3-gd php8.3-zip php8.3-opcache \
     php8.3-ldap php8.3-bcmath php8.3-readline
   ```

2. **Instalacja MySQL i Apache**
   ```bash
   # Instalacja MySQL 8.0 (domyślnie w Ubuntu 24.04)
   sudo apt install -y mysql-server
   sudo systemctl enable mysql
   sudo systemctl start mysql
   
   # Zabezpieczenie instalacji MySQL
   sudo mysql_secure_installation
   
   # Utworzenie bazy danych i użytkownika
   sudo mysql -e "CREATE DATABASE assethub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   sudo mysql -e "CREATE USER 'assethub'@'localhost' IDENTIFIED BY 'secure_password';"
   sudo mysql -e "GRANT ALL PRIVILEGES ON assethub.* TO 'assethub'@'localhost';"
   sudo mysql -e "FLUSH PRIVILEGES;"
   
   # Instalacja Apache
   sudo apt install -y apache2
   
   # Włączenie modułów (PHP 8.3)
   sudo a2enmod rewrite
   sudo a2enmod php8.3
   
   # Uruchomienie usług
   sudo systemctl enable apache2
   sudo systemctl start apache2
   ```

3. **Instalacja Composer (najnowsza wersja)**
   ```bash
   # Pobranie najnowszego Composera
   php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
   php -r "if (hash_file('sha384', 'composer-setup.php') === file_get_contents('https://composer.github.io/installer.sig')) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
   php composer-setup.php
   php -r "unlink('composer-setup.php');"
   sudo mv composer.phar /usr/local/bin/composer
   sudo chmod +x /usr/local/bin/composer
   ```

4. **Pobranie i Konfiguracja AssetHub**
   ```bash
   # Przejście do katalogu web
   cd /var/www
   
   # Klonowanie repozytorium
   sudo git clone https://github.com/cycu85/assethub.git
   sudo chown -R www-data:www-data assethub
   cd assethub
   
   # Konfiguracja środowiska
   sudo -u www-data cp .env.example .env
   sudo -u www-data nano .env
   # Zmień DATABASE_URL na: mysql://assethub:secure_password@localhost:3306/assethub
   
   # Instalacja zależności z PHP 8.3
   sudo -u www-data composer install --no-dev --optimize-autoloader
   sudo -u www-data composer require symfony/asset
   
   # Utworzenie struktury bazy danych
   sudo -u www-data php bin/console doctrine:database:create
   sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction
   
   # Ustawienie uprawnień (Ubuntu 24.04)
   sudo chmod -R 755 var/
   sudo chmod -R 775 var/cache var/log
   sudo chown -R www-data:www-data var/
   
   # Tworzenie katalogów
   sudo -u www-data mkdir -p var/log public/uploads/avatars public/uploads/reviews var/backups
   sudo -u www-data mkdir -p public/uploads/asekuracja/equipment public/uploads/asekuracja/sets
   sudo chmod 755 public/uploads/avatars public/uploads/reviews public/uploads/asekuracja var/backups
   sudo chmod 755 public/uploads/asekuracja/equipment public/uploads/asekuracja/sets
   ```

5. **Konfiguracja Apache dla Ubuntu 24.04**
   ```bash
   # Utworzenie pliku konfiguracyjnego
   sudo tee /etc/apache2/sites-available/assethub.conf > /dev/null <<EOF
   <VirtualHost *:80>
       ServerName your-domain.com
       DocumentRoot /var/www/assethub/public
       
       <Directory /var/www/assethub/public>
           AllowOverride All
           Require all granted
           DirectoryIndex index.php
           
           # Dodatkowe zabezpieczenia dla Ubuntu 24.04
           <FilesMatch "\.php$">
               SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
           </FilesMatch>
       </Directory>
       
       # Ulepszony logging
       ErrorLog \${APACHE_LOG_DIR}/assethub_error.log
       CustomLog \${APACHE_LOG_DIR}/assethub_access.log combined
       LogLevel info ssl:warn
   </VirtualHost>
   EOF
   
   # Włączenie proxy_fcgi dla FPM (Ubuntu 24.04)
   sudo a2enmod proxy_fcgi setenvif
   sudo a2enconf php8.3-fpm
   
   # Aktywacja strony
   sudo a2ensite assethub.conf
   sudo a2dissite 000-default.conf
   sudo systemctl reload apache2
   
   # Restart PHP-FPM
   sudo systemctl restart php8.3-fpm
   ```

6. **Uruchomienie Kreatora Instalacji**
   - Procedura identyczna jak dla Ubuntu 22.04
   - Przejdź do: `http://your-domain.com/install`

### Metoda 2: Instalacja Manualna

1. **Utworzenie Pliku .env**
   ```bash
   cp .env.example .env
   ```

2. **Edycja Konfiguracji**
   ```bash
   # Skopiuj szablon i dostosuj do swoich potrzeb
   cp .env.example .env
   ```
   
   ```env
   # .env - NIGDY NIE COMMITUJ TEGO PLIKU!
   APP_ENV=prod
   APP_SECRET=your-secret-key-here
   DATABASE_URL=mysql://assethub:secure_password@localhost:3306/assethub
   MAILER_DSN=smtp://localhost
   ```
   
   **⚠️ BEZPIECZEŃSTWO:** Plik `.env` zawiera wrażliwe dane i NIE powinien być w git!

3. **Utworzenie Bazy Danych**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate --no-interaction
   php bin/console doctrine:fixtures:load --no-interaction
   ```

4. **Załadowanie Przykładowych Danych (opcjonalne)**
   ```bash
   # Załaduj kompletne dane przykładowe z modułem Asekuracja
   php bin/console doctrine:fixtures:load --no-interaction
   ```

5. **Utworzenie Użytkownika Administratora** (jeśli nie używasz fixtures)
   ```bash
   php bin/console app:create-admin
   ```

## ⚙️ Konfiguracja

### Konfiguracja Bazy Danych

#### MySQL (Domyślna)
```env
DATABASE_URL=mysql://assethub:secure_password@localhost:3306/assethub
```

#### SQLite
```env
DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db
```

#### PostgreSQL
```env
DATABASE_URL=postgresql://username:password@localhost:5432/assethub
```

### Konfiguracja Email

#### SMTP
```env
MAILER_DSN=smtp://user:password@smtp.example.com:587
```

#### Gmail
```env
MAILER_DSN=gmail://username:password@default
```

### Konfiguracja HTTPS

1. **Instalacja Certbot (Let's Encrypt)**
   ```bash
   sudo apt install -y certbot python3-certbot-apache
   sudo certbot --apache -d your-domain.com
   ```

2. **Konfiguracja SSL w Apache**
   ```apache
   <VirtualHost *:443>
       ServerName your-domain.com
       DocumentRoot /var/www/assethub/public
       
       SSLEngine on
       SSLCertificateFile /etc/letsencrypt/live/your-domain.com/fullchain.pem
       SSLCertificateKeyFile /etc/letsencrypt/live/your-domain.com/privkey.pem
       
       # Dodatkowe ustawienia bezpieczeństwa
       Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
       Header always set X-Frame-Options DENY
       Header always set X-Content-Type-Options nosniff
   </VirtualHost>
   ```

### Optymalizacja Wydajności

1. **Konfiguracja OPcache**
   ```ini
   # /etc/php/8.2/apache2/php.ini
   opcache.enable=1
   opcache.memory_consumption=256
   opcache.max_accelerated_files=20000
   opcache.validate_timestamps=0
   ```

2. **Konfiguracja Cache Symfony**
   ```bash
   # Wyczyszczenie i rozgrzanie cache
   php bin/console cache:clear --env=prod
   php bin/console cache:warmup --env=prod
   ```

## 📚 Użytkowanie

### Pierwsze Kroki

1. **Logowanie do Systemu**
   - Przejdź do głównej strony aplikacji
   - Zaloguj się używając danych administratora:
   
   **👤 Domyślne konta (po załadowaniu fixtures):**
   - `admin`/`admin123` - Administrator (wszystkie moduły + Asekuracja)
   - `user`/`user123` - Użytkownik podstawowy
   - `hr`/`hr123` - Użytkownik HR

2. **Konfiguracja Modułów**
   - Przejdź do Panel Administracyjny → Moduły
   - Aktywuj potrzebne moduły (domyślnie: Admin, Sprzęt, Asekuracja)

3. **Dodawanie Użytkowników**
   - Panel Administracyjny → Użytkownicy → Dodaj użytkownika
   - Przypisz odpowiednie role do modułów

4. **Konfiguracja Kategorii Sprzętu**
   - Panel Administracyjny → Kategorie Sprzętu
   - Dodaj kategorie odpowiadające Twojemu inwentarzowi

5. **Monitoring Systemu**
   - Panel Administracyjny → Logi
   - Przeglądaj logi aktywności użytkowników i operacji systemowych
   - Filtruj logi według dat, poziomów i kategorii

6. **🎨 Konfiguracja Wyglądu Aplikacji**
   - Panel Administracyjny → Ustawienia → Ogólne
   - **Zmiana nazwy aplikacji** - wyświetlana w całym systemie
   - **Upload logo firmy** - formaty: JPG, PNG, GIF, WebP, SVG (max 2MB)
   - **Zaawansowana konfiguracja kolorów** - niezależne ustawienia dla:
     - **Główny kolor aplikacji** - przycisiki, linki, elementy UI
     - **Kolor tła menu bocznego** - tło całego menu nawigacyjnego
     - **Kolor tekstu w menu** - kolor wszystkich pozycji menu
     - **Kolor aktywnego elementu** - wyróżnienie zaznaczonej pozycji menu
   - **Dual input system** - każdy kolor można ustawić:
     - Color picker (wizualny selektor kolorów)
     - Pole tekstowe HEX (ręczne wpisywanie, np. #ff0000, #abc)
   - **Podgląd na żywo** - wszystkie zmiany widoczne natychmiast w prawym panelu z podglądem menu
   - **Synchronizacja dwukierunkowa** - color picker ↔ pole tekstowe
   - **Inteligentna walidacja** - automatyczne poprawki formatu HEX (dodawanie #, rozszerzanie z 3 do 6 znaków)
   - **Reset do domyślnych** - przycisk przywracający wszystkie ustawienia z modalem potwierdzenia:
     - AssetHub, #405189, #2a3042, #ffffff, #405189, logo domyślne

7. **🛡️ Testowanie modułu Asekuracja**
   Po zalogowaniu jako `admin` sprawdź następujące funkcjonalności:
   
   - **`/asekuracja/`** - Dashboard modułu z kartami sprzętu i statystykami przeglądów
   - **`/asekuracja/equipment/`** - Lista sprzętu asekuracyjnego (3 przykładowe elementy)
     - Szelki robocze Petzl AVAO (ASK-001-2024)
     - Lina dynamiczna Edelrid Boa (ASK-002-2024)  
     - Kask Black Diamond Vector (ASK-003-2024)
   - **`/asekuracja/equipment-sets/`** - Zestawy sprzętu (1 przykładowy zestaw podstawowy)
   - **`/asekuracja/reviews/`** - System przeglądów z modalami zakończenia
   - **`/asekuracja/reviews/new`** - Tworzenie nowego przeglądu z wyborem sprzętu/zestawu
   
   **🔧 Testowanie funkcjonalności:**
   - Utwórz nowy przegląd dla sprzętu
   - Wyślij przegląd (zmiana statusu na "in_review") 
   - Zakończ przegląd przez modal z załącznikami
   - Sprawdź odnośniki w widoku sprzętu do historii przeglądów

8. **🔗 Integracja LDAP/Active Directory**
   - Panel Administracyjny → Ustawienia → LDAP
   - **Konfiguracja serwera** - host, port, szyfrowanie (SSL/TLS/StartTLS)
   - **Uwierzytelnianie** - Bind DN użytkownika serwisowego i hasło
   - **Wyszukiwanie** - Base DN i filtr użytkowników LDAP
   - **Mapowanie pól** - dopasowanie atrybutów LDAP do pól użytkownika
   - **Testowanie połączenia** - weryfikacja konfiguracji z podglądem użytkowników
   - **Synchronizacja istniejących** - aktualizacja danych użytkowników z LDAP
   - **Synchronizacja nowych** - automatyczne tworzenie kont z katalogu
   - **Wsparcie dla** - Active Directory, OpenLDAP, Azure AD Domain Services
   - **Bezpieczeństwo** - szyfrowane połączenia i bezpieczne przechowywanie haseł

### Zarządzanie Sprzętem

1. **Dodawanie Sprzętu**
   ```
   Sprzęt → Dodaj sprzęt
   - Wprowadź numer inwentarzowy
   - Wybierz kategorię
   - Wypełnij dane techniczne
   - Dodaj dokumentację
   ```

2. **Przypisywanie Sprzętu**
   ```
   Sprzęt → [Wybierz sprzęt] → Edytuj
   - Wybierz użytkownika z listy
   - Zmień status na "W użyciu"
   - System automatycznie utworzy log aktywności
   ```

3. **Harmonogram Przeglądów**
   ```
   Sprzęt → [Wybierz sprzęt] → Edytuj
   - Ustaw "Następny przegląd"
   - System będzie wysyłał przypomnienia
   ```

### Zarządzanie Użytkownikami i Rolami

1. **Struktura Ról**
   ```
   ADMIN - pełny dostęp do panelu administracyjnego
   EQUIPMENT_MANAGER - zarządzanie sprzętem
   EQUIPMENT_USER - tylko podgląd sprzętu
   ```

2. **Tworzenie Niestandardowych Ról**
   ```
   Panel Administracyjny → Role → Dodaj rolę
   - Wybierz moduł
   - Ustaw uprawnienia (VIEW, CREATE, EDIT, DELETE)
   - Opisz rolę
   ```

## 🎨 System Dynamicznego CSS

### Dynamiczna Kolorystyka
System oferuje zaawansowaną dynamiczną zmianę kolorystyki aplikacji:

#### Architektura CSS
- **DynamicCssController** - generuje CSS na podstawie ustawień z bazy danych
- **Route**: `/assets/css/dynamic-theme.css` - automatycznie includowany w każdej stronie
- **Cache**: ETag based caching (1 minuta) dla wydajności
- **CSS Variables**: Nowoczesne zmienne CSS z fallback dla starszych przeglądarek

#### Rozdzielone Kolory Menu
```css
/* Niezależne kolory dla różnych elementów menu */
:root {
    --vz-vertical-menu-bg: #2a3042;           /* Tło menu */
    --vz-vertical-menu-item-color: #ffffff;    /* Tekst menu */
    --vz-vertical-menu-item-active-bg: #405189; /* Tło aktywnego elementu */
}

/* Specyficzność CSS - nadpisywanie app.min.css */
.navbar-menu .navbar-nav .nav-link.active {
    background-color: var(--sidebar-active-color) !important;
    color: var(--sidebar-text-color) !important;
}
```

#### Rozwiązywanie Konfliktów CSS
System wykorzystuje podwójne podejście dla maksymalnej kompatybilności:
1. **CSS Variables** - nowoczesne zmienne CSS dla wszystkich kontekstów (light/dark theme, różne warianty sidebar)
2. **Direct Selectors** - bezpośrednie selektory z `!important` dla nadpisywania zewnętrznych arkuszy (Velzon template)

#### Podgląd na Żywo
- **JavaScript sync** - dwukierunkowa synchronizacja między color picker a polem tekstowym
- **Live preview** - natychmiastowy podgląd w prawym panelu z miniaturą menu
- **Hex validation** - inteligentna walidacja i konwersja formatów kolorów

## 🔌 API i Integracje

### REST API

System oferuje RESTful API dla integracji z zewnętrznymi systemami:

```bash
# Przykłady endpointów
GET /api/equipment - lista sprzętu
POST /api/equipment - dodanie sprzętu
PUT /api/equipment/{id} - aktualizacja sprzętu
DELETE /api/equipment/{id} - usunięcie sprzętu

GET /api/users - lista użytkowników
POST /api/equipment/{id}/assign - przypisanie sprzętu
```

### Autoryzacja API

```http
Authorization: Token your-api-token-here
Content-Type: application/json
```

### Eksport Danych

System umożliwia eksport danych w formatach:
- **CSV** - dla arkuszy kalkulacyjnych
- **PDF** - dla raportów
- **JSON** - dla integracji API

## 🛠️ Rozwój

### Struktura Projektu

```
assethub/
├── config/           # Konfiguracja Symfony
├── migrations/       # Migracje bazy danych
├── public/          # Pliki publiczne (CSS, JS, obrazy)
│   └── uploads/     # Katalogi dla uploads
│       ├── avatars/ # Zdjęcia profilowe użytkowników
│       ├── reviews/ # Załączniki przeglądów (legacy)
│       └── asekuracja/
│           ├── equipment/  # Załączniki sprzętu asekuracyjnego
│           └── sets/      # Załączniki zestawów asekuracyjnych
├── src/
│   ├── Controller/  # Kontrolery
│   ├── Entity/      # Encje Doctrine
│   ├── Form/        # Formularze Symfony
│   ├── Repository/  # Repozytoria danych
│   ├── Service/     # Usługi biznesowe
│   └── AsekuracyjnySPM/  # Moduł Asekuracja
│       ├── Controller/   # Kontrolery modułu
│       ├── Entity/      # Encje: Equipment, Review, ReviewEquipment
│       ├── Repository/  # Repozytoria z zaawansowanymi zapytaniami
│       └── Service/     # Serwisy biznesowe modułu
├── templates/       # Szablony Twig
│   └── asekuracja/  # Szablony modułu Asekuracja
├── tests/          # Testy automatyczne
└── var/            # Cache, logi, sesje, backupy
```

### Środowisko Deweloperskie

1. **Instalacja Zależności Deweloperskich**
   ```bash
   composer install
   ```

2. **Uruchomienie Serwera Deweloperskiego**
   ```bash
   symfony server:start
   ```

3. **Uruchomienie Testów**
   ```bash
   php bin/phpunit
   ```

4. **Analiza Kodu**
   ```bash
   # PHP CS Fixer
   vendor/bin/php-cs-fixer fix
   
   # PHPStan
   vendor/bin/phpstan analyse
   ```

### Dodawanie Nowych Modułów

1. **Utworzenie Encji**
   ```bash
   php bin/console make:entity
   ```

2. **Utworzenie Kontrolera**
   ```bash
   php bin/console make:controller
   ```

3. **Utworzenie Formularza**
   ```bash
   php bin/console make:form
   ```

4. **Migracja Bazy Danych**
   ```bash
   php bin/console make:migration
   php bin/console doctrine:migrations:migrate
   ```

### Testy Jednostkowe Enterprise

System posiada kompleksowe pokrycie testami jednostkowymi:

```bash
# Uruchomienie wszystkich testów
php bin/phpunit

# Testy konkretnej grupy
php bin/phpunit tests/Service/
php bin/phpunit tests/Controller/
```

#### Pokrycie Testami
- **Service Layer** - 100% pokrycie dla wszystkich serwisów biznesowych
- **Controllers** - Testy integracyjne z mock'owanymi serwisami  
- **Authorization** - Pełne testy uprawnień i bezpieczeństwa
- **Audit System** - Weryfikacja logowania wszystkich operacji

#### Struktura Testów
```
tests/
├── Controller/
│   ├── EquipmentControllerTest.php      # ✅ 12 testów
│   ├── DashboardControllerTest.php      # ✅ 7 testów
│   └── SearchControllerTest.php         # ✅ 8 testów
├── Service/
│   ├── AuthorizationServiceTest.php     # ✅ 17 testów
│   ├── AuditServiceTest.php            # ✅ 15 testów
│   ├── EquipmentServiceTest.php        # ✅ 18 testów
│   └── AdminServiceTest.php            # ✅ 12 testów
└── Entity/
    └── {Entity}Test.php
```

### Konwencje Kodowania

- **PSR-12** - Standard kodowania PHP
- **Symfony Best Practices** - Najlepsze praktyki Symfony
- **PHPDoc** - Dokumentacja kodu
- **Type Hints** - Typowanie zmiennych i funkcji

## 🔒 Bezpieczeństwo Enterprise

### Wbudowane Funkcje Bezpieczeństwa

1. **AuthorizationService - Centralizowana Autoryzacja**
   ```php
   // Sprawdzanie uprawnień do modułów
   $this->authorizationService->checkModuleAccess($user, 'equipment');
   $this->authorizationService->checkPermission($user, 'equipment', 'CREATE');
   
   // Granularne uprawnienia
   $canEdit = $this->authorizationService->hasPermission($user, 'equipment', 'EDIT');
   $canEditAny = $this->authorizationService->hasAnyPermission($user, 'equipment', ['EDIT', 'DELETE']);
   ```

2. **AuditService - Kompleksowy System Audytu**
   ```bash
   # Wielopoziomowe logowanie wszystkich operacji
   var/log/app.log          # Akcje użytkowników (INFO)
   var/log/security.log     # Zdarzenia bezpieczeństwa (ERROR)
   var/log/admin.log        # Akcje administracyjne (NOTICE)  
   var/log/database.log     # Operacje bazodanowe (NOTICE/ERROR)
   ```

3. **Rate Limiting**
   - Ochrona wyszukiwania przed spam/DoS attacks
   - Automatyczne logowanie prób przekroczenia limitów
   - Blokowanie na podstawie IP/użytkownika

4. **Security Event Monitoring**
   - Automatyczne wykrywanie podejrzanych działań
   - Logowanie nieudanych prób logowania
   - Monitorowanie zmian uprawnień

### Najlepsze Praktyki

1. **Regularne Aktualizacje**
   ```bash
   # Aktualizacja zależności
   composer update
   
   # Sprawdzenie podatności
   symfony security:check
   ```

2. **Backup Bazy Danych**
   ```bash
   # MySQL
   mysqldump -u assethub -p assethub > backup_$(date +%Y%m%d_%H%M%S).sql
   
   # SQLite (jeśli używasz)
   cp var/data.db var/backup/data_$(date +%Y%m%d_%H%M%S).db
   ```

3. **Monitoring Logów**
   ```bash
   # Logi aplikacji - główny plik logów
   tail -f var/log/prod.log
   
   # Logi specjalistyczne (dostępne od wersji z systemem logowania)
   tail -f var/log/app.log          # Logi aplikacji
   tail -f var/log/security.log     # Logi bezpieczeństwa
   tail -f var/log/equipment.log    # Logi modułu sprzętu
   tail -f var/log/dictionary.log   # Logi systemu słowników
   tail -f var/log/doctrine.log     # Logi bazy danych
   
   # Logi Apache
   tail -f /var/log/apache2/assethub_error.log
   ```

### Zabezpieczenia Serwera

1. **Firewall**
   ```bash
   sudo ufw enable
   sudo ufw allow 22/tcp
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   ```

2. **Automatyczne Aktualizacje**
   ```bash
   sudo apt install unattended-upgrades
   sudo dpkg-reconfigure unattended-upgrades
   ```

## 📊 Monitoring i Analityka

### Metryki Systemowe

System automatycznie zbiera następujące metryki:
- Liczba użytkowników aktywnych
- Wykorzystanie sprzętu
- Koszty eksploatacji
- Częstotliwość awarii

### Raporty

1. **Raport Wykorzystania Sprzętu**
   - Dostępny w: Sprzęt → Raporty
   - Format: PDF, CSV
   - Zakres: miesięczny, kwartalny, roczny

2. **Raport Kosztów**
   - Analiza kosztów zakupu i eksploatacji
   - Amortyzacja sprzętu
   - Prognozy budżetowe

## 🔐 Bezpieczeństwo i Konfiguracja

### Pliki Środowiskowe (.env)

**⚠️ WAŻNE:** Projekt używa plików `.env` do konfiguracji wrażliwych danych.

#### ✅ **Prawidłowa konfiguracja:**
```bash
# 1. Skopiuj szablon
cp .env.example .env

# 2. Edytuj plik .env z własnymi danymi
nano .env

# 3. Ustaw bezpieczne wartości:
APP_SECRET=generate-random-32-char-string
DATABASE_URL=mysql://user:password@localhost/dbname
```

#### ❌ **NIGDY nie commituj:**
- `.env` - zawiera hasła produkcyjne
- `.env.local` - lokalne nadpisania
- `.env.prod` - ustawienia produkcyjne

#### ✅ **Bezpieczne do git:**
- `.env.example` - szablon bez haseł
- `config/packages/` - konfiguracje bez sekretów

#### 🛡️ **Dodatkowe zabezpieczenia:**
- Plik `.env` jest w `.gitignore`
- Używaj różnych haseł dla każdego środowiska
- Regularnie zmieniaj `APP_SECRET` w produkcji
- Nie udostępniaj plików `.env` przez email/chat

## 🤝 Wsparcie

### Dokumentacja

- **Wiki**: [github.com/cycu85/assethub/wiki](https://github.com/cycu85/assethub/wiki)
- **API Docs**: [your-domain.com/api/docs](http://your-domain.com/api/docs)
- **FAQ**: [github.com/cycu85/assethub/wiki/FAQ](https://github.com/cycu85/assethub/wiki/FAQ)

### Zgłaszanie Problemów

1. **GitHub Issues**: [github.com/cycu85/assethub/issues](https://github.com/cycu85/assethub/issues)
2. **Email Support**: support@your-domain.com
3. **Community Forum**: [forum.your-domain.com](http://forum.your-domain.com)

### Szablony Zgłoszeń

#### Bug Report
```markdown
**Opis problemu**
Krótki opis tego, co nie działa

**Kroki do odtworzenia**
1. Przejdź do...
2. Kliknij na...
3. Zobacz błąd

**Oczekiwane zachowanie**
Co powinno się stać

**Środowisko**
- OS: [Ubuntu 22.04]
- PHP: [8.2.10]
- Browser: [Chrome 118]
```

#### Feature Request
```markdown
**Czy Twoja propozycja jest związana z problemem?**
Jasny opis problemu. Np. Frustruje mnie, że...

**Opisz rozwiązanie, które chciałbyś zobaczyć**
Jasny opis tego, co chcesz, żeby się stało.

**Dodatkowy kontekst**
Dodaj inne informacje lub zrzuty ekranu dotyczące prośby o funkcję tutaj.
```

## 🎯 Roadmapa

### Wersja 2.0 (Q2 2024)
- [ ] Moduł BI i zaawansowana analityka
- [ ] Integracja z systemami ERP
- [ ] Aplikacja mobilna (React Native)
- [ ] Multi-tenancy (obsługa wielu firm)

### Wersja 2.1 (Q3 2024)
- [ ] Workflow i procesy zatwierdzania
- [ ] Integracja z systemami IoT
- [ ] Zaawansowane raportowanie
- [ ] API GraphQL

### Wersja 2.2 (Q4 2024)
- [ ] Machine Learning dla predykcji awarii
- [ ] Integracja z chmurą (AWS, Azure, GCP)
- [ ] Elasticsearch dla zaawansowanego wyszukiwania
- [ ] Mikroserwisy

## 🏆 Autorzy i Współtwórcy

### Core Team
- **Główny Deweloper**: Twoje Imię (your.email@domain.com)
- **UI/UX Designer**: Designer Name (designer@domain.com)
- **DevOps Engineer**: DevOps Name (devops@domain.com)

### Contributors
Zobacz pełną listę współtwórców na: [github.com/cycu85/assethub/contributors](https://github.com/cycu85/assethub/contributors)

### Sposób Współpracy

1. **Fork** repozytorium
2. **Utwórz** branch dla funkcjonalności (`git checkout -b feature/AmazingFeature`)
3. **Commit** zmiany (`git commit -m 'Add some AmazingFeature'`)
4. **Push** do branch (`git push origin feature/AmazingFeature`)
5. **Otwórz** Pull Request

## 📄 Licencja

Projekt jest udostępniony na licencji MIT - zobacz plik [LICENSE](LICENSE) dla szczegółów.

```
MIT License

Copyright (c) 2024 AssetHub Project

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction...
```

## 🙏 Podziękowania

- **Symfony** - Framework PHP
- **Bootstrap** - Framework CSS
- **Velzon** - Template administratorski
- **GridJS** - Tabele interaktywne
- **Lord Icons** - Animowane ikony
- **Community** - Za feedback i wsparcie

---

<div align="center">
  <p>Made with ❤️ by AssetHub Team</p>
  <p>
    <a href="https://github.com/cycu85/assethub">GitHub</a> •
    <a href="https://your-domain.com">Website</a> •
    <a href="https://twitter.com/assethub">Twitter</a> •
    <a href="mailto:contact@your-domain.com">Contact</a>
  </p>
</div>