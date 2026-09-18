# Invoicing & myDATA

A multi-tenant invoicing application for Greek accounting firms, with
integration to **myDATA** — the Greek tax authority's (AADE) mandatory
e-bookkeeping platform.

Built as a study in backend and system design: the interesting parts are not
the CRUD, but the constraints the domain imposes — gapless legal numbering under
concurrency, append-only documents, exact monetary arithmetic, and idempotent
submission to an external API that can fail mid-flight.

> **Status:** sandbox only. This project submits to AADE's developer
> environment. It is not, and is not intended to be, production accounting
> software.

## Stack

**Backend** — Laravel 13 · PHP 8.4 · PostgreSQL 16 · Redis (queues)
**Frontend** — React 19 · TypeScript · Vite · TanStack Query · React Hook Form + Zod
**Infrastructure** — Docker Compose (nginx, php-fpm, postgres, redis, queue worker, node)
**Quality** — Pest · PHPStan level 8 · Pint · Vitest · GitHub Actions

## Running locally

Requires only Docker. Nothing is installed on the host.

```bash
git clone <repo-url>
cd invoicing-mydata

cp api/.env.example api/.env
docker compose up -d --build

docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed
```

| Service  | URL                   |
| -------- | --------------------- |
| Frontend | http://localhost:5173 |
| API      | http://localhost:8000 |

Sign in with `test@test.gr` / `password`.

The seeder creates three companies, each with numbering series, customers
(with and without VAT numbers), and items across all Greek VAT rates — enough
to issue a mixed-rate invoice end to end.

## Design decisions

The reasoning behind each is recorded in [`docs/adr/`](docs/adr/) (in Greek):
context, options considered, decision, and — crucially — what it cost.

**Money is never a float.** Every monetary value is stored as integer cents
(`bigint`) with a `Money` value object on top, backed by `brick/money`.
Rounding strategy is declared explicitly at every multiplication rather than
happening as a side effect. VAT rates are integer percentages, and VAT belongs
to the _line_, not the invoice — a single Greek invoice can legitimately carry
24%, 13% and 6% simultaneously.

**Documents are append-only.** Once issued, an invoice cannot be edited or
deleted — not even soft-deleted. Corrections are made by issuing a credit note
or cancellation that references the original. This is enforced at three levels:
policy, state machine, and by keeping status/number/totals out of `$fillable`.
The consequence is snapshot fields: an invoice copies the customer's details at
issue time, because a legal document must show what was true then.

**Numbering is gapless, enforced with a row lock.** A tax audit treats a missing
number as a hidden document. `max(number) + 1` breaks under concurrency, and a
Postgres sequence doesn't roll back — a failed transaction would consume a
number and leave a gap. Instead the series row is locked with
`SELECT … FOR UPDATE` inside a transaction, and every check that can fail runs
_before_ a number is consumed.

**Status is a state machine, not a string.** Transitions are explicit and
exhaustive (`match`, not `switch`); anything not in the table throws a
`DomainException`. `submitting` exists as a distinct state so that an invoice
stuck in the queue is distinguishable from one never sent.

**Tenant isolation is a security boundary.** Filtering happens in two separate
layers: a Global Scope that is always on (the boundary), and an optional
`?company_id=` filter (convenience). They are deliberately not the same
mechanism — if the filter breaks, a user sees more than they wanted; if the
scope breaks, it's a data breach.

## myDATA integration

AADE's platform accepts XML, validated against published XSD schemas that change
several times a year. This project targets **v2.0.2** (live since 10 September
2026), and the official schemas are committed under
[`api/resources/mydata/xsd/`](api/resources/mydata/xsd/) — both so that
validation never depends on a network call, and as a record of exactly which
version the payload was built against.

**The payload is verified, not assumed.** The XML builder uses `DOMDocument`
specifically because it allows `schemaValidate()` against the real XSD inside a
test. That turns "I think I wrote it correctly" into "provably valid".

**Submission is asynchronous and idempotent.** This is the same problem as a
payment webhook: you send the request, the response is lost to a timeout, and
you cannot tell whether it went through. Retrying naively files a second invoice
in the company's tax books — and it cannot be deleted. Payment gateways solve
this with an `Idempotency-Key` header they remember for you; myDATA does not, so
the responsibility is ours.

Each attempt is a row in `submissions`, carrying its own key, the exact XML
sent, and the response. Three independent guards prevent a double send: an
existing MARK, an in-flight attempt, and the invoice state machine. The job
itself re-checks before sending, so it can run twice without submitting twice.

**Rejection and failure are different things.** AADE rejecting an invoice is
final — retrying produces the same rejection. A network error is transient and
deserves exponential backoff. Conflating them means either retrying an invalid
document forever, or abandoning a valid one during a brief outage. The
distinction is carried through the HTTP client, the job, the submission status,
and all the way to the error message shown on screen.

## Scope

**In:** companies, customers, items, numbering series, invoice issuing with
per-line VAT, myDATA submission and cancellation (sandbox), credit notes, PDF
generation and email delivery, role-based permissions, audit trail.

**Deliberately out:** accounting entries, trial balances, VAT returns,
inventory, payroll, payment tracking, multi-language, mobile app. These are
decisions, not omissions — an invoicing application is not an accounting suite.

## Known gaps

Listed because they are real, and because a project that claims to be finished
usually isn't.

- **No reconciliation.** If the worker dies after sending but before receiving a
  response, the attempt stays in `sent` and the invoice cannot be resubmitted —
  correctly, since a double filing is worse than a stuck document. The proper
  fix is AADE's `RequestTransmittedDocs`: ask what they already have.
- **401/403 are treated as transient** and trigger pointless retries. Mapping
  HTTP status to permanent vs. retryable is straightforward but was not done.
- **No alerting.** Failures are logged; nobody reads logs.
- **No QR code on the PDF.** AADE returns a `qrUrl` with the submission
  response; without real sandbox credentials that field is always empty, so the
  code could not be tested.
- **No UI for myDATA credentials or for assigning roles.** Both endpoints exist
  and are covered by policies and tests; the screens do not.
- **The audit trail is not surfaced.** `GET /invoices/{id}/activity` returns it;
  nothing displays it. Email delivery is not recorded in it at all.
- **Tenant isolation does not actually restrict anyone.** The Global Scope and
  `accessibleCompanyIds()` are in place as the mechanism, but the latter
  currently returns every company — a deliberate choice for a single accounting
  firm (ADR-0001), and the point at which per-user company access would attach.
- **Some AADE code mappings are unverified.** VAT category and payment method
  are integer ranges in the XSD with no labels; the semantics live only in the
  specification PDF. Values are structurally valid and isolated in enums, so a
  correction is local.
- **No deployment.** The application runs locally only. Notably, the queue
  worker does not pick up new code or config without a restart, which a real
  deployment script would handle with `queue:restart`.

## Testing

```bash
docker compose exec php ./vendor/bin/pest        # 122 backend tests
docker compose exec php ./vendor/bin/phpstan analyse
docker compose exec php ./vendor/bin/pint --test
docker compose exec node npm test                # frontend unit tests
```

Every pull request runs all five checks; none can be merged while red, and
`main` is protected from direct pushes.

Tests that earn their place:

- The generated XML is validated against AADE's official XSD.
- Twenty consecutive issues produce numbers 1–20 with no gaps.
- A failed issue does not consume a number.
- A submission job run twice sends exactly once.
- An invoice keeps its customer snapshot after the customer changes.
- `0.1 + 0.2 !== 0.3` — asserted explicitly, as the reason the `Money` value
  object exists.

- The generated PDF does not fall back to Helvetica — which would silently
  render Greek text as question marks.
- A viewer cannot issue an invoice, and the invoice is left without a number.
- An accountant cannot set myDATA credentials; an admin can.

## Project layout

```
api/     Laravel API
web/     React SPA
docker/  Dockerfiles and nginx config
docs/adr/  Architecture decision records
```
