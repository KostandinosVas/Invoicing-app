import { useQuery } from '@tanstack/react-query';
import { api } from '../lib/api';
import type { Customer, Paginated } from '../types/api';

export function useCustomers(companyId?: number) {
  return useQuery({
    queryKey: ['customers', companyId ?? null],
    queryFn: async () => {
      const res = await api.get<Paginated<Customer>>('/api/customers', {
        params: companyId ? { company_id: companyId } : undefined,
      });
      return res.data;
    },
  });
}