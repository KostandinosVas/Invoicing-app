import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useSubmitInvoice } from '../hooks/useSubmitInvoice';
import { isAxiosError } from 'axios';
import { useInvoice } from '../hooks/useInvoices';
import { useIssueInvoice } from '../hooks/useIssueInvoice';
import { PageHeader } from '../components/PageHeader';
import { Button } from '../components/Button';
import { StatusBadge } from '../components/StatusBadge';
import { formatCents } from '../lib/money';
import { documentTypeLabels } from '../lib/labels';
import tableStyles from '../components/Table.module.css';
import styles from './InvoiceShow.module.css';
import { LinkButton } from '../components/LinkButton';

export function InvoiceShow() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const invoiceId = Number(id);

  const { data: invoice, isLoading, isError } = useInvoice(invoiceId);
  const issueInvoice = useIssueInvoice();
  const submitInvoice = useSubmitInvoice();
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [issueError, setIssueError] = useState<string | null>(null);

  if (isLoading) return <p>Φόρτωση…</p>;

  if (isError || !invoice) {
    return <p>Το παραστατικό δεν βρέθηκε.</p>;
  }

  const isDraft = invoice.status === 'draft';

  async function handleIssue() {
    setIssueError(null);

    try {
      await issueInvoice.mutateAsync(invoiceId);
    } catch (error) {
      if (isAxiosError(error) && error.response?.status === 403) {
        setIssueError('Το παραστατικό δεν μπορεί να εκδοθεί στην τρέχουσα κατάσταση.');
      } else {
        setIssueError('Η έκδοση απέτυχε. Δοκιμάστε ξανά.');
      }
    }
  }


  async function handleSubmit() {
    setSubmitError(null);

    try {
      await submitInvoice.mutateAsync(invoiceId);
    } catch (error) {
      if (isAxiosError(error) && error.response?.status === 403) {
        setSubmitError('Το παραστατικό δεν μπορεί να διαβιβαστεί στην τρέχουσα κατάσταση.');
      } else if (isAxiosError(error) && error.response?.status === 422) {
        setSubmitError('Υπάρχει ήδη απόπειρα διαβίβασης σε εξέλιξη.');
      } else {
        setSubmitError('Η διαβίβαση δεν ξεκίνησε. Δοκιμάστε ξανά.');
      }
    }
  }

  const canSubmit = invoice.status === 'issued' || invoice.status === 'rejected';

    const canCreateCreditNote =
    invoice.document_type === 'invoice' &&
    invoice.status !== 'draft' &&
    invoice.status !== 'cancelled';

  const title = invoice.number
    ? `${documentTypeLabels[invoice.document_type]} #${invoice.number}`
    : `${documentTypeLabels[invoice.document_type]} (προσχέδιο)`;

  return (
    <>
      <PageHeader
        title={title}
        subtitle={
          invoice.related_invoice_id
            ? `Διόρθωση του παραστατικού #${invoice.related_invoice_id}`
            : undefined
        }
        action={
          <div style={{ display: 'flex', gap: 'var(--space-3)' }}>
            <Button variant="secondary" onClick={() => navigate('/invoices')}>
              Πίσω
            </Button>

            {isDraft && (
              <Button onClick={handleIssue} disabled={issueInvoice.isPending}>
                {issueInvoice.isPending ? 'Έκδοση…' : 'Έκδοση'}
              </Button>
            )}

            {canSubmit && (
              <Button onClick={handleSubmit} disabled={submitInvoice.isPending}>
                {submitInvoice.isPending ? 'Διαβίβαση…' : 'Διαβίβαση στο myDATA'}
              </Button>
            )}

            {canCreateCreditNote && (
              <LinkButton
                to={`/invoices/${invoiceId}/credit-notes/new`}
                variant="secondary"
              >
                Έκδοση πιστωτικού
              </LinkButton>
            )}
          </div>

          
        }
      />

      {isDraft && (
        <div className={styles.warning}>
          {invoice.document_type === 'credit_note'
            ? 'Μετά την έκδοση το πιστωτικό παίρνει αριθμό και δεν μπορεί να τροποποιηθεί ή να διαγραφεί.'
            : 'Μετά την έκδοση το παραστατικό παίρνει αριθμό και δεν μπορεί να τροποποιηθεί ή να διαγραφεί. Διορθώσεις γίνονται μόνο με πιστωτικό ή ακυρωτικό.'}
        </div>
      )}

      {issueError && <p className={styles.error}>{issueError}</p>}

      {submitError && <p className={styles.error}>{submitError}</p>}

      {invoice.status === 'submitting' && (
        <div className={styles.warning}>
          Η διαβίβαση βρίσκεται σε εξέλιξη. Η απάντηση της ΑΑΔΕ ενδέχεται να
          καθυστερήσει — ανανεώστε τη σελίδα σε λίγο.
        </div>
      )}


      {invoice.last_submission &&
        ['rejected', 'failed'].includes(invoice.last_submission.status) && (
          <div className={styles.errorBox}>
            <p className={styles.errorTitle}>
              {invoice.last_submission.status === 'rejected'
                ? 'Η ΑΑΔΕ απέρριψε τη διαβίβαση'
                : 'Η διαβίβαση απέτυχε'}
            </p>

            <ul className={styles.errorList}>
              {invoice.last_submission.errors.map((error, index) => (
                <li key={index}>{error}</li>
              ))}
            </ul>

            <p className={styles.errorMeta}>
              Απόπειρα {invoice.last_submission.attempt}
              {invoice.last_submission.completed_at &&
                ` · ${new Date(invoice.last_submission.completed_at).toLocaleString('el-GR')}`}
            </p>
          </div>
        )}

      <div className={styles.grid}>
        <section className={styles.card}>
          <h2 className={styles.cardTitle}>Γραμμές</h2>

          <table className={tableStyles.table}>
            <thead>
              <tr>
                <th>#</th>
                <th>Περιγραφή</th>
                <th style={{ textAlign: 'right' }}>Ποσότητα</th>
                <th style={{ textAlign: 'right' }}>Τιμή</th>
                <th style={{ textAlign: 'right' }}>ΦΠΑ</th>
                <th style={{ textAlign: 'right' }}>Σύνολο</th>
              </tr>
            </thead>
            <tbody>
              {(invoice.lines ?? []).map((line) => (
                <tr key={line.id}>
                  <td className={tableStyles.numeric}>{line.position}</td>
                  <td>{line.description}</td>
                  <td className={tableStyles.numeric}>
                    {line.quantity} {line.unit}
                  </td>
                  <td className={tableStyles.numeric}>
                    {formatCents(line.unit_price_cents)}
                  </td>
                  <td className={tableStyles.numeric}>{line.vat_rate}%</td>
                  <td className={tableStyles.numeric}>
                    {formatCents(line.total_cents)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          <div className={styles.totals}>
            <div className={styles.totalRow}>
              <span className={styles.metaLabel}>Καθαρή αξία</span>
              <span className={styles.totalValue}>
                {formatCents(invoice.totals.net_amount_cents)}
              </span>
            </div>

            <div className={styles.totalRow}>
              <span className={styles.metaLabel}>ΦΠΑ</span>
              <span className={styles.totalValue}>
                {formatCents(invoice.totals.vat_amount_cents)}
              </span>
            </div>

            <div className={`${styles.totalRow} ${styles.grandTotal}`}>
              <span>Σύνολο</span>
              <span className={styles.totalValue}>
                {formatCents(invoice.totals.total_cents)}
              </span>
            </div>
          </div>
        </section>

                <aside>
          <section className={styles.card}>
            <h2 className={styles.cardTitle}>Στοιχεία</h2>

            <div className={styles.meta}>
              <span className={styles.metaLabel}>Κατάσταση</span>
              <span>
                <StatusBadge status={invoice.status} />
              </span>

              <span className={styles.metaLabel}>Αριθμός</span>
              <span>{invoice.number ?? '—'}</span>

              <span className={styles.metaLabel}>Ημερομηνία</span>
              <span>{invoice.issue_date ?? '—'}</span>

              <span className={styles.metaLabel}>ΜΑΡΚ</span>
              <span className={tableStyles.numeric}>
                {invoice.mydata_mark ?? '—'}
              </span>
            </div>
          </section>

          {invoice.related_invoice_id && (
            <section className={styles.card} style={{ marginTop: 'var(--space-4)' }}>
              <h2 className={styles.cardTitle}>Αναφέρεται σε</h2>

              <LinkButton
                to={`/invoices/${invoice.related_invoice_id}`}
                variant="secondary"
                size="small"
              >
                Άνοιγμα αρχικού παραστατικού
              </LinkButton>
            </section>
          )}

          {invoice.corrections && invoice.corrections.length > 0 && (
            <section className={styles.card} style={{ marginTop: 'var(--space-4)' }}>
              <h2 className={styles.cardTitle}>Διορθώσεις</h2>

              <div className={styles.corrections}>
                {invoice.corrections.map((correction) => (
                  <div key={correction.id} className={styles.correctionRow}>
                    <span>
                      {documentTypeLabels[correction.document_type]}
                      {correction.number ? ` #${correction.number}` : ' (προσχέδιο)'}
                    </span>

                    <span className={tableStyles.numeric}>
                      {formatCents(correction.total_cents)}
                    </span>

                    <LinkButton
                      to={`/invoices/${correction.id}`}
                      variant="secondary"
                      size="small"
                    >
                      Άνοιγμα
                    </LinkButton>
                  </div>
                ))}
              </div>
            </section>
          )}

          <section className={styles.card} style={{ marginTop: 'var(--space-4)' }}>
            <h2 className={styles.cardTitle}>Πελάτης</h2>

            <div className={styles.meta}>
              <span className={styles.metaLabel}>Επωνυμία</span>
              <span>{invoice.customer.name}</span>

              <span className={styles.metaLabel}>ΑΦΜ</span>
              <span>{invoice.customer.vat_number ?? '—'}</span>

              <span className={styles.metaLabel}>ΔΟΥ</span>
              <span>{invoice.customer.tax_office ?? '—'}</span>

              <span className={styles.metaLabel}>Διεύθυνση</span>
              <span>
                {invoice.customer.address ?? '—'}
                {invoice.customer.city ? `, ${invoice.customer.city}` : ''}
              </span>
            </div>
          </section>
        </aside>
      </div>
    </>
  );
}