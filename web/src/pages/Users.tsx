import { useState } from 'react';
import { isAxiosError } from 'axios';
import { useUsers, useUpdateUserRole, type RoleName } from '../hooks/useUsers';
import { useAuth } from '../auth/AuthContext';
import { PageHeader } from '../components/PageHeader';
import { Select } from '../components/Select';
import tableStyles from '../components/Table.module.css';

const ROLE_LABELS: Record<RoleName, string> = {
  admin: 'Διαχειριστής',
  accountant: 'Λογιστής',
  viewer: 'Προβολή μόνο',
};

const ROLE_OPTIONS = (Object.keys(ROLE_LABELS) as RoleName[]).map((role) => ({
  value: role,
  label: ROLE_LABELS[role],
}));

export function Users() {
  const { user: currentUser } = useAuth();
  const { data: users, isLoading, isError } = useUsers();
  const updateRole = useUpdateUserRole();
  const [error, setError] = useState<string | null>(null);

  if (currentUser?.role !== 'admin') {
    return <p>Μόνο ο διαχειριστής μπορεί να διαχειριστεί χρήστες.</p>;
  }

  if (isLoading) return <p>Φόρτωση…</p>;
  if (isError || !users) return <p>Δεν ήταν δυνατή η φόρτωση των χρηστών.</p>;

  async function handleChange(userId: number, name: string, role: RoleName) {
    if (!window.confirm(`Αλλαγή ρόλου του χρήστη «${name}» σε «${ROLE_LABELS[role]}»;`)) {
      return;
    }

    setError(null);

    try {
      await updateRole.mutateAsync({ userId, role });
    } catch (err) {
      if (isAxiosError(err) && err.response?.status === 403) {
        setError('Δεν επιτρέπεται αυτή η αλλαγή ρόλου.');
      } else {
        setError('Η αλλαγή ρόλου απέτυχε.');
      }
    }
  }

  return (
    <>
      <PageHeader title="Χρήστες" subtitle={`${users.length} χρήστες`} />

      {error && <p style={{ color: 'var(--color-danger)' }}>{error}</p>}

      <div className={tableStyles.wrapper}>
        <table className={tableStyles.table}>
          <thead>
            <tr>
              <th>Όνομα</th>
              <th>Email</th>
              <th>Ρόλος</th>
            </tr>
          </thead>
          <tbody>
            {users.map((user) => (
              <tr key={user.id}>
                <td>{user.name}</td>
                <td>{user.email}</td>
                <td>
                  {user.id === currentUser.id ? (
                    <span className={tableStyles.muted}>
                      {ROLE_LABELS[user.role]} (εσείς)
                    </span>
                  ) : (
                    <Select
                      label=""
                      options={ROLE_OPTIONS}
                      value={user.role}
                      disabled={updateRole.isPending}
                      onChange={(e) =>
                        handleChange(user.id, user.name, e.target.value as RoleName)
                      }
                    />
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}