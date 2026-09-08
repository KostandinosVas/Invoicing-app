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

export function AppLayout() {
  const { user, logout } = useAuth();

  return (
    <div className={styles.shell}>
      <aside className={styles.sidebar}>
        <div className={styles.brand}>Τιμολόγηση</div>

        <nav className={styles.nav}>
          {links.map((link) => (
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