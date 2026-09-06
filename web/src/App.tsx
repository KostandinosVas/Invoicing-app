import { AuthProvider, useAuth } from './auth/AuthContext';
import { Login } from './pages/Login';

function Shell() {
  const { user, isLoading, logout } = useAuth();

  if (isLoading) return <p>Φόρτωση…</p>;

  if (!user) return <Login />;

  return (
    <div>
      <p>Συνδεδεμένος ως {user.name}</p>
      <button onClick={() => logout()}>Αποσύνδεση</button>
    </div>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <Shell />
    </AuthProvider>
  );
}