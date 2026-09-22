import { NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { Button } from '../components/Button';
import styles from './AppLayout.module.css';

const links = [
  { to: '/companies', label: 'Εταιρείες' },
  { to: '/customers', label: 'Πελάτες' },
  { to: '/items', label: 'Είδη' },
  { to: '/invoices', label: 'Παραστατικά' },
  { to: '/series', label: 'Σειρές' },
];

const adminLinks = [{ to: '/users', label: 'Χρήστες' }];

const roleLabels = {
  admin: 'Διαχειριστής',
  accountant: 'Λογιστής',
  viewer: 'Προβολή μόνο',
} as const;

export function AppLayout() {
  const { user, logout } = useAuth();

  const visibleLinks = user?.role === 'admin' ? [...links, ...adminLinks] : links;

  return (
    <div className={styles.shell}>
      <aside className={styles.sidebar}>
        <div className={styles.brand}>Τιμολόγηση</div>

        <nav className={styles.nav}>
          {visibleLinks.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              className={({ isActive }) =>
                isActive
                  ? `${styles.navLink} ${styles.navLinkActive}`
                  : styles.navLink
              }
            >
              {link.label}
            </NavLink>
          ))}
        </nav>

        <div className={styles.user}>
          <span className={styles.userName}>{user?.name}</span>
          {user && <span className={styles.userName}>{roleLabels[user.role]}</span>}
          <Button variant="secondary" size="small" onClick={() => logout()}>
            Αποσύνδεση
          </Button>
        </div>
      </aside>

      <main className={styles.content}>
        <Outlet />
      </main>
    </div>
  );
}