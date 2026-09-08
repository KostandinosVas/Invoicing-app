import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useInvoices } from '../hooks/useInvoices';
import { useCompanies } from '../hooks/useCompanies';
import { PageHeader } from '../components/PageHeader';
import { Button } from '../components/Button';
import { Select } from '../components/Select';
import { StatusBadge } from '../components/StatusBadge';
import { formatCents } from '../lib/money';
import { documentTypeLabels } from '../lib/labels';
import type { InvoiceStatus } from '../types/api';
import tableStyles from '../components/Table.module.css';
import styles from './Customers.module.css';

const STATUS_OPTIONS = [
  { value: 'draft', label: 'Προσχέδιο' },
  { value: 'issued', label: 'Εκδοθέν' },
  { value: 'submitting', label: 'Υποβάλλεται' },
  { value: 'submitted', label: 'Υποβληθέν' },
  { value: 'rejected', label: 'Απορρίφθηκε' },
  { value: 'cancelled', label: 'Ακυρωμένο' },
];

export function Invoices() {
  const [companyId, setCompanyId] = useState<number | undefined>();
  const [status, setStatus] = useState<InvoiceStatus | undefined>();

  const { data: companiesData } = useCompanies();
  const { data, isLoading, isError } = useInvoices({ companyId, status });

  const companies = companiesData?.data ?? [];
  const invoices = data?.data ?? [];

  const companyName = (id: number) =>
    companies.find((c) => c.id === id)?.name ?? '—';

  const action = (
    <Link to="/invoices/new">
      <Button>Νέο παραστατικό</Button>
    </Link>
  );

  return (
    <>
      <PageHeader
        title="Παραστατικά"
        subtitle={`${data?.meta.total ?? 0} εγγραφές`}
        action={action}
      />

      <div className={styles.filters}>
        <Select
          label="Εταιρεία"
          placeholder="Όλες"
          options={companies.map((c) => ({ value: c.id, label: c.name }))}
          value={companyId ?? ''}
          onChange={(e) =>
            setCompanyId(e.target.value ? Number(e.target.value) : undefined)
          }
        />

        <Select
          label="Κατάσταση"
          placeholder="Όλες"
          options={STATUS_OPTIONS}
          value={status ?? ''}
          onChange={(e) =>
            setStatus((e.target.value || undefined) as InvoiceStatus | undefined)
          }
        />
      </div>

      {isLoading && <p>Φόρτωση…</p>}
      {isError && <p>Δεν ήταν δυνατή η φόρτωση των παραστατικών.</p>}

      {!isLoading && !isError && invoices.length === 0 && (
        <p>Δεν υπάρχουν παραστατικά ακόμα.</p>
      )}

      {invoices.length > 0 && (
        <div className={tableStyles.wrapper}>
          <table className={tableStyles.table}>
            <thead>
              <tr>
                <th>Αριθμός</th>
                <th>Τύπος</th>
                <th>Ημερομηνία</th>
                <th>Πελάτης</th>
                <th>Εταιρεία</th>
                <th style={{ textAlign: 'right' }}>Σύνολο</th>
                <th>Κατάσταση</th>
                <th />
              </tr>
            </thead>
            <tbody>
              {invoices.map((invoice) => (
                <tr key={invoice.id}>
                  <td className={tableStyles.numeric}>
                    {invoice.number ?? '—'}
                  </td>
                  <td>{documentTypeLabels[invoice.document_type]}</td>
                  <td className={tableStyles.numeric}>
                    {invoice.issue_date ?? '—'}
                  </td>
                  <td>{invoice.customer.name}</td>
                  <td>{companyName(invoice.company_id)}</td>
                  <td className={tableStyles.numeric}>
                    {formatCents(invoice.totals.total_cents)}
                  </td>
                  <td>
                    <StatusBadge status={invoice.status} />
                  </td>
                  <td>
                    <Link to={`/invoices/${invoice.id}`}>
                      <Button variant="secondary" size="small">
                        Άνοιγμα
                      </Button>
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </>
  );
}