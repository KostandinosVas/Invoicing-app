import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useSeries } from '../hooks/useSeries';
import { useCompanies } from '../hooks/useCompanies';
import { PageHeader } from '../components/PageHeader';
import { Button } from '../components/Button';
import { Select } from '../components/Select';
import { documentTypeLabels } from '../lib/labels';
import tableStyles from '../components/Table.module.css';
import styles from './Customers.module.css';

export function SeriesList() {
  const [companyId, setCompanyId] = useState<number | undefined>(undefined);

  const { data: companiesData } = useCompanies();
  const { data, isLoading, isError } = useSeries({ companyId });

  const companies = companiesData?.data ?? [];
  const series = data?.data ?? [];

  const companyName = (id: number) =>
    companies.find((c) => c.id === id)?.name ?? '—';

  const action = (
    <Link to="/series/new">
      <Button>Νέα σειρά</Button>
    </Link>
  );

  return (
    <>
      <PageHeader
        title="Σειρές αρίθμησης"
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
      {isError && <p>Δεν ήταν δυνατή η φόρτωση των σειρών.</p>}

      {!isLoading && !isError && series.length === 0 && (
        <p>Δεν υπάρχουν σειρές ακόμα.</p>
      )}

      {series.length > 0 && (
        <div className={tableStyles.wrapper}>
          <table className={tableStyles.table}>
            <thead>
              <tr>
                <th>Κωδικός</th>
                <th>Τύπος παραστατικού</th>
                <th>Εταιρεία</th>
                <th style={{ textAlign: 'right' }}>Τελευταίος αριθμός</th>
                <th>Κατάσταση</th>
              </tr>
            </thead>
            <tbody>
              {series.map((s) => (
                <tr key={s.id}>
                  <td className={tableStyles.numeric}>{s.code}</td>
                  <td>{documentTypeLabels[s.document_type]}</td>
                  <td>{companyName(s.company_id)}</td>
                  <td className={tableStyles.numeric}>{s.last_number}</td>
                  <td>
                    {s.is_active ? (
                      'Ενεργή'
                    ) : (
                      <span className={tableStyles.muted}>Ανενεργή</span>
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