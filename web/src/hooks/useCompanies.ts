import { useQuery } from '@tanstack/react-query';
import { api } from '../lib/api';
import type { Company, Paginated } from '../types/api';

export function useCompanies() {
  return useQuery({
    queryKey: ['companies'],
    queryFn: async () => {
      const res = await api.get<Paginated<Company>>('/api/companies');
      return res.data;
    },
  });
}