import { Link } from 'react-router-dom';
import { useCompanies } from '../hooks/useCompanies';
import { PageHeader } from '../components/PageHeader';
import { Button } from '../components/Button';
import tableStyles from '../components/Table.module.css';

export function Companies() {
  const { data, isLoading, isError } = useCompanies();

  const action = (
    <Link to="/companies/new">
      <Button>Νέα εταιρεία</Button>
    </Link>
  );

  if (isLoading) {
    return (
      <>
        <PageHeader title="Εταιρείες" action={action} />
        <p>Φόρτωση…</p>
      </>
    );
  }

  if (isError) {
    return (
      <>
        <PageHeader title="Εταιρείες" action={action} />
        <p>Δεν ήταν δυνατή η φόρτωση των εταιρειών.</p>
      </>
    );
  }

  const companies = data?.data ?? [];

  return (
    <>
      <PageHeader
        title="Εταιρείες"
        subtitle={`${companies.length} εγγραφές`}
        action={action}
      />

      {companies.length === 0 ? (
        <p>Δεν υπάρχουν εταιρείες ακόμα.</p>
      ) : (
        <div className={tableStyles.wrapper}>
          <table className={tableStyles.table}>
            <thead>
              <tr>
                <th>Επωνυμία</th>
                <th>ΑΦΜ</th>
                <th>ΔΟΥ</th>
                <th>Πόλη</th>
              </tr>
            </thead>
            <tbody>
              {companies.map((company) => (
                <tr key={company.id}>
                  <td>{company.name}</td>
                  <td className={tableStyles.numeric}>{company.vat_number}</td>
                  <td>{company.tax_office ?? <span className={tableStyles.muted}>—</span>}</td>
                  <td>{company.city ?? <span className={tableStyles.muted}>—</span>}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </>
  );
}