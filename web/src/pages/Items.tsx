import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useItems } from '../hooks/useItems';
import { useCompanies } from '../hooks/useCompanies';
import { PageHeader } from '../components/PageHeader';
import { Button } from '../components/Button';
import { Select } from '../components/Select';
import { formatCents } from '../lib/money';
import tableStyles from '../components/Table.module.css';
import styles from './Customers.module.css';

export function Items() {
  const [companyId, setCompanyId] = useState<number | undefined>(undefined);

  const { data: companiesData } = useCompanies();
  const { data, isLoading, isError } = useItems({ companyId });

  const companies = companiesData?.data ?? [];
  const items = data?.data ?? [];

  const companyName = (id: number) =>
    companies.find((c) => c.id === id)?.name ?? '—';

  const action = (
    <Link to="/items/new">
      <Button>Νέο είδος</Button>
    </Link>
  );

  return (
    <>
      <PageHeader
        title="Είδη & υπηρεσίες"
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
      </div>

      {isLoading && <p>Φόρτωση…</p>}
      {isError && <p>Δεν ήταν δυνατή η φόρτωση των ειδών.</p>}

      {!isLoading && !isError && items.length === 0 && (
        <p>Δεν υπάρχουν είδη ακόμα.</p>
      )}

      {items.length > 0 && (
        <div className={tableStyles.wrapper}>
          <table className={tableStyles.table}>
            <thead>
              <tr>
                <th>Κωδικός</th>
                <th>Περιγραφή</th>
                <th>Εταιρεία</th>
                <th>Μονάδα</th>
                <th style={{ textAlign: 'right' }}>Τιμή</th>
                <th style={{ textAlign: 'right' }}>ΦΠΑ</th>
                <th>Κατάσταση</th>
              </tr>
            </thead>
            <tbody>
              {items.map((item) => (
                <tr key={item.id}>
                  <td className={tableStyles.numeric}>
                    {item.code ?? <span className={tableStyles.muted}>—</span>}
                  </td>
                  <td>{item.name}</td>
                  <td>{companyName(item.company_id)}</td>
                  <td>{item.unit}</td>
                  <td className={tableStyles.numeric}>
                    {formatCents(item.unit_price_cents)}
                  </td>
                  <td className={tableStyles.numeric}>{item.vat_rate}%</td>
                  <td>
                    {item.is_active ? (
                      'Ενεργό'
                    ) : (
                      <span className={tableStyles.muted}>Ανενεργό</span>
                    )}
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