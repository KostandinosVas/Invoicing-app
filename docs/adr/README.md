# Architecture Decision Records

Καταγραφή των αποφάσεων που ορίζουν το σύστημα: τι πρόβλημα υπήρχε, τι
εναλλακτικές εξετάστηκαν, τι επιλέχθηκε και τι κόστισε.

Τα ADR δεν ενημερώνονται. Αν μια απόφαση αλλάξει, γράφεται νέο που δηλώνει
ποιο αντικαθιστά.

| #                                     | Απόφαση                                                | Κατάσταση |
| ------------------------------------- | ------------------------------------------------------ | --------- |
| [0001](0001-tenancy-model.md)         | Multi-tenancy με shared schema και cross-company reads | Αποδεκτό  |
| [0002](0002-money-representation.md)  | Χρήμα σε ακέραια λεπτά με Money value object           | Αποδεκτό  |
| [0003](0003-append-only-documents.md) | Τα παραστατικά είναι append-only                       | Αποδεκτό  |
| [0004](0004-invoice-state-machine.md) | Κύκλος ζωής παραστατικού ως state machine              | Αποδεκτό  |
| [0005](0005-gapless-numbering.md)     | Αρίθμηση χωρίς κενά με pessimistic locking             | Αποδεκτό  |
| [0006](0006-spa-authentication.md)    | Sanctum SPA authentication με session cookies          | Αποδεκτό  |

## Δομή

Κάθε ADR ακολουθεί: **Context** (το πρόβλημα) → **Options** (τι εξετάστηκε) →
**Decision** (τι επιλέχθηκε) → **Consequences** (τι κερδίθηκε, τι πληρώθηκε).
