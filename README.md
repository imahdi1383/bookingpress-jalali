مسیر اجرا روی ویندوز:
---

در پاورشل:

```powershell
wsl -d DDEV
```

بعد داخل WSL:

```bash
cd ~/projects/bookingpress-jalali-dev
ddev start
ddev launch wp-admin/
code .
```
