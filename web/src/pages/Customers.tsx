import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useCustomers } from '../hooks/useCustomers';
import { useCompanies } from '../hooks/useCompanies';
import { PageHeader } from '../components/PageHeader';
import { Button } from '../components/Button';
import { Select } from '../components/Select';
import tableStyles from '../components/Table.module.css';
import styles from './Customers.module.css';

export function Customers() {
  const [companyId, setCompanyId] = useState<number | undefined>(undefined);

  const { data: companiesData } = useCompanies();
  const { data, isLoading, isError } = useCustomers(companyId);

  const companies = companiesData?.data ?? [];
  const customers = data?.data ?? [];

  const companyName = (id: number) =>
    companies.find((c) => c.id === id)?.name ?? '—';

  const action = (
    <Link to="/customers/new">
      <Button>Νέος πελάτης</Button>
    </Link>
  );

  return (
    <>
      <PageHeader
        title="Πελάτες"
        subtitle={`${customers.length} εγγραφές`}
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

      {isError && <p>Δεν ήταν δυνατή η φόρτωση των πελατών.</p>}

      {!isLoading && !isError && customers.length === 0 && (
        <p>Δεν υπάρχουν πελάτες ακόμα.</p>
      )}

      {customers.length > 0 && (
        <div className={tableStyles.wrapper}>
          <table className={tableStyles.table}>
            <thead>
              <tr>
                <th>Επωνυμία</th>
                <th>ΑΦΜ</th>
                <th>Εταιρεία</th>
                <th>Πόλη</th>
              </tr>
            </thead>
            <tbody>
              {customers.map((customer) => (
                <tr key={customer.id}>
                  <td>{customer.name}</td>
                  <td className={tableStyles.numeric}>
                    {customer.vat_number ?? (
                      <span className={tableStyles.muted}>—</span>
                    )}
                  </td>
                  <td>{companyName(customer.company_id)}</td>
                  <td>
                    {customer.city ?? <span className={tableStyles.muted}>—</span>}
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