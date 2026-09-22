import { useCompanies } from '../hooks/useCompanies';
import { useAuth } from '../auth/AuthContext';
import { PageHeader } from '../components/PageHeader';
import { LinkButton } from '../components/LinkButton';
import tableStyles from '../components/Table.module.css';

export function Companies() {
  const { data, isLoading, isError } = useCompanies();
  const { user } = useAuth();

  const canWrite = user?.can_write ?? false;
  const canManageCredentials = user?.can_manage_credentials ?? false;

  const action = canWrite ? (
    <LinkButton to="/companies/new">Νέα εταιρεία</LinkButton>
  ) : null;

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
        subtitle={`${data?.meta.total ?? 0} εγγραφές`}
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
                <th>myDATA</th>
                {canManageCredentials && <th />}
              </tr>
            </thead>
            <tbody>
              {companies.map((company) => (
                <tr key={company.id}>
                  <td>{company.name}</td>
                  <td className={tableStyles.numeric}>{company.vat_number}</td>
                  <td>{company.tax_office ?? <span className={tableStyles.muted}>—</span>}</td>
                  <td>{company.city ?? <span className={tableStyles.muted}>—</span>}</td>
                  <td>
                    {company.has_mydata_credentials ? (
                      'Ρυθμισμένο'
                    ) : (
                      <span className={tableStyles.muted}>Χωρίς διαπιστευτήρια</span>
                    )}
                  </td>
                  {canManageCredentials && (
                    <td>
                      <LinkButton
                        to={`/companies/${company.id}/credentials`}
                        variant="secondary"
                        size="small"
                      >
                        Διαπιστευτήρια
                      </LinkButton>
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </>
  );
}