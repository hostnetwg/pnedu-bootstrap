# Zaświadczenia — pnedu.pl

Dokumentacja **frontu** (pnedu). Kanon architektury i szablonów: **`pneadm/docs/CERTIFICATES.md`**.

Ostatnia aktualizacja: **2026-11-02** (przepływ linku tokenowego).

---

## Zasada

**pnedu nie generuje PDF lokalnie** — wywołuje API **adm.pnedu.pl** (`CertificateApiClient`) z tokenem `PNEADM_API_TOKEN`.

---

## Konfiguracja `.env`

```env
PNEADM_API_URL=https://adm.pnedu.pl
PNEADM_API_TOKEN=<ten-sam-token-co-w-pneadm>
# opcjonalnie miniatury / storage adm:
# PNEADM_PUBLIC_URL=https://adm.pnedu.pl
```

Po zmianie: `sail artisan config:clear`.

---

## Szkolenia klasyczne (`courses`)

| Trasa | Kontroler | Opis |
|-------|-----------|------|
| `GET /dashboard/zaswiadczenia` | `CertificateController@dashboardCertificatesIndex` | Lista szkoleń użytkownika |
| `GET /dashboard/zaswiadczenia/{course}` | `dashboardCertificateShow` | Strona certyfikatu. Pokazuje **imię i nazwisko uczestnika** (`participants`), dla którego powstanie PDF — na formularzu danych urodzenia, na podglądzie i na stronie „Trwa pobieranie…”. |
| `GET /dashboard/zaswiadczenia/{course}/download` | `dashboardCertificateDownload` | Pobranie PDF |
| `GET /certificates/{token}` | `showListByToken` | Lista bez logowania |
| `GET /certificate/{token}/{course}/download` | `downloadByToken` | PDF bez logowania |

Szczegóły tokenów: `pneadm/docs/CERTIFICATE_DOWNLOAD_LINKS.md`.

### Przepływ `/certificate/{token}/{course}`

1. Zalogowany użytkownik z tym samym, zweryfikowanym e-mailem → przekierowanie na `/dashboard/zaswiadczenia/{course}`; zalogowany na inne konto → 403 (`certificates.errors.wrong-account-for-link`).
2. `courses.certificate_download_status` musi być `download_enabled` (inaczej 403).
3. Formularz daty i miejsca urodzenia (`certificates.birth-data-form`) pokazuje się, gdy szkolenie jest płatne (`courses.is_paid`) i brakuje danych albo jest `?edit=1`. Dla bezpłatnych pola są opcjonalne. Wymóg wynika z rejestru zaświadczeń, **nie** z ustawienia szablonu (`show_birth_info`).
4. Zapis → API `POST /api/participants/update-birth-data` (kasuje istniejący PDF, żeby następne pobranie miało nowe dane).
5. Pobranie → `ensure` → `data` → `generate` (`save_to_storage=true`) → `mark-downloaded`. Jeśli plik PDF już istnieje w adm, API zwraca go bez ponownego renderowania.

Jak szablon zamienia się w PDF (bloki, daty, znane problemy): `pneadm/docs/CERTIFICATE_TEMPLATES.md`.

---

## Kursy online

| Trasa | Kontroler | Opis |
|-------|-----------|------|
| `GET /dashboard/kursy-online` | `DashboardOnlineCoursesController@index` | Lista zapisów i kart oczekujących na dostęp / płatność |
| `POST /dashboard/kursy-online/oczekujace/{ident}/rezygnacja` | `resignPending` | Rezygnacja uczestnika z nieopłaconego zamówienia online |
| `GET /dashboard/kursy-online/{enrollment}` | `show` | Strona kursu |
| `GET /dashboard/kursy-online/{enrollment}/zaswiadczenie` | `OnlineCourseCertificateController@show` | Profil + podgląd |
| `POST …/zaswiadczenie/profil` | `updateProfile` | Dane urodzenia (profil) |
| `GET …/zaswiadczenie/pobierz` | `download` | ensure + generate PDF via API |

### Warunki pobrania

1. `online_courses.certificate_download_status = download_enabled`
2. E-mail zapisu (`online_course_enrollments.email`) = e-mail zalogowanego użytkownika
3. Jeśli włączone `certificate_birth_data_required` — komplet profilu (`UserCertificateProfileService`)

### Serwisy

| Klasa | Rola |
|-------|------|
| `CertificateApiClient` | HTTP do pneadm (`ensure`, `generate`, …) |
| `OnlineCourseCertificateService` | Kontekst UI (czy pokazać CTA, linki) |
| `UserCertificateProfileService` | Imię, nazwisko, data/miejsce urodzenia z konta |

Import CSV w panelu adm nadaje tylko dostęp (e-mail, imię, telefon, ID Publigo). Na PDF przy pobraniu przez uczestnika i tak idą dane z konta pnedu.pl.

Model `Certificate` i `OnlineCourse` — connection `pneadm`.

---

## Pliki

- `app/Http/Controllers/OnlineCourseCertificateController.php`
- `app/Http/Controllers/CertificateController.php`
- `app/Services/CertificateApiClient.php`
- `app/Services/OnlineCourseCertificateService.php`
- `app/Services/UserCertificateProfileService.php`
- `resources/views/dashboard/online-courses/` (w tym `certificate-cta`, `certificate-profile-form`)
- `tests/Unit/UserCertificateProfileServiceTest.php`

---

## Wdrożenie

Brak migracji certyfikatów w bazie **pnedu** (dane w **pneadm**).  
Checklist produkcji: `pneadm/docs/deploy/2026-07-online-certificates-production-deploy.md`.

---

## Dokumenty historyczne (root projektu)

Starsze pliki `CERTIFICATE_*.md` w katalogu głównym opisują wcześniejsze etapy (pakiet `pne-certificate-generator`, poprawki logo itd.) — **nie zastępują** tego dokumentu ani kanonu w pneadm.
