import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';
import type { Item } from '../types/api';

export type CreateItemInput = {
  company_id: number;
  name: string;
  code?: string;
  unit?: string;
  unit_price_cents: number;
  vat_rate: number;
  is_active?: boolean;
};

export function useCreateItem() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: CreateItemInput) => {
      const res = await api.post<{ data: Item }>('/api/items', input);
      return res.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['items'] });
    },
  });
}