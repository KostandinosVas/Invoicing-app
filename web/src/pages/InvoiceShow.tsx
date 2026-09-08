import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
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

export function InvoiceShow() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const invoiceId = Number(id);

  const { data: invoice, isLoading, isError } = useInvoice(invoiceId);
  const issueInvoice = useIssueInvoice();
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

  const title = invoice.number
    ? `${documentTypeLabels[invoice.document_type]} #${invoice.number}`
    : `${documentTypeLabels[invoice.document_type]} (προσχέδιο)`;

  return (
    <>
      <PageHeader
        title={title}
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
          </div>
        }
      />

      {isDraft && (
        <div className={styles.warning}>
          Μετά την έκδοση το παραστατικό παίρνει αριθμό και δεν μπορεί να
          τροποποιηθεί ή να διαγραφεί. Διορθώσεις γίνονται μόνο με πιστωτικό ή
          ακυρωτικό.
        </div>
      )}

      {issueError && <p className={styles.error}>{issueError}</p>}

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
              <span>{invoice.mydata_mark ?? '—'}</span>
            </div>
          </section>

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