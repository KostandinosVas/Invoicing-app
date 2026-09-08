import { useQuery } from '@tanstack/react-query';
import { api } from '../lib/api';
import type { Invoice, InvoiceStatus, Paginated } from '../types/api';

type Filters = {
  companyId?: number;
  status?: InvoiceStatus;
};

export function useInvoices({ companyId, status }: Filters = {}) {
  return useQuery({
    queryKey: ['invoices', companyId ?? null, status ?? null],
    queryFn: async () => {
      const res = await api.get<Paginated<Invoice>>('/api/invoices', {
        params: {
          ...(companyId ? { company_id: companyId } : {}),
          ...(status ? { status } : {}),
        },
      });
      return res.data;
    },
  });
}

export function useInvoice(id: number) {
  return useQuery({
    queryKey: ['invoices', 'detail', id],
    queryFn: async () => {
      const res = await api.get<{ data: Invoice }>(`/api/invoices/${id}`);
      return res.data.data;
    },
  });
}