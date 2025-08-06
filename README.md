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

AssetHub to nowoczesny system zarządzania zasobami firmy, zaprojektowany z myślą o przedsiębiorstwach potrzebujących efektywnego kontrolowania swojego majątku. System oferuje modularną architekturę, pozwalającą na aktywację tylko niezbędnych funkcjonalności.

### Główne Cechy

- **🏗️ Architektura Modularna** - Aktywuj tylko potrzebne moduły
- **👥 Zaawansowane Zarządzanie Użytkownikami** - Role i uprawnienia per moduł
- **📊 Kompleksowe Raporty** - Analiza wykorzystania i kosztów
- **🔐 Bezpieczeństwo** - Pełna kontrola dostępu i logi aktywności
- **📱 Responsywny Interfejs** - Optymalizacja dla urządzeń mobilnych
- **🚀 Łatwa Instalacja** - Graficzny kreator instalacji

## ✨ Funkcjonalności

### 🔧 Moduł Sprzętu i Narzędzi
- Inwentaryzacja sprzętu z numerami inwentarzowymi
- Śledzenie lokalizacji i przypisań do użytkowników
- Harmonogram przeglądów i konserwacji
- Historia użytkowania i napraw
- Zarządzanie dokumentacją i certyfikatami

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

### 👨‍💼 Panel Administracyjny
- **Zarządzanie użytkownikami i rolami** - System uprawnień z granularnymi rolami (system_admin, employees_viewer, employees_editor, employees_manager)
- **Konfiguracja modułów systemu** - Aktywacja i zarządzanie modułami aplikacji
- **Generowanie raportów i analiz** - Kompleksowe raporty systemu
- **System logowania** - Kompleksowe logowanie aktywności użytkowników z wielokanałowymi logami
- **Podgląd logów** - Przeglądanie i filtrowanie logów systemowych w panelu administracyjnym
- **System słowników** - Zarządzanie słownikami systemowymi dla wszystkich modułów

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

## 💻 Wymagania Systemowe

### Minimalne Wymagania

| Komponent | Wymaganie |
|-----------|-----------|
| **System Operacyjny** | Ubuntu 20.04+ / CentOS 8+ / Debian 11+ |
| **PHP** | 8.2 lub nowszy |
| **Serwer Web** | Apache 2.4+ / Nginx 1.18+ |
| **Baza Danych** | MySQL 8.0+ (domyślnie) / PostgreSQL 13+ / SQLite 3.35+ |
| **Pamięć RAM** | Minimum 512MB, zalecane 2GB+ |
| **Przestrzeń Dyskowa** | Minimum 1GB, zalecane 10GB+ (w tym miejsce na avatary, backupy bazy danych) |
| **PHP Extensions** | mysql, pdo, intl, mbstring, xml, curl, gd, ldap |
| **Narzędzia systemowe** | mysqldump (dla kopii zapasowych bazy danych) |

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
   sudo mysql -e "CREATE DATABASE myapp2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   sudo mysql -e "CREATE USER 'myapp2'@'localhost' IDENTIFIED BY 'secure_password';"
   sudo mysql -e "GRANT ALL PRIVILEGES ON myapp2.* TO 'myapp2'@'localhost';"
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
   sudo git clone https://github.com/cycu85/myapp2.git
   sudo chown -R www-data:www-data myapp2
   cd myapp2
   
   # Konfiguracja środowiska
   # Skopiuj szablon i dostosuj do swoich potrzeb:
   sudo -u www-data cp .env.example .env
   sudo -u www-data nano .env
   # Zmień DATABASE_URL na: mysql://myapp2:secure_password@localhost:3306/myapp2
   # WAŻNE: Plik .env zawiera hasła i NIE jest w git!
   
   # Instalacja zależności
   sudo -u www-data composer install --no-dev --optimize-autoloader
   sudo -u www-data composer require symfony/asset
   
   # Utworzenie struktury bazy danych
   sudo -u www-data php bin/console doctrine:database:create
   sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction
   
   # Ustawienie uprawnień
   sudo chmod -R 755 var/
   sudo chmod -R 777 var/cache var/log
   
   # Tworzenie katalogów logów (system automatycznie utworzy pliki logów)
   sudo -u www-data mkdir -p var/log
   
   # Tworzenie katalogów dla uploads i backupów
   sudo -u www-data mkdir -p public/uploads/avatars
   sudo -u www-data mkdir -p var/backups
   sudo chmod 755 public/uploads/avatars var/backups
   sudo chown -R www-data:www-data public/uploads/avatars var/backups
   ```

5. **Konfiguracja Apache**
   ```bash
   # Utworzenie pliku konfiguracyjnego
   sudo tee /etc/apache2/sites-available/myapp2.conf > /dev/null <<EOF
   <VirtualHost *:80>
       ServerName your-domain.com
       DocumentRoot /var/www/myapp2/public
       
       <Directory /var/www/myapp2/public>
           AllowOverride All
           Require all granted
           DirectoryIndex index.php
       </Directory>
       
       ErrorLog \${APACHE_LOG_DIR}/myapp2_error.log
       CustomLog \${APACHE_LOG_DIR}/myapp2_access.log combined
   </VirtualHost>
   EOF
   
   # Aktywacja strony
   sudo a2ensite myapp2.conf
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
   DATABASE_URL=mysql://myapp2:secure_password@localhost:3306/myapp2
   MAILER_DSN=smtp://localhost
   ```
   
   **⚠️ BEZPIECZEŃSTWO:** Plik `.env` zawiera wrażliwe dane i NIE powinien być w git!

3. **Utworzenie Bazy Danych**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate --no-interaction
   php bin/console doctrine:fixtures:load --no-interaction
   ```

4. **Utworzenie Użytkownika Administratora**
   ```bash
   php bin/console app:create-admin
   ```

## ⚙️ Konfiguracja

### Konfiguracja Bazy Danych

#### MySQL (Domyślna)
```env
DATABASE_URL=mysql://myapp2:secure_password@localhost:3306/myapp2
```

#### SQLite
```env
DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db
```

#### PostgreSQL
```env
DATABASE_URL=postgresql://username:password@localhost:5432/myapp2
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
       DocumentRoot /var/www/myapp2/public
       
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
   - Zaloguj się używając danych administratora utworzonych podczas instalacji

2. **Konfiguracja Modułów**
   - Przejdź do Panel Administracyjny → Moduły
   - Aktywuj potrzebne moduły (domyślnie: Admin i Sprzęt)

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

7. **🔗 Integracja LDAP/Active Directory**
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
myapp2/
├── config/           # Konfiguracja Symfony
├── migrations/       # Migracje bazy danych
├── public/          # Pliki publiczne (CSS, JS, obrazy)
├── src/
│   ├── Controller/  # Kontrolery
│   ├── Entity/      # Encje Doctrine
│   ├── Form/        # Formularze Symfony
│   ├── Repository/  # Repozytoria danych
│   └── Service/     # Usługi biznesowe
├── templates/       # Szablony Twig
├── tests/          # Testy automatyczne
└── var/            # Cache, logi, sesje
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

### Konwencje Kodowania

- **PSR-12** - Standard kodowania PHP
- **Symfony Best Practices** - Najlepsze praktyki Symfony
- **PHPDoc** - Dokumentacja kodu
- **Type Hints** - Typowanie zmiennych i funkcji

## 🔒 Bezpieczeństwo

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
   mysqldump -u myapp2 -p myapp2 > backup_$(date +%Y%m%d_%H%M%S).sql
   
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
   tail -f /var/log/apache2/myapp2_error.log
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

- **Wiki**: [github.com/cycu85/myapp2/wiki](https://github.com/cycu85/myapp2/wiki)
- **API Docs**: [your-domain.com/api/docs](http://your-domain.com/api/docs)
- **FAQ**: [github.com/cycu85/myapp2/wiki/FAQ](https://github.com/cycu85/myapp2/wiki/FAQ)

### Zgłaszanie Problemów

1. **GitHub Issues**: [github.com/cycu85/myapp2/issues](https://github.com/cycu85/myapp2/issues)
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
Zobacz pełną listę współtwórców na: [github.com/cycu85/myapp2/contributors](https://github.com/cycu85/myapp2/contributors)

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
    <a href="https://github.com/cycu85/myapp2">GitHub</a> •
    <a href="https://your-domain.com">Website</a> •
    <a href="https://twitter.com/myapp2">Twitter</a> •
    <a href="mailto:contact@your-domain.com">Contact</a>
  </p>
</div>