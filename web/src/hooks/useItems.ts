import { useQuery } from '@tanstack/react-query';
import { api } from '../lib/api';
import type { Item, Paginated } from '../types/api';

type Filters = {
  companyId?: number;
  activeOnly?: boolean;
};

export function useItems({ companyId, activeOnly }: Filters = {}) {
  return useQuery({
    queryKey: ['items', companyId ?? null, activeOnly ?? false],
    queryFn: async () => {
      const res = await api.get<Paginated<Item>>('/api/items', {
        params: {
          ...(companyId ? { company_id: companyId } : {}),
          ...(activeOnly ? { active_only: 1 } : {}),
        },
      });
      return res.data;
    },
  });
}