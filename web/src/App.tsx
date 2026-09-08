import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider } from './auth/AuthContext';
import { RequireAuth } from './auth/RequireAuth';
import { AppLayout } from './layouts/AppLayout';
import { Login } from './pages/Login';
import { Companies } from './pages/Companies';
import { CompanyCreate } from './pages/CompanyCreate';
import { Customers } from './pages/Customers';
import { CustomerCreate } from './pages/CustomerCreate';
import { Items } from './pages/Items';
import { ItemCreate } from './pages/ItemCreate';
import { SeriesList } from './pages/SeriesList';
import { SeriesCreate } from './pages/SeriesCreate';

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<Login />} />

          <Route element={<RequireAuth />}>
            <Route element={<AppLayout />}>
              <Route path="/" element={<Navigate to="/companies" replace />} />
              <Route path="/companies" element={<Companies />} />
              <Route path="/companies/new" element={<CompanyCreate />} />
              <Route path="/customers" element={<Customers />} />
              <Route path="/customers/new" element={<CustomerCreate />} />
              <Route path="/items" element={<Items />} />
              <Route path="/items/new" element={<ItemCreate />} />
              <Route path="/series" element={<SeriesList />} />
              <Route path="/series/new" element={<SeriesCreate />} />
            </Route>
          </Route>

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}