import { Link, Outlet } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';

export function AppLayout() {
  const { user, logout } = useAuth();

  return (
    <div>
      <header>
        <nav>
          <Link to="/">Επισκόπηση</Link>
          <Link to="/companies">Εταιρείες</Link>
          <Link to="/customers">Πελάτες</Link>
          <Link to="/items">Είδη</Link>
          <Link to="/invoices">Παραστατικά</Link>
        </nav>

        <div>
          <span>{user?.name}</span>
          <button onClick={() => logout()}>Αποσύνδεση</button>
        </div>
      </header>

      <main>
        <Outlet />
      </main>
    </div>
  );
}