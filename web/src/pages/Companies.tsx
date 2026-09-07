import { useCompanies } from '../hooks/useCompanies';

export function Companies() {
  const { data, isLoading, isError } = useCompanies();

  if (isLoading) return <p>Φόρτωση εταιρειών…</p>;

  if (isError) return <p>Δεν ήταν δυνατή η φόρτωση των εταιρειών.</p>;

  if (!data || data.data.length === 0) {
    return <p>Δεν υπάρχουν εταιρείες ακόμα.</p>;
  }

  return (
    <div>
      <h2>Εταιρείες</h2>

      <table>
        <thead>
          <tr>
            <th>Επωνυμία</th>
            <th>ΑΦΜ</th>
            <th>ΔΟΥ</th>
            <th>Πόλη</th>
          </tr>
        </thead>
        <tbody>
          {data.data.map((company) => (
            <tr key={company.id}>
              <td>{company.name}</td>
              <td>{company.vat_number}</td>
              <td>{company.tax_office ?? '—'}</td>
              <td>{company.city ?? '—'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}