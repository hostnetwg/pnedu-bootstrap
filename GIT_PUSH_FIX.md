# 🔧 Naprawa problemu z git push

## Problem

GitHub nie akceptuje haseł przy push przez HTTPS - wymaga Personal Access Token (PAT) lub użycia SSH.

## Rozwiązanie

### ✅ Opcja 1: SSH (Zalecane - już skonfigurowane!)

Twoje SSH jest już skonfigurowane i działa. Remote URL został zmieniony z HTTPS na SSH.

**Teraz możesz zrobić push:**

```bash
git push
```

Nie będzie już pytać o hasło - użyje klucza SSH automatycznie.

### Opcja 2: Personal Access Token (jeśli chcesz zostać przy HTTPS)

Jeśli wolisz używać HTTPS, musisz utworzyć Personal Access Token:

1. **Utwórz token na GitHub:**
   - Przejdź do: https://github.com/settings/tokens
   - Kliknij "Generate new token" → "Generate new token (classic)"
   - Nazwa: np. "pnedu-bootstrap"
   - Uprawnienia: zaznacz `repo` (pełny dostęp do repozytoriów)
   - Kliknij "Generate token"
   - **Skopiuj token** (będzie widoczny tylko raz!)

2. **Użyj tokenu jako hasła:**
   ```bash
   git push
   # Username: hostnetwg
   # Password: [wklej tutaj token]
   ```

3. **Lub zapisz token w Git Credential Manager:**
   ```bash
   git config --global credential.helper store
   git push
   # Wpisz token jako hasło - zostanie zapisany
   ```

## Sprawdzenie konfiguracji

```bash
# Sprawdź remote URL
git remote -v

# Powinno pokazać:
# origin  git@github.com:hostnetwg/pnedu-bootstrap.git (fetch)
# origin  git@github.com:hostnetwg/pnedu-bootstrap.git (push)
```

## Test połączenia SSH

```bash
ssh -T git@github.com
# Powinno pokazać: "Hi hostnetwg! You've successfully authenticated..."
```

## ✅ Status

Remote URL został zmieniony na SSH. Możesz teraz zrobić `git push` bez podawania hasła.

---

**Data:** $(date)  
**Status:** ✅ Naprawione - użyj `git push`

