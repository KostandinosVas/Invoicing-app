import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';
import type { Customer } from '../types/api';

export type CreateCustomerInput = {
  company_id: number;
  name: string;
  vat_number?: string;
  tax_office?: string;
  address?: string;
  city?: string;
  postal_code?: string;
  country?: string;
};

export function useCreateCustomer() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: CreateCustomerInput) => {
      const res = await api.post<{ data: Customer }>('/api/customers', input);
      return res.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] });
    },
  });
}