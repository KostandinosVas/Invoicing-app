# ADR-0006: Sanctum SPA authentication με session cookies

**Κατάσταση:** Αποδεκτό
**Ημερομηνία:** 2026-09-05

## Context

Το frontend είναι React SPA, ξεχωριστό από το Laravel API — επιλογή που έγινε
ώστε το API design να είναι ορατό και ελεγχόμενο, αντί να κρύβεται πίσω από
Inertia.

Άρα τα δύο τρέχουν σε διαφορετικά origins: `localhost:5173` και
`localhost:8000` στο development.

Τα δεδομένα είναι φορολογικά: στοιχεία πελατών, ΑΦΜ, ποσά, παραστατικά τρίτων
επιχειρήσεων. Επιπλέον, **ολόκληρο το tenant isolation στηρίζεται στο
`Auth::user()`** — χωρίς authenticated χρήστη το Global Scope δεν φιλτράρει
ποτέ. Το auth δεν είναι ένα ακόμα feature· είναι η προϋπόθεση της απομόνωσης
δεδομένων.

## Options

**Α. API tokens (Sanctum personal access tokens).** Το SPA παίρνει token στο
login και το στέλνει ως `Authorization: Bearer`. Απλό, δουλεύει από παντού —
αλλά το token πρέπει να αποθηκευτεί κάπου προσβάσιμο από JavaScript. Στο
`localStorage`, οποιοδήποτε XSS το εξάγει και παραμένει έγκυρο μέχρι να λήξει.

**Β. JWT.** Ίδιο πρόβλημα αποθήκευσης, συν δυσκολία ανάκλησης: ένα υπογεγραμμένο
token ισχύει μέχρι να λήξει, εκτός αν κρατάς blacklist — που ακυρώνει το
βασικό πλεονέκτημα του stateless.

**Γ. Sanctum SPA authentication με session cookies.**

## Decision

**Γ.** Cookie-based session authentication.

- Το session cookie είναι **`HttpOnly`**: η JavaScript δεν μπορεί να το
  διαβάσει καν. Ένα XSS δεν το εξάγει.
- Το CSRF token είναι ξεχωριστό cookie, **αναγνώσιμο** από JS, και πρέπει να
  επιστρέφεται σε header. Τρίτο site δεν μπορεί να το διαβάσει λόγω same-origin
  policy, άρα δεν μπορεί να πλαστογραφήσει αίτημα.
- Το session ανακαλείται άμεσα από τον server.
- `session()->regenerate()` μετά το login, ως προστασία από session fixation.

Ρυθμίσεις που απαιτούνται και στα δύο άκρα:

- Backend: `statefulApi()` middleware, `SANCTUM_STATEFUL_DOMAINS`,
  `supports_credentials: true` στο CORS με **συγκεκριμένο** `allowed_origins`
  (το wildcard είναι ασυμβίβαστο με credentials).
- Frontend: `withCredentials` **και** `withXSRFToken` στον axios. Το δεύτερο
  είναι απαραίτητο επειδή ο axios στέλνει το CSRF header μόνο για same-origin
  by default.

Το login επιστρέφει γενικό μήνυμα σφάλματος ανεξαρτήτως αιτίας — «λάθος
στοιχεία» και όχι «δεν υπάρχει τέτοιο email», ώστε να μην είναι δυνατή η
απαρίθμηση εγγεγραμμένων χρηστών. Rate limiting `throttle:5,1` κατά brute force.

## Consequences

**Κερδίζουμε:** το credential δεν είναι προσβάσιμο από JavaScript· άμεση
ανάκληση· CSRF protection από το framework.

**Πληρώνουμε:**

- **SPA και API πρέπει να μοιράζονται top-level domain** στην παραγωγή
  (`app.example.gr` / `api.example.gr`). Δεν είναι λύση για third-party
  clients.
- Το state είναι server-side: τα sessions ζουν στο Redis και αποτελούν
  εξάρτηση διαθεσιμότητας.
- Απαιτείται προκαταρκτικό `GET /sanctum/csrf-cookie` πριν το πρώτο POST — ένα
  επιπλέον round trip, και μη προφανές βήμα για όποιον δει το API απ' έξω.
- Αν χρειαστεί mobile app, θα προστεθούν tokens **παράλληλα**. Το Sanctum
  υποστηρίζει και τους δύο μηχανισμούς ταυτόχρονα, οπότε η απόφαση δεν κλείνει
  τον δρόμο.
- Τα tests πρέπει να στέλνουν `Origin` header, αλλιώς το Sanctum θεωρεί το
  request stateless και δεν ξεκινά session.
